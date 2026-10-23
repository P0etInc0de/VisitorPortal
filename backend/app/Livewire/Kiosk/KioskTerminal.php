<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Kiosk;

use App\Enums\VisitStatusEnum;
use App\Livewire\Concerns\RateLimitsSearch;
use App\Models\Kiosk;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use App\Support\VisitorContactRequirement;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class KioskTerminal extends Component
{
    use RateLimitsSearch;

    private const SIGNATURE_PREFIX = 'data:image/png;base64,';

    private const SIGNATURE_MAX_BYTES = 3_000_000;

    public Kiosk $kiosk;

    public string $screen = 'home';

    public string $search = '';

    public string $code = '';

    public ?string $statusMessage = null;

    public string $statusTone = 'success';

    public ?string $signatureData = null;

    public ?string $signatureContext = null;

    /**
     * @var array<string, mixed>
     */
    public array $pendingCheckIn = [];

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

    public function mount(string $token): void
    {
        $kiosk = Kiosk::query()->with('site')->where('token', $token)->first();

        abort_if(! ($kiosk instanceof Kiosk) || ! $kiosk->is_active || ! $kiosk->site?->is_active, 404);

        $this->kiosk = $kiosk;
        $this->touchLastSeen();
    }

    public function showHome(): void
    {
        $this->resetFlow();
        $this->screen = 'home';
    }

    public function showSearch(): void
    {
        $this->ensureKioskActive();
        $this->resetFlow();
        $this->screen = 'search';
    }

    public function showQr(): void
    {
        $this->ensureKioskActive();
        $this->resetFlow();
        $this->screen = 'qr';
    }

    public function showWalkIn(): void
    {
        $this->ensureKioskActive();

        if (! $this->walkInEnabled()) {
            return;
        }

        $this->resetFlow();
        $this->screen = 'walkin';
    }

    public function showCheckout(): void
    {
        $this->ensureKioskActive();

        if (! $this->checkOutEnabled()) {
            return;
        }

        $this->resetFlow();
        $this->screen = 'checkout';
    }

    public function checkInVisitor(int $visitId, int $visitorId): void
    {
        $this->ensureKioskActive();

        [$visit, $visitor] = $this->resolveParticipant($visitId, $visitorId);

        if (filled($visitor->pivot->checked_in_at) && blank($visitor->pivot->checked_out_at)) {
            $this->finish(__('Sie sind bereits eingecheckt.'), 'info');

            return;
        }

        if ($this->signatureRequired()) {
            $this->requestSignature(
                ['type' => 'visitor', 'visit_id' => $visit->id, 'visitor_id' => $visitor->id],
                $this->displayName($visitor),
            );

            return;
        }

        $this->performVisitorCheckIn($visit->id, $visitor->id, null);
    }

    public function checkOutVisitor(int $visitId, int $visitorId): void
    {
        $this->ensureKioskActive();

        [$visit, $visitor] = $this->resolveParticipant($visitId, $visitorId);

        if (blank($visitor->pivot->checked_in_at) || filled($visitor->pivot->checked_out_at)) {
            $this->finish(__('Sie sind derzeit nicht eingecheckt.'), 'info');

            return;
        }

        try {
            $this->visitActionService()->checkOutParticipant($visit, $visitor);
        } catch (ValidationException) {
            $this->finish(__('Dieser Besuch kann nicht mehr bearbeitet werden.'), 'error');

            return;
        }

        $this->finish(__('Auf Wiedersehen, :name! Sie sind ausgecheckt.', ['name' => $this->displayName($visitor)]));
    }

    public function submitCode(): void
    {
        $this->ensureKioskActive();

        $this->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $this->enforceSearchRateLimit('kiosk-code');

        $visit = Visit::query()
            ->where('site_id', $this->kiosk->site_id)
            ->where('check_in_code', Str::lower(trim($this->code)))
            ->with(['visitors'])
            ->first();

        if (! $visit instanceof Visit) {
            $this->addError('code', __('Dieser Code ist ungültig oder gehört nicht zu diesem Standort.'));

            return;
        }

        $pending = $visit->visitors->filter(
            fn (Visitor $visitor): bool => blank($visitor->pivot->checked_in_at) || filled($visitor->pivot->checked_out_at)
        );

        if ($pending->isEmpty()) {
            $this->finish(__('Dieser Besuch ist bereits eingecheckt.'), 'info');

            return;
        }

        if ($this->signatureRequired()) {
            $this->requestSignature(
                ['type' => 'code', 'visit_id' => $visit->id, 'visitor_ids' => $pending->pluck('id')->map(fn ($id): int => (int) $id)->all()],
                $pending->map(fn (Visitor $visitor): string => $this->displayName($visitor))->implode(', '),
            );

            return;
        }

        $this->performCodeCheckIn($visit->id, $pending->pluck('id')->map(fn ($id): int => (int) $id)->all(), null);
    }

    public function registerWalkIn(): void
    {
        $this->ensureKioskActive();

        if (! $this->walkInEnabled()) {
            return;
        }

        $payload = $this->validate($this->walkInRules());

        if ($this->signatureRequired()) {
            $this->requestSignature(
                ['type' => 'walkin', 'payload' => $payload['walkIn']],
                trim(($payload['walkIn']['first_name'] ?? '').' '.($payload['walkIn']['name'] ?? '')),
            );

            return;
        }

        $this->performWalkIn($payload['walkIn'], null);
    }

    public function confirmSignature(): void
    {
        $this->ensureKioskActive();

        if ($this->screen !== 'signature' || $this->pendingCheckIn === []) {
            $this->showHome();

            return;
        }

        if (! $this->hasValidSignature($this->signatureData)) {
            $this->addError('signature', __('Bitte unterschreiben Sie, um einzuchecken.'));

            return;
        }

        $signature = (string) $this->signatureData;
        $pending = $this->pendingCheckIn;
        $this->pendingCheckIn = [];

        match ($pending['type'] ?? null) {
            'visitor' => $this->performVisitorCheckIn((int) $pending['visit_id'], (int) $pending['visitor_id'], $signature),
            'code' => $this->performCodeCheckIn((int) $pending['visit_id'], (array) $pending['visitor_ids'], $signature),
            'walkin' => $this->performWalkIn((array) $pending['payload'], $signature),
            default => $this->finish(__('Unbekannte Aktion.'), 'error'),
        };
    }

    public function cancelSignature(): void
    {
        $this->resetFlow();
        $this->screen = 'home';
    }

    public function render(): View
    {
        return view('livewire.kiosk.kiosk-terminal', [
            'checkInResults' => $this->screen === 'search' ? $this->searchParticipants(true) : collect(),
            'checkOutResults' => $this->screen === 'checkout' ? $this->searchParticipants(false) : collect(),
            'walkInEnabled' => $this->walkInEnabled(),
            'checkOutEnabled' => $this->checkOutEnabled(),
            'autoResetSeconds' => max(10, (int) config('kiosk.auto_reset_seconds', 45)),
        ])->layout('layouts.kiosk', [
            'kiosk' => $this->kiosk,
        ]);
    }

    /**
     * @param  array<string, mixed>  $pending
     */
    private function requestSignature(array $pending, string $context): void
    {
        $this->pendingCheckIn = $pending;
        $this->signatureContext = $context;
        $this->signatureData = null;
        $this->resetErrorBag('signature');
        $this->screen = 'signature';
    }

    private function performVisitorCheckIn(int $visitId, int $visitorId, ?string $signature): void
    {
        [$visit, $visitor] = $this->resolveParticipant($visitId, $visitorId);

        if (filled($visitor->pivot->checked_in_at) && blank($visitor->pivot->checked_out_at)) {
            $this->finish(__('Sie sind bereits eingecheckt.'), 'info');

            return;
        }

        try {
            $this->visitActionService()->checkInParticipant($visit, $visitor, null, $signature);
        } catch (ValidationException) {
            $this->finish(__('Dieser Besuch kann nicht mehr eingecheckt werden.'), 'error');

            return;
        }

        $this->finish(__('Willkommen, :name! Sie sind eingecheckt.', ['name' => $this->displayName($visitor)]));
    }

    /**
     * @param  array<int, int>  $visitorIds
     */
    private function performCodeCheckIn(int $visitId, array $visitorIds, ?string $signature): void
    {
        $visit = Visit::query()
            ->where('site_id', $this->kiosk->site_id)
            ->whereKey($visitId)
            ->with(['visitors'])
            ->firstOrFail();

        $pending = $visit->visitors
            ->whereIn('id', $visitorIds)
            ->filter(fn (Visitor $visitor): bool => blank($visitor->pivot->checked_in_at) || filled($visitor->pivot->checked_out_at));

        if ($pending->isEmpty()) {
            $this->finish(__('Dieser Besuch ist bereits eingecheckt.'), 'info');

            return;
        }

        try {
            DB::transaction(function () use ($visit, $pending, $signature): void {
                foreach ($pending as $visitor) {
                    $this->visitActionService()->checkInParticipant($visit, $visitor, null, $signature);
                }
            });
        } catch (ValidationException) {
            $this->finish(__('Dieser Besuch kann nicht mehr eingecheckt werden.'), 'error');

            return;
        }

        $names = $pending->map(fn (Visitor $visitor): string => $this->displayName($visitor))->implode(', ');

        $this->finish(__('Willkommen! Eingecheckt: :names', ['names' => $names]));
    }

    /**
     * @param  array<string, string|null>  $walkInPayload
     */
    private function performWalkIn(array $walkInPayload, ?string $signature): void
    {
        if (! $this->walkInEnabled()) {
            return;
        }

        $payload = validator(['walkIn' => $walkInPayload], $this->walkInRules())->validate();

        $host = $this->kiosk->walkInHost;

        if (! $host instanceof User || ! $host->is_active) {
            $this->finish(__('Für dieses Terminal ist kein gültiger Host konfiguriert.'), 'error');

            return;
        }

        [$visitor] = DB::transaction(function () use ($payload, $host, $signature): array {
            $visitor = $this->resolveWalkInVisitor($payload['walkIn']);

            $visit = Visit::query()->create([
                'title' => __('Kiosk-Walk-in: :name', ['name' => $this->displayName($visitor) ?: __('Gast')]),
                'site_id' => $this->kiosk->site_id,
                'host_user_id' => $host->id,
                'created_by_user_id' => null,
                'scheduled_from' => now(),
                'scheduled_until' => now()->addHour(),
                'status' => VisitStatusEnum::Planned->value,
                'is_walk_in' => true,
                'is_confidential' => (bool) config('privacy.walk_in_confidential_default', true),
                'notes' => __('Am Kiosk als Walk-in erfasst.'),
            ]);

            $visit->visitors()->syncWithoutDetaching([
                $visitor->id => [
                    'notes' => __('Walk-in am Kiosk registriert.'),
                ],
            ]);

            $this->visitActionService()->checkInParticipant($visit, $visitor, null, $signature);

            return [$visitor];
        });

        $this->finish(__('Willkommen, :name! Sie sind eingecheckt.', ['name' => $this->displayName($visitor)]));
    }

    private function hasValidSignature(?string $signature): bool
    {
        if (! is_string($signature) || ! str_starts_with($signature, self::SIGNATURE_PREFIX)) {
            return false;
        }

        return strlen($signature) <= self::SIGNATURE_MAX_BYTES;
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
    private function searchParticipants(bool $forCheckIn): Collection
    {
        $search = trim($this->search);

        if (Str::length($search) < 2) {
            return collect();
        }

        $this->enforceSearchRateLimit('kiosk-search');

        $referenceNow = now();
        $windowEnd = $referenceNow->copy()->addHours(max(0, (int) config('reception.check_in_window_hours', 48)));

        $visits = Visit::query()
            ->where('site_id', $this->kiosk->site_id)
            ->where('status', VisitStatusEnum::Planned->value)
            ->where('scheduled_from', '<=', $windowEnd)
            ->where(function ($query) use ($referenceNow): void {
                $query->where('scheduled_until', '>=', $referenceNow)
                    ->orWhereHas('visitors', function ($query): void {
                        $query->whereNotNull('visit_visitor.checked_in_at')
                            ->whereNull('visit_visitor.checked_out_at');
                    });
            })
            ->whereHas('visitors', function ($query) use ($search): void {
                $query->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('company', 'like', '%'.$search.'%')
                    ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
            })
            ->with([
                'host:id,first_name,name',
                'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
            ])
            ->orderBy('scheduled_from')
            ->limit(40)
            ->get();

        return $visits
            ->flatMap(function (Visit $visit) use ($forCheckIn, $search): Collection {
                return $visit->visitors
                    ->filter(function (Visitor $visitor) use ($forCheckIn): bool {
                        return $forCheckIn
                            ? (blank($visitor->pivot->checked_in_at) || filled($visitor->pivot->checked_out_at))
                            : (filled($visitor->pivot->checked_in_at) && blank($visitor->pivot->checked_out_at));
                    })
                    ->filter(fn (Visitor $visitor): bool => $this->participantMatchesSearch($visitor, $search))
                    ->map(fn (Visitor $visitor): array => $this->mapParticipant($visit, $visitor));
            })
            ->take(max(1, (int) config('kiosk.search_result_limit', 15)))
            ->values();
    }

    private function participantMatchesSearch(Visitor $visitor, string $search): bool
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
            $visitor->company,
        ])));

        return Str::contains($haystack, Str::lower($search));
    }

    /**
     * @return array<string, mixed>
     */
    private function mapParticipant(Visit $visit, Visitor $visitor): array
    {
        return [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'display_name' => $this->displayName($visitor),
            'company' => $visitor->company,
            'host' => trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? '')),
            'visit_time' => $visit->scheduled_from?->format('d.m. H:i'),
        ];
    }

    /**
     * @return array{0: Visit, 1: Visitor}
     */
    private function resolveParticipant(int $visitId, int $visitorId): array
    {
        $visit = Visit::query()
            ->where('site_id', $this->kiosk->site_id)
            ->whereKey($visitId)
            ->with(['visitors'])
            ->firstOrFail();

        $visitor = $visit->visitors->firstWhere('id', $visitorId);

        abort_if(! $visitor instanceof Visitor, 404);

        return [$visit, $visitor];
    }

    /**
     * @param  array<string, string|null>  $payload
     */
    private function resolveWalkInVisitor(array $payload): Visitor
    {
        $email = $this->normalizeText($payload['email'] ?? null);

        if ($email) {
            $existing = Visitor::query()->where('email', $email)->first();

            if ($existing instanceof Visitor) {
                return $existing;
            }
        }

        return Visitor::query()->create([
            'title' => $this->normalizeText($payload['title'] ?? null),
            'first_name' => $payload['first_name'],
            'name' => $payload['name'],
            'email' => $email,
            'phone' => $this->normalizeText($payload['phone'] ?? null),
            'company' => $this->normalizeText($payload['company'] ?? null),
            'notes' => null,
            'created_by_user_id' => null,
        ]);
    }

    private function walkInEnabled(): bool
    {
        return (bool) $this->kiosk->allow_walk_in && filled($this->kiosk->walk_in_host_user_id);
    }

    private function checkOutEnabled(): bool
    {
        return (bool) $this->kiosk->allow_check_out;
    }

    private function signatureRequired(): bool
    {
        return (bool) $this->kiosk->require_signature;
    }

    private function displayName(Visitor $visitor): string
    {
        $name = trim(implode(' ', array_filter([
            $visitor->title,
            $visitor->first_name,
            $visitor->name,
        ])));

        return $name !== '' ? $name : (string) __('Gast');
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function ensureKioskActive(): void
    {
        abort_unless($this->kiosk->is_active, 404);
    }

    private function finish(string $message, string $tone = 'success'): void
    {
        $this->statusMessage = $message;
        $this->statusTone = $tone;
        $this->screen = 'success';
        $this->search = '';
        $this->code = '';
        $this->signatureData = null;
        $this->signatureContext = null;
        $this->pendingCheckIn = [];
    }

    private function resetFlow(): void
    {
        $this->search = '';
        $this->code = '';
        $this->statusMessage = null;
        $this->statusTone = 'success';
        $this->signatureData = null;
        $this->signatureContext = null;
        $this->pendingCheckIn = [];
        $this->resetErrorBag();

        $this->walkIn = [
            'title' => '',
            'first_name' => '',
            'name' => '',
            'email' => '',
            'phone' => '',
            'company' => '',
        ];
    }

    private function touchLastSeen(): void
    {
        $threshold = max(10, (int) config('kiosk.touch_last_seen_seconds', 60));

        if ($this->kiosk->last_seen_at && $this->kiosk->last_seen_at->gt(now()->subSeconds($threshold))) {
            return;
        }

        $this->kiosk->forceFill(['last_seen_at' => now()])->save();
    }

    private function visitActionService(): VisitActionService
    {
        return app(VisitActionService::class);
    }
}
