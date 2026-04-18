<?php

namespace App\Http\Controllers\Portal;

use App\Enums\VisitorStatusEnum;
use App\Enums\VisitStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleVisitRequest;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitController extends Controller
{
    public function create(): View
    {
		$this->authorize('create', Visit::class);		

        return $this->formView(new Visit([
            'user_id' => auth()->id(),
            'status' => VisitStatusEnum::Draft->value,
        ]), false);
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $visit = DB::transaction(function () use ($payload) {
            $visit = Visit::query()->create([
                'title' => $payload['title'],
                'user_id' => (int) $payload['user_id'],
                'responsible_user_id' => (int) (($payload['responsible_user_id'] ?? null) ?: $payload['user_id']),
                'scheduled_from' => $payload['scheduled_from'],
                'scheduled_until' => $payload['scheduled_until'] ?? null,
                'status' => $payload['status'],
                'notes' => $payload['notes'] ?? null,
            ]);
			
            $this->syncParticipants($visit, $payload['participants'] ?? []);

            return $visit;
        });

        // Technical log for the current implementation. Visit lifecycle changes may later
        // also be written to a dedicated activity log.
        Log::channel('web')->info('Visit created', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('portal.visits.show', $visit)
            ->with('status', __('Besuch wurde angelegt.'));
    }

    public function show(Visit $visit): View
    {
		$this->authorize('view', $visit);

        $visit->load([
            'host:id,name',
            'responsibleUser:id,name',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
        ]);

        $participants = $visit->visitors
            ->map(function (Visitor $visitor) use ($visit) {
                $pivot = $visitor->pivot;

                return [
                    'id' => $visitor->id,
                    'name' => trim(($visitor->first_name ?? '').' '.($visitor->name ?? '')),
                    'company' => $visitor->company,
                    'email' => $visitor->email,
                    'phone' => $visitor->phone,
                    'badge_printed_at' => $pivot->badge_printed_at,
                    'checked_in_at' => $pivot->checked_in_at,
                    'checked_out_at' => $pivot->checked_out_at,
                    'status' => $this->resolveParticipantStatus((string) $visit->status, $pivot),
                ];
            })
            ->values();

        return view('portal.visits.show', [
            'visit' => $visit,
            'participants' => $participants,
            'visitStatus' => $this->resolveVisitStatus($visit),
        ]);
    }

    public function edit(Visit $visit): View
    {
        return $this->formView($visit, true);
    }

    public function update(UpdateVisitRequest $request, Visit $visit): RedirectResponse
    {
        $payload = $request->validated();

        DB::transaction(function () use ($visit, $payload) {
            $visit->update([
                'title' => $payload['title'],
                'user_id' => (int) $payload['user_id'],
                'responsible_user_id' => (int) (($payload['responsible_user_id'] ?? null) ?: $payload['user_id']),
                'scheduled_from' => $payload['scheduled_from'],
                'scheduled_until' => $payload['scheduled_until'] ?? null,
                'status' => $payload['status'],
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->syncParticipants($visit, $payload['participants'] ?? []);
        });

        Log::channel('web')->info('Visit updated', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('portal.visits.show', $visit)
            ->with('status', __('Besuch wurde aktualisiert.'));
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => VisitStatusEnum::Canceled->value,
            'canceled_at' => now(),
            'canceled_by_user_id' => auth()->id(),
        ]);

        Log::channel('web')->info('Visit canceled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde abgesagt.'));
    }

    public function reopen(Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => $visit->status === VisitStatusEnum::Canceled->value ? VisitStatusEnum::Planned->value : $visit->status,
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);

        Log::channel('web')->info('Visit reopened', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde wieder geöffnet.'));
    }

    public function reschedule(RescheduleVisitRequest $request, Visit $visit): RedirectResponse
    {
        $payload = $request->validated();

        $visit->update([
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'] ?? null,
        ]);

        Log::channel('web')->info('Visit rescheduled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'] ?? null,
        ]);

        return back()->with('status', __('Termin wurde verschoben.'));
    }

    private function formView(Visit $visit, bool $isEdit): View
    {
        $visit->loadMissing([
            'host:id,name',
            'responsibleUser:id,name',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
        ]);

        $users = User::query()->select('id', 'name')->orderBy('name')->get();
        $existingVisitors = Visitor::query()
            ->select('id', 'first_name', 'name', 'company', 'email', 'phone')
            ->orderBy('first_name')
            ->orderBy('name')
            ->get();

        $initialParticipants = old('participants');

        if (! is_array($initialParticipants)) {
            $initialParticipants = $visit->visitors->map(function (Visitor $visitor) {
                return [
                    'visitor_id' => $visitor->id,
                    'first_name' => $visitor->first_name,
                    'name' => $visitor->name,
                    'email' => $visitor->email,
                    'phone' => $visitor->phone,
                    'company' => $visitor->company,
                    'is_existing' => true,
                    'is_open' => false,
                ];
            })->values()->all();
        } else {
            $initialParticipants = collect($initialParticipants)
                ->map(function (array $participant) {
                    $participant['is_existing'] = filled($participant['visitor_id'] ?? null);
                    $participant['is_open'] = blank($participant['visitor_id'] ?? null);

                    return $participant;
                })
                ->values()
                ->all();
        }

        if ($initialParticipants === []) {
            $initialParticipants = [];
        }

        return view('portal.visits.create', [
            'visit' => $visit,
            'isEdit' => $isEdit,
            'formAction' => $isEdit ? route('portal.visits.update', $visit) : route('portal.visits.store'),
            'formMethod' => $isEdit ? 'PATCH' : 'POST',
            'users' => $users,
            'existingVisitors' => $existingVisitors,
            'initialParticipants' => $initialParticipants,
        ]);
    }

    private function syncParticipants(Visit $visit, array $participantRows): void
    {
        $syncPayload = [];

        foreach ($this->normalizedParticipants(collect($participantRows)) as $row) {
            $visitor = $this->resolveVisitor($row);
            $syncPayload[$visitor->id] = [
                'notes' => Arr::get($row, 'notes'),
            ];
        }

        $visit->visitors()->sync($syncPayload);
    }

    private function normalizedParticipants(Collection $participantRows): Collection
    {
        return $participantRows
            ->map(function ($row) {
                return [
                    'visitor_id' => Arr::get($row, 'visitor_id') ?: null,
                    'first_name' => $this->normalizeText(Arr::get($row, 'first_name')),
                    'name' => $this->normalizeText(Arr::get($row, 'name')),
                    'email' => $this->normalizeText(Arr::get($row, 'email')),
                    'phone' => $this->normalizeText(Arr::get($row, 'phone')),
                    'company' => $this->normalizeText(Arr::get($row, 'company')),
                    'notes' => $this->normalizeText(Arr::get($row, 'notes')),
                ];
            })
            ->filter(function (array $row) {
                return filled($row['visitor_id'])
                    || filled($row['first_name'])
                    || filled($row['name'])
                    || filled($row['email'])
                    || filled($row['phone'])
                    || filled($row['company']);
            })
            ->filter(function (array $row) {
                if (filled($row['visitor_id'])) {
                    return true;
                }

                return filled($row['first_name']) && filled($row['name']);
            })
            ->values();
    }

    private function resolveVisitor(array $row): Visitor
    {
        if (filled($row['visitor_id'])) {
            return Visitor::query()->findOrFail($row['visitor_id']);
        }

        if (filled($row['email'])) {
            $existingByEmail = Visitor::query()->where('email', $row['email'])->first();

            if ($existingByEmail) {
                $existingByEmail->update([
                    'first_name' => $row['first_name'] ?: $existingByEmail->first_name,
                    'name' => $row['name'] ?: $existingByEmail->name,
                    'phone' => $row['phone'] ?: $existingByEmail->phone,
                    'company' => $row['company'] ?: $existingByEmail->company,
                ]);

                return $existingByEmail;
            }
        }

        return Visitor::query()->create([
            'first_name' => $row['first_name'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'company' => $row['company'],
            'status' => VisitorStatusEnum::Planned,
            'notes' => null,
        ]);
    }

    private function normalizeText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveParticipantStatus(string $visitStatus, object $pivot): array
    {
        if (filled($pivot->checked_out_at)) {
            return ['label' => __('Ausgecheckt'), 'class' => 'badge-neutral badge-outline'];
        }

        if (filled($pivot->checked_in_at)) {
            return ['label' => __('Eingecheckt'), 'class' => 'badge-success badge-outline'];
        }

        if (filled($pivot->badge_printed_at)) {
            return ['label' => __('Badge bereit'), 'class' => 'badge-info badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Canceled->value) {
            return ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }

    private function resolveVisitStatus(Visit $visit): array
    {
        if ($visit->status === VisitStatusEnum::Canceled->value || filled($visit->canceled_at)) {
            return ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        if ($visit->status === VisitStatusEnum::Completed->value) {
            return ['label' => __('Abgeschlossen'), 'class' => 'badge-success badge-outline'];
        }

        if ($visit->status === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }
}
