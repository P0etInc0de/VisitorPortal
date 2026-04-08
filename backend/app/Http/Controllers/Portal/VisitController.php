<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleVisitRequest;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\Visit;
use App\Models\Visitor;
use App\Models\User;
use App\Support\SafeActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function create(): View
    {
        $visit = new Visit([
            'user_id' => auth()->id(),
            'status' => 'draft',
        ]);

        return $this->formView($visit, false);
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $visit = DB::transaction(function () use ($validated) {
            $visit = Visit::query()->create($this->visitPayload($validated));
            $this->syncParticipants($visit, $validated['participants'] ?? []);

            return $visit;
        });

        SafeActivityLog::info('Visit created', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('portal.visits.show', $visit)
            ->with('status', __('Besuch wurde angelegt.'));
    }

    public function show(Visit $visit): View
    {
        $visit->load([
            'host:id,name',
            'responsibleUser:id,name',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('last_name'),
        ]);

        $participants = $visit->visitors
            ->map(function (Visitor $visitor) use ($visit) {
                $pivot = $visitor->pivot;

                return [
                    'id' => $visitor->id,
                    'name' => trim(($visitor->first_name ?? '') . ' ' . ($visitor->last_name ?? '')),
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
        $visit->load([
            'host:id,name',
            'responsibleUser:id,name',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('last_name'),
        ]);

        return $this->formView($visit, true);
    }

    public function update(UpdateVisitRequest $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $visit) {
            $visit->update($this->visitPayload($validated));
            $this->syncParticipants($visit, $validated['participants'] ?? []);
        });

        SafeActivityLog::info('Visit updated', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('portal.visits.show', $visit)
            ->with('status', __('Besuch wurde aktualisiert.'));
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'canceled_by_user_id' => auth()->id(),
        ]);

        SafeActivityLog::info('Visit canceled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde abgesagt.'));
    }

    public function reopen(Visit $visit): RedirectResponse
    {
        $visit->update([
            'status' => $visit->status === 'canceled' ? 'planned' : $visit->status,
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);

        SafeActivityLog::info('Visit reopened', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde wieder geöffnet.'));
    }

    public function reschedule(RescheduleVisitRequest $request, Visit $visit): RedirectResponse
    {
        $validated = $request->validated();

        $visit->update([
            'scheduled_from' => $validated['scheduled_from'],
            'scheduled_until' => $validated['scheduled_until'] ?? null,
        ]);

        SafeActivityLog::info('Visit rescheduled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
            'scheduled_from' => $validated['scheduled_from'],
            'scheduled_until' => $validated['scheduled_until'] ?? null,
        ]);

        return back()->with('status', __('Termin wurde verschoben.'));
    }

    private function formView(Visit $visit, bool $isEdit): View
    {
        $users = User::query()->select(['id', 'name'])->orderBy('name')->get();
        $existingVisitors = Visitor::query()
            ->select(['id', 'first_name', 'last_name', 'company', 'email', 'phone'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $initialParticipants = old('participants');

        if (! is_array($initialParticipants)) {
            $initialParticipants = $visit->exists
                ? $visit->visitors->map(function (Visitor $visitor) {
                    return [
                        'visitor_id' => $visitor->id,
                        'first_name' => $visitor->first_name,
                        'last_name' => $visitor->last_name,
                        'email' => $visitor->email,
                        'phone' => $visitor->phone,
                        'company' => $visitor->company,
                    ];
                })->values()->all()
                : [[
                    'visitor_id' => null,
                    'first_name' => '',
                    'last_name' => '',
                    'email' => '',
                    'phone' => '',
                    'company' => '',
                ]];
        }

        if ($initialParticipants === []) {
            $initialParticipants[] = [
                'visitor_id' => null,
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'company' => '',
            ];
        }

        return view('portal.visits.create', [
            'visit' => $visit,
            'users' => $users,
            'existingVisitors' => $existingVisitors,
            'initialParticipants' => $initialParticipants,
            'isEdit' => $isEdit,
            'formAction' => $isEdit ? route('portal.visits.update', $visit) : route('portal.visits.store'),
            'formMethod' => $isEdit ? 'PATCH' : 'POST',
        ]);
    }

    private function visitPayload(array $validated): array
    {
        $hostId = (int) $validated['user_id'];
        $responsibleUserId = Arr::get($validated, 'responsible_user_id');

        return [
            'title' => $validated['title'],
            'user_id' => $hostId,
            'responsible_user_id' => $responsibleUserId ?: $hostId,
            'scheduled_from' => $validated['scheduled_from'],
            'scheduled_until' => $validated['scheduled_until'] ?? null,
            'status' => $validated['status'],
            'notes' => Arr::get($validated, 'notes'),
        ];
    }

    private function syncParticipants(Visit $visit, array $participantRows): void
    {
        $rows = $this->normalizedParticipants(collect($participantRows));

        if ($rows->isEmpty()) {
            $visit->visitors()->detach();
            return;
        }

        $syncPayload = [];

        foreach ($rows as $row) {
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
                    'last_name' => $this->normalizeText(Arr::get($row, 'last_name')),
                    'email' => $this->normalizeText(Arr::get($row, 'email')),
                    'phone' => $this->normalizeText(Arr::get($row, 'phone')),
                    'company' => $this->normalizeText(Arr::get($row, 'company')),
                    'notes' => $this->normalizeText(Arr::get($row, 'notes')),
                ];
            })
            ->filter(function (array $row) {
                return filled($row['visitor_id'])
                    || filled($row['first_name'])
                    || filled($row['last_name'])
                    || filled($row['email'])
                    || filled($row['phone'])
                    || filled($row['company']);
            })
            ->values();
    }

    private function resolveVisitor(array $row): Visitor
    {
        if (filled($row['visitor_id'])) {
            return Visitor::query()->findOrFail($row['visitor_id']);
        }

        $email = $row['email'];

        if (filled($email)) {
            $visitor = Visitor::query()->firstOrNew(['email' => $email]);
            $visitor->first_name = $row['first_name'] ?: $visitor->first_name;
            $visitor->last_name = $row['last_name'] ?: $visitor->last_name;
            $visitor->phone = $row['phone'] ?: $visitor->phone;
            $visitor->company = $row['company'] ?: $visitor->company;
            $visitor->status = $visitor->status ?: 'planned';
            $visitor->notes = $visitor->notes ?? null;
            $visitor->save();

            return $visitor;
        }

        $query = Visitor::query();

        if (filled($row['first_name'])) {
            $query->where('first_name', $row['first_name']);
        }

        if (filled($row['last_name'])) {
            $query->where('last_name', $row['last_name']);
        }

        if (filled($row['company'])) {
            $query->where('company', $row['company']);
        }

        $existingVisitor = $query->first();

        if ($existingVisitor) {
            if (blank($existingVisitor->phone) && filled($row['phone'])) {
                $existingVisitor->phone = $row['phone'];
                $existingVisitor->save();
            }

            return $existingVisitor;
        }

        return Visitor::query()->create([
            'first_name' => $row['first_name'] ?: '-',
            'last_name' => $row['last_name'] ?: '-',
            'email' => $row['email'],
            'phone' => $row['phone'],
            'company' => $row['company'],
            'status' => 'planned',
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
            return [
                'label' => __('Ausgecheckt'),
                'class' => 'badge-neutral badge-outline',
            ];
        }

        if (filled($pivot->checked_in_at)) {
            return [
                'label' => __('Eingecheckt'),
                'class' => 'badge-success badge-outline',
            ];
        }

        if (filled($pivot->badge_printed_at)) {
            return [
                'label' => __('Badge bereit'),
                'class' => 'badge-info badge-outline',
            ];
        }

        if ($visitStatus === 'draft') {
            return [
                'label' => __('Entwurf'),
                'class' => 'badge-error badge-outline',
            ];
        }

        if ($visitStatus === 'canceled') {
            return [
                'label' => __('Abgesagt'),
                'class' => 'badge-neutral badge-outline',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'badge-warning badge-outline',
        ];
    }

    private function resolveVisitStatus(Visit $visit): array
    {
        if ($visit->status === 'canceled' || filled($visit->canceled_at)) {
            return [
                'label' => __('Abgesagt'),
                'class' => 'badge-neutral badge-outline',
            ];
        }

        if ($visit->status === 'completed') {
            return [
                'label' => __('Abgeschlossen'),
                'class' => 'badge-success badge-outline',
            ];
        }

        if ($visit->status === 'draft') {
            return [
                'label' => __('Entwurf'),
                'class' => 'badge-error badge-outline',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'badge-warning badge-outline',
        ];
    }
}
