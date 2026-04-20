<?php

namespace App\Livewire\Portal;

use App\Enums\VisitorStatusEnum;
use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class CheckInOutBoard extends Component
{
    public string $search = '';

    public ?string $walkInHostId = null;

    protected VisitActionService $visitActionService;

    /**
     * @var array<string, string>
     */
    public array $walkIn = [
        'title' => '',
        'first_name' => '',
        'name' => '',
        'email' => '',
        'phone' => '',
        'company' => '',
    ];

    public function boot(VisitActionService $visitActionService): void
    {
        $this->visitActionService = $visitActionService;
    }

    public function checkIn(int $visitId, int $visitorId): void
    {
        [$visit, $visitor, $user] = $this->resolveActionContext($visitId, $visitorId);

        $this->visitActionService->checkInParticipant($visit, $visitor, $user);

        session()->flash('status', __('Teilnehmender wurde eingecheckt.'));
    }

    public function checkOut(int $visitId, int $visitorId): void
    {
        [$visit, $visitor, $user] = $this->resolveActionContext($visitId, $visitorId);

        $this->visitActionService->checkOutParticipant($visit, $visitor, $user);

        session()->flash('status', __('Teilnehmender wurde ausgecheckt.'));
    }

    public function printBadge(int $visitId, int $visitorId): void
    {
        $this->dispatch('print-badge', url: route('reception.participants.badge', [$visitId, $visitorId]));
    }

    public function registerWalkIn(bool $printBadge = false)
    {
        $payload = $this->validate([
            ...$this->walkInRules(),
            'walkInHostId' => ['required', 'integer', 'exists:users,id'],
        ]);
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $hostId = (int) $payload['walkInHostId'];

        [$visit, $visitor] = DB::transaction(function () use ($payload, $user, $hostId) {
            $visitor = $this->resolveWalkInVisitor($payload['walkIn']);
            $visit = $this->createWalkInVisit($visitor, $user, $hostId);

            $visit->visitors()->syncWithoutDetaching([
                $visitor->id => [
                    'notes' => __('Walk-in am Empfang registriert.'),
                ],
            ]);

            $this->visitActionService->checkInParticipant($visit, $visitor, $user);

            return [$visit->loadMissing('responsibleUser'), $visitor];
        });

        $this->resetWalkIn();
        session()->flash('status', __('Walk-in wurde angelegt und eingecheckt.'));

        if ($printBadge) {
            $this->dispatch('print-badge', url: route('reception.participants.badge', [$visit->id, $visitor->id]));
        }
    }

    public function render(): View
    {
        return view('livewire.portal.check-in-out-board', [
            'hosts' => User::query()
                ->select('id', 'first_name', 'name')
                ->orderBy('first_name')
                ->orderBy('name')
                ->get(),
            'results' => $this->searchResults(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    protected function walkInRules(): array
    {
        return [
            'walkIn.title' => ['nullable', 'string', 'max:255'],
            'walkIn.first_name' => ['required', 'string', 'max:255'],
            'walkIn.name' => ['required', 'string', 'max:255'],
            'walkIn.email' => ['nullable', 'email', 'max:255'],
            'walkIn.phone' => ['nullable', 'string', 'max:255'],
            'walkIn.company' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchResults(): Collection
    {
        $todayStart = now()->startOfDay();
        $search = trim($this->search);

        $visits = Visit::query()
            ->with([
                'host:id,first_name,name',
                'responsibleUser:id,first_name,name',
                'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
            ])
            ->where('status', '!=', VisitStatusEnum::Canceled->value)
            ->where(function ($query) use ($todayStart) {
                $query->where('scheduled_until', '>=', $todayStart)
                    ->orWhere(function ($query) use ($todayStart) {
                        $query->whereNull('scheduled_until')
                            ->where('scheduled_from', '>=', $todayStart);
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('host', function ($query) use ($search) {
                            $query->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        })
                        ->orWhereHas('visitors', function ($query) use ($search) {
                            $query->where('title', 'like', '%'.$search.'%')
                                ->orWhere('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('company', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderBy('scheduled_from')
            ->limit($search === '' ? 15 : 30)
            ->get();

        return $visits
            ->flatMap(function (Visit $visit) {
                return $visit->visitors->map(fn (Visitor $visitor) => $this->mapParticipantResult($visit, $visitor));
            })
            ->when($search !== '', function (Collection $results) use ($search) {
                return $results->filter(fn (array $result) => $this->participantMatchesSearch($result, $search));
            })
            ->take(20)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapParticipantResult(Visit $visit, Visitor $visitor): array
    {
        $pivot = $visit->visitors->firstWhere('id', $visitor->id)?->pivot;
        $status = $this->resolveParticipantStatus((string) $visit->status, $pivot);
        $displayName = trim(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
        ])));

        return [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'badge_url' => route('reception.participants.badge', [$visit->id, $visitor->id]),
            'visit_title' => $visit->display_title,
            'visit_time' => optional($visit->scheduled_from)->format('H:i'),
            'visit_date' => optional($visit->scheduled_from)->format('d.m.'),
            'host' => trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? '')),
            'display_name' => $displayName !== '' ? $displayName : __('Ohne Namen'),
            'title' => $visitor->title,
            'name' => trim(($visitor->first_name ?? '').' '.($visitor->name ?? '')),
            'company' => $visitor->company,
            'email' => $visitor->email,
            'phone' => $visitor->phone,
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'badge_ready' => filled($pivot?->badge_printed_at),
            'can_check_in' => blank($pivot?->checked_in_at) || filled($pivot?->checked_out_at),
            'check_in_label' => filled($pivot?->checked_out_at) ? __('Erneut einchecken') : __('Check-in'),
            'can_check_out' => filled($pivot?->checked_in_at) && blank($pivot?->checked_out_at),
        ];
    }

    private function participantMatchesSearch(array $result, string $search): bool
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $result['display_name'] ?? null,
            $result['company'] ?? null,
            $result['email'] ?? null,
            $result['phone'] ?? null,
            $result['visit_title'] ?? null,
            $result['host'] ?? null,
        ])));

        return Str::contains($haystack, Str::lower($search));
    }

    /**
     * @return array{0: Visit, 1: Visitor, 2: User}
     */
    private function resolveActionContext(int $visitId, int $visitorId): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $visit = Visit::query()->findOrFail($visitId);
        $visitor = Visitor::query()->findOrFail($visitorId);

        return [$visit, $visitor, $user];
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    private function resolveWalkInVisitor(array $payload): Visitor
    {
        $email = $this->normalizeText($payload['email'] ?? null);
        $phone = $this->normalizeText($payload['phone'] ?? null);

        $existingVisitor = null;

        if ($email) {
            $existingVisitor = Visitor::query()->where('email', $email)->first();
        } elseif ($phone) {
            $existingVisitor = Visitor::query()->where('phone', $phone)->first();
        }

        if ($existingVisitor) {
            $existingVisitor->update([
                'title' => $this->normalizeText($payload['title'] ?? null) ?: $existingVisitor->title,
                'first_name' => $payload['first_name'],
                'name' => $payload['name'],
                'email' => $email ?: $existingVisitor->email,
                'phone' => $phone ?: $existingVisitor->phone,
                'company' => $this->normalizeText($payload['company'] ?? null) ?: $existingVisitor->company,
            ]);

            return $existingVisitor;
        }

        $token = (string) Str::ulid();

        return Visitor::query()->create([
            'title' => $this->normalizeText($payload['title'] ?? null),
            'first_name' => $payload['first_name'],
            'name' => $payload['name'],
            'email' => $email ?: 'walkin-'.$token.'@local.invalid',
            'phone' => $phone ?: 'walkin-'.$token,
            'company' => $this->normalizeText($payload['company'] ?? null),
            'status' => VisitorStatusEnum::Planned,
            'notes' => null,
        ]);
    }

    private function createWalkInVisit(Visitor $visitor, User $user, int $hostId): Visit
    {
        $displayName = trim(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
        ])));

        return Visit::query()->create([
            'title' => __('Walk-in: :name', ['name' => $displayName !== '' ? $displayName : __('Gast')]),
            'user_id' => $hostId,
            'responsible_user_id' => $user->id,
            'scheduled_from' => now(),
            'scheduled_until' => now()->addHour(),
            'status' => VisitStatusEnum::Planned->value,
            'notes' => __('Am Empfang als Walk-in erfasst.'),
        ]);
    }

    private function resetWalkIn(): void
    {
        $this->reset('walkIn');
        $this->walkInHostId = null;

        $this->walkIn = [
            'title' => '',
            'first_name' => '',
            'name' => '',
            'email' => '',
            'phone' => '',
            'company' => '',
        ];
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array{label: string, class: string}
     */
    private function resolveParticipantStatus(string $visitStatus, mixed $pivot): array
    {
        if (filled($pivot?->checked_out_at)) {
            return ['label' => __('Ausgecheckt'), 'class' => 'badge-neutral badge-outline'];
        }

        if (filled($pivot?->checked_in_at)) {
            return ['label' => __('Eingecheckt'), 'class' => 'badge-success badge-outline'];
        }

        if (filled($pivot?->badge_printed_at)) {
            return ['label' => __('Badge bereit'), 'class' => 'badge-info badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }

}
