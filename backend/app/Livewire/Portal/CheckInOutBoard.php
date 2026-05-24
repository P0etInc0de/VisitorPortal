<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Portal;

use App\Enums\VisitStatusEnum;
use App\Livewire\Concerns\RateLimitsSearch;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use App\Support\VisitorContactRequirement;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CheckInOutBoard extends Component
{
    use AuthorizesRequests;
    use RateLimitsSearch;

    public string $search = '';

    public ?string $walkInHostId = null;

    public ?string $walkInSiteId = null;

    public bool $walkInIsConfidential = true;

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

    public function mount(): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->walkInIsConfidential = $this->walkInConfidentialDefault();
        $this->walkInSiteId = (string) ($this->siteOptions()->first()?->id ?? $this->actor()->site_id);
    }

    public function checkIn(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('checkIn', Visitor::class);

        [$visit, $visitor, $user] = $this->resolveActionContext($visitId, $visitorId);
        $referenceNow = now();

        if (! $this->visitIsInCheckInWindow($visit, $referenceNow, $this->checkInWindowEnd($referenceNow))) {
            return;
        }

        $this->visitActionService()->checkInParticipant($visit, $visitor, $user);
    }

    public function checkOut(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('checkOut', Visitor::class);

        [$visit, $visitor, $user] = $this->resolveActionContext($visitId, $visitorId);

        $this->visitActionService()->checkOutParticipant($visit, $visitor, $user);
    }

    public function printBadge(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('print', Visitor::class);

        [$visit, $visitor] = $this->resolveActionContext($visitId, $visitorId);

        $this->visitActionService()->printBadge($visit, $visitor);
    }

    public function registerWalkIn(bool $printBadge = false)
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('create', Visit::class);
        $this->authorize('create', Visitor::class);
        $this->authorize('checkIn', Visitor::class);

        if ($printBadge) {
            $this->authorize('print', Visitor::class);
        }

        $payload = $this->validate([
            ...$this->walkInRules(),
            'walkInSiteId' => ['required', 'integer', Rule::exists('sites', 'id')->where('is_active', true)],
            'walkInHostId' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ]);
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $siteId = (int) $payload['walkInSiteId'];

        if (! $this->canSelectAnyHost($user) && ! $user->canAccessSite($siteId)) {
            throw ValidationException::withMessages([
                'walkInSiteId' => __('Sie können nur Standorte auswählen, denen Sie zugeordnet sind.'),
            ]);
        }

        $hostId = (int) $payload['walkInHostId'];

        if (! $this->hostOptions()->contains('id', $hostId)) {
            throw ValidationException::withMessages([
                'walkInHostId' => __('Sie können nur Hosts aus dem ausgewählten Standort auswählen.'),
            ]);
        }

        [$visit, $visitor] = DB::transaction(function () use ($payload, $user, $hostId, $siteId) {
            $visitor = $this->resolveWalkInVisitor($payload['walkIn'], $user);
            $visit = $this->createWalkInVisit($visitor, $user, $hostId, $siteId, $this->walkInIsConfidential);

            $visit->visitors()->syncWithoutDetaching([
                $visitor->id => [
                    'notes' => __('Walk-in am Empfang registriert.'),
                ],
            ]);

            $this->visitActionService()->checkInParticipant($visit, $visitor, $user);

            return [$visit->loadMissing('host'), $visitor];
        });

        $this->resetWalkIn();
        session()->flash('status', __('Walk-in wurde angelegt und eingecheckt.'));

        if ($printBadge) {
            $this->visitActionService()->printBadge($visit, $visitor);

            $this->dispatch('print-badge', url: route('reception.participants.badge', [$visit->id, $visitor->id]));
        }
    }

    public function render(): View
    {
        $this->authorize('viewAny', Visit::class);

        return view('livewire.portal.check-in-out-board', [
            'hosts' => $this->hostOptions(),
            'siteOptions' => $this->siteOptions(),
            'results' => $this->searchResults(),
            'checkInWindowHours' => $this->checkInWindowHours(),
        ])->layout('layouts.app', [
            'header' => new HtmlString(
                view('partials.page-header', [
                    'title' => __('Check-In / Out'),
                ])->render()
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    protected function walkInRules(): array
    {
        $rules = [
            'walkIn.title' => ['nullable', 'string', 'max:255'],
            'walkIn.first_name' => ['required', 'string', 'max:255'],
            'walkIn.name' => ['required', 'string', 'max:255'],
            'walkIn.email' => ['nullable', 'email', 'max:255'],
            'walkIn.phone' => ['nullable', 'string', 'max:50'],
            'walkIn.company' => ['nullable', 'string', 'max:255'],
        ];

        $requirement = VisitorContactRequirement::current();

        if (VisitorContactRequirement::requiresEmail($requirement)) {
            $rules['walkIn.email'][] = 'required';
        }

        if (VisitorContactRequirement::requiresPhone($requirement)) {
            $rules['walkIn.phone'][] = 'required';
        }

        if (VisitorContactRequirement::requiresOne($requirement)) {
            $rules['walkIn.email'][] = 'required_without:walkIn.phone';
            $rules['walkIn.phone'][] = 'required_without:walkIn.email';
        }

        return $rules;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function searchResults(): Collection
    {
        $actor = $this->actor();
        $canViewContactDetails = $actor->can('ViewContactDetails:Visitor');
        $referenceNow = now();
        $windowEnd = $this->checkInWindowEnd($referenceNow);
        $search = trim($this->search);

        if ($search !== '') {
            $this->enforceSearchRateLimit('check-in-out-board');
        }

        $visits = Visit::query()
            ->visibleTo($actor)
            ->with([
                'host:id,first_name,name',
                'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
            ])
            ->where('status', VisitStatusEnum::Planned->value)
            ->where('scheduled_from', '<=', $windowEnd)
            ->where(function ($query) use ($referenceNow): void {
                $query->where('scheduled_until', '>=', $referenceNow)
                    ->orWhereHas('visitors', function ($query): void {
                        $query->whereNotNull('visit_visitor.checked_in_at')
                            ->whereNull('visit_visitor.checked_out_at');
                    });
            })
            ->when($search !== '', function ($query) use ($search, $canViewContactDetails) {
                $query->where(function ($query) use ($search, $canViewContactDetails) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('host', function ($query) use ($search) {
                            $query->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        })
                        ->orWhereHas('visitors', function ($query) use ($search, $canViewContactDetails) {
                            $query->where('title', 'like', '%'.$search.'%')
                                ->orWhere('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('company', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);

                            if ($canViewContactDetails) {
                                $query->orWhere('email', 'like', '%'.$search.'%')
                                    ->orWhere('phone', 'like', '%'.$search.'%');
                            }
                        });
                });
            })
            ->orderBy('scheduled_from')
            ->get();

        return $visits
            ->flatMap(function (Visit $visit) use ($referenceNow, $windowEnd, $canViewContactDetails) {
                return $visit->visitors
                    ->filter(fn (Visitor $visitor) => $this->participantShouldBeListed($visit, $visitor, $referenceNow, $windowEnd))
                    ->map(fn (Visitor $visitor) => $this->mapParticipantResult($visit, $visitor, $referenceNow, $canViewContactDetails));
            })
            ->when($search !== '', function (Collection $results) use ($search) {
                return $results->filter(fn (array $result) => $this->participantMatchesSearch($result, $search));
            })
            ->sortBy([
                ['sort_bucket', 'asc'],
                ['sort_timestamp', 'asc'],
                ['display_name', 'asc'],
            ])
            ->take(20)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapParticipantResult(Visit $visit, Visitor $visitor, Carbon $referenceNow, bool $canViewContactDetails): array
    {
        $pivot = $visit->visitors->firstWhere('id', $visitor->id)?->pivot;
        $status = $this->resolveParticipantStatus((string) $visit->status, $pivot);
        $canOperate = $this->visitActionService()->canOperate($visit);
        $canCheckIn = $canOperate && $this->visitIsInCheckInWindow($visit, $referenceNow, $this->checkInWindowEnd($referenceNow));
        $displayName = trim(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
        ])));
        $scheduledFrom = $visit->scheduled_from;
        $sortDistanceSeconds = $scheduledFrom
            ? abs($scheduledFrom->diffInSeconds($referenceNow, false))
            : PHP_INT_MAX;

        return [
            'row_key' => "visit-{$visit->id}-visitor-{$visitor->id}",
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'badge_url' => route('reception.participants.badge', [$visit->id, $visitor->id]),
            'visit_title' => $visit->display_title,
            'visit_time' => optional($visit->scheduled_from)->format('H:i'),
            'visit_date' => optional($visit->scheduled_from)->format('d.m.'),
            'is_recurring' => filled($visit->recurring_visit_series_id),
            'recurrence_is_modified' => (bool) $visit->recurrence_is_modified,
            'host' => trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? '')),
            'display_name' => $displayName !== '' ? $displayName : __('Ohne Namen'),
            'title' => $visitor->title,
            'name' => trim(($visitor->first_name ?? '').' '.($visitor->name ?? '')),
            'company' => $visitor->company,
            'email' => $canViewContactDetails ? $visitor->email : null,
            'phone' => $canViewContactDetails ? $visitor->phone : null,
            'scheduled_from_timestamp' => $scheduledFrom?->timestamp ?? PHP_INT_MAX,
            'sort_bucket' => filled($pivot?->checked_out_at) ? 1 : 0,
            'sort_timestamp' => $this->participantSortTimestamp($visit, $visitor),
            'sort_distance_seconds' => $sortDistanceSeconds,
            'is_past' => $scheduledFrom && $scheduledFrom->lt($referenceNow) ? 1 : 0,
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'checked_in_label' => $this->formatActionTimestamp($pivot?->checked_in_at),
            'checked_out_label' => $this->formatActionTimestamp($pivot?->checked_out_at),
            'badge_ready' => filled($pivot?->badge_printed_at),
            'can_print_badge' => $canOperate,
            'can_check_in' => $canCheckIn && (blank($pivot?->checked_in_at) || filled($pivot?->checked_out_at)),
            'check_in_label' => filled($pivot?->checked_out_at) ? __('Erneut einchecken') : __('Check-in'),
            'can_check_out' => $canOperate && filled($pivot?->checked_in_at) && blank($pivot?->checked_out_at),
        ];
    }

    private function participantShouldBeListed(Visit $visit, Visitor $visitor, Carbon $referenceNow, Carbon $windowEnd): bool
    {
        $pivot = $visitor->pivot;

        return $this->visitIsInCheckInWindow($visit, $referenceNow, $windowEnd)
            || (filled($pivot?->checked_in_at) && blank($pivot?->checked_out_at));
    }

    private function participantSortTimestamp(Visit $visit, Visitor $visitor): int
    {
        $pivot = $visitor->pivot;
        $scheduledFrom = $visit->scheduled_from;
        $scheduledUntil = $visit->scheduled_until;

        if (
            filled($pivot?->checked_in_at)
            && blank($pivot?->checked_out_at)
            && $scheduledFrom instanceof Carbon
            && $scheduledUntil instanceof Carbon
            && $scheduledFrom->isSameDay($scheduledUntil)
        ) {
            return $scheduledUntil->timestamp;
        }

        return $scheduledFrom?->timestamp ?? PHP_INT_MAX;
    }

    private function visitIsInCheckInWindow(Visit $visit, Carbon $referenceNow, Carbon $windowEnd): bool
    {
        return $visit->scheduled_from instanceof Carbon
            && $visit->scheduled_until instanceof Carbon
            && $visit->scheduled_from->lte($windowEnd)
            && $visit->scheduled_until->gte($referenceNow);
    }

    private function checkInWindowEnd(?Carbon $referenceNow = null): Carbon
    {
        return ($referenceNow ?? now())->copy()->addHours($this->checkInWindowHours());
    }

    private function checkInWindowHours(): int
    {
        return max(0, (int) config('reception.check_in_window_hours', 48));
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
        $this->authorize('view', $visit);
        $visitor = Visitor::query()->findOrFail($visitorId);

        return [$visit, $visitor, $user];
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    private function resolveWalkInVisitor(array $payload, User $actor): Visitor
    {
        $email = $this->normalizeText($payload['email'] ?? null);
        $phone = $this->normalizeText($payload['phone'] ?? null);
        $emailVisitor = $email ? Visitor::query()->visibleTo($actor)->where('email', $email)->first() : null;

        if ($emailVisitor) {
            return $emailVisitor;
        }

        return Visitor::query()->create([
            'title' => $this->normalizeText($payload['title'] ?? null),
            'first_name' => $payload['first_name'],
            'name' => $payload['name'],
            'email' => $email,
            'phone' => $phone,
            'company' => $this->normalizeText($payload['company'] ?? null),
            'notes' => null,
            'created_by_user_id' => $actor->id,
        ]);
    }

    private function createWalkInVisit(Visitor $visitor, User $user, int $hostId, int $siteId, bool $isConfidential): Visit
    {
        $displayName = trim(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
        ])));

        return Visit::query()->create([
            'title' => __('Walk-in: :name', ['name' => $displayName !== '' ? $displayName : __('Gast')]),
            'site_id' => $siteId,
            'host_user_id' => $hostId,
            'created_by_user_id' => $user->id,
            'scheduled_from' => now(),
            'scheduled_until' => now()->addHour(),
            'status' => VisitStatusEnum::Planned->value,
            'is_walk_in' => true,
            'is_confidential' => $isConfidential,
            'notes' => __('Am Empfang als Walk-in erfasst.'),
        ]);
    }

    private function hostOptions()
    {
        $actor = $this->actor();
        $siteId = $this->selectedWalkInSiteId();

        if (! $this->canSelectAnyHost($actor) && ! $actor->canAccessSite($siteId)) {
            return collect();
        }

        $query = User::query()
            ->select('id', 'first_name', 'name', 'site_id')
            ->with('sites:id')
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor']))
            ->orderBy('first_name')
            ->orderBy('name');

        return $this->whereCanAccessAnySite($query, [$siteId])->get();
    }

    private function siteOptions()
    {
        $actor = $this->actor();

        if ($this->canSelectAnyHost($actor)) {
            return Site::query()
                ->select('id', 'name')
                ->active()
                ->orderBy('name')
                ->get();
        }

        return Site::query()
            ->select('id', 'name')
            ->active()
            ->whereIn('id', $actor->assignedSiteIds()->all())
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) $actor->site_id])
            ->orderBy('name')
            ->get();
    }

    private function selectedWalkInSiteId(): int
    {
        $siteId = (int) ($this->walkInSiteId ?: $this->siteOptions()->first()?->id ?: $this->actor()->site_id);

        $this->walkInSiteId = (string) $siteId;

        return $siteId;
    }

    private function whereCanAccessAnySite($query, array $siteIds)
    {
        return $query->where(function ($query) use ($siteIds): void {
            $query->whereIn('site_id', $siteIds)
                ->orWhereHas('sites', fn ($query) => $query->whereIn('sites.id', $siteIds));
        });
    }

    private function canSelectAnyHost(User $actor): bool
    {
        return $actor->can('ViewAny:Visit')
            || $actor->can('EditAny:Visit')
            || $actor->can('DeleteAny:Visit')
            || $actor->can('CreateForAny:Visit');
    }

    private function actor(): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function resetWalkIn(): void
    {
        $this->reset('walkIn');
        $this->walkInHostId = null;
        $this->walkInIsConfidential = $this->walkInConfidentialDefault();

        $this->walkIn = [
            'title' => '',
            'first_name' => '',
            'name' => '',
            'email' => '',
            'phone' => '',
            'company' => '',
        ];
    }

    private function walkInConfidentialDefault(): bool
    {
        return (bool) config('privacy.walk_in_confidential_default', true);
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function visitActionService(): VisitActionService
    {
        return app(VisitActionService::class);
    }

    private function formatActionTimestamp(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('d.m.Y H:i');
        }

        return Carbon::parse($value)->format('d.m.Y H:i');
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
            return ['label' => __('Ausweis bereit'), 'class' => 'badge-info badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }
}
