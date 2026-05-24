<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Portal;

use App\Enums\VisitStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleVisitRequest;
use App\Http\Requests\StoreVisitRequest;
use App\Http\Requests\UpdateVisitRequest;
use App\Models\RecurringVisitSeries;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\RecurringVisitService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class VisitController extends Controller
{
    public function create(): View
    {
        $this->authorize('create', Visit::class);

        return $this->formView(new Visit([
            'site_id' => auth()->user()?->site_id,
            'host_user_id' => auth()->id(),
            'status' => VisitStatusEnum::Planned->value,
        ]), false);
    }

    public function store(StoreVisitRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $visit = app(RecurringVisitService::class)->createFromPayload($payload, $request->user());

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
            'host:id,first_name,name',
            'substituteUser:id,first_name,name',
            'recurringSeries',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
        ]);

        $participants = $visit->visitors
            ->map(function (Visitor $visitor) use ($visit) {
                $pivot = $visitor->pivot;

                return [
                    'id' => $visitor->id,
                    'name' => trim(($visitor->first_name ?? '').' '.($visitor->name ?? '')),
                    'title' => $visitor->title,
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
            'recurrenceMeta' => $this->recurrenceMeta($visit),
            'visitStatus' => $this->resolveVisitStatus($visit),
        ]);
    }

    public function edit(Visit $visit): View
    {
        $this->authorize('update', $visit);

        return $this->formView($visit, true);
    }

    public function update(UpdateVisitRequest $request, Visit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $payload = $request->validated();

        $visit = app(RecurringVisitService::class)->updateFromPayload($visit, $payload, $request->user());

        Log::channel('web')->info('Visit updated', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('portal.visits.show', $visit)
            ->with('status', __('Besuch wurde aktualisiert.'));
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->authorize('cancel', $visit);

        $visit->update([
            'status' => VisitStatusEnum::Canceled->value,
            'canceled_at' => now(),
            'canceled_by_user_id' => auth()->id(),
            'recurrence_is_modified' => $visit->recurring_visit_series_id ? true : $visit->recurrence_is_modified,
        ]);

        Log::channel('web')->info('Visit canceled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde abgesagt.'));
    }

    public function reopen(Visit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $visit->update([
            'status' => $visit->status === VisitStatusEnum::Canceled->value ? VisitStatusEnum::Planned->value : $visit->status,
            'canceled_at' => null,
            'canceled_by_user_id' => null,
            'recurrence_is_modified' => $visit->recurring_visit_series_id ? true : $visit->recurrence_is_modified,
        ]);

        Log::channel('web')->info('Visit reopened', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', __('Besuch wurde wieder geöffnet.'));
    }

    public function reschedule(RescheduleVisitRequest $request, Visit $visit): RedirectResponse
    {
        $this->authorize('update', $visit);

        $payload = $request->validated();

        $visit->update([
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'],
            'recurrence_is_modified' => $visit->recurring_visit_series_id ? true : $visit->recurrence_is_modified,
        ]);

        Log::channel('web')->info('Visit rescheduled', [
            'visit_id' => $visit->id,
            'user_id' => auth()->id(),
            'scheduled_from' => $payload['scheduled_from'],
            'scheduled_until' => $payload['scheduled_until'],
        ]);

        return back()->with('status', __('Termin wurde verschoben.'));
    }

    private function formView(Visit $visit, bool $isEdit): View
    {
        $visit->loadMissing([
            'host:id,first_name,name,title',
            'substituteUser:id,first_name,name,title',
            'recurringSeries',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
        ]);

        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        $siteOptions = $this->selectableSites($actor, $visit);
        $hostUsers = $this->selectableHostUsers($actor);
        $substituteUsers = $this->selectableSubstituteUsers($actor);

        $existingVisitors = Visitor::query()
            ->visibleTo($actor)
            ->select('id', 'title', 'first_name', 'name', 'company')
            ->orderBy('first_name')
            ->orderBy('name')
            ->limit(50)
            ->get();

        $initialParticipants = old('participants');

        if (! is_array($initialParticipants)) {
            $initialParticipants = $visit->visitors->map(function (Visitor $visitor) {
                return [
                    'visitor_id' => $visitor->id,
                    'title' => $visitor->title,
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
                    $participant['title'] = $participant['title'] ?? null;
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
            'hostUsers' => $hostUsers,
            'substituteUsers' => $substituteUsers,
            'siteOptions' => $siteOptions,
            'existingVisitors' => $existingVisitors,
            'initialParticipants' => $initialParticipants,
        ]);
    }

    private function selectableSites(User $actor, Visit $visit)
    {
        $currentSiteId = $visit->exists ? (int) $visit->site_id : null;

        if ($this->canSelectAnyHost($actor)) {
            return Site::query()
                ->select('id', 'name')
                ->where(function ($query) use ($currentSiteId): void {
                    $query->active()
                        ->when($currentSiteId, fn ($query) => $query->orWhere('sites.id', $currentSiteId));
                })
                ->orderBy('name')
                ->get();
        }

        $assignedSiteIds = $actor->assignedSiteIds();

        if ($assignedSiteIds->isEmpty()) {
            return collect();
        }

        return Site::query()
            ->select('id', 'name')
            ->where(function ($query) use ($assignedSiteIds, $currentSiteId): void {
                $query->active()
                    ->whereIn('id', $assignedSiteIds->all())
                    ->when($currentSiteId, fn ($query) => $query->orWhere('sites.id', $currentSiteId));
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) $actor->site_id])
            ->orderBy('name')
            ->get();
    }

    private function selectableHostUsers(User $actor)
    {
        $query = $this->selectableUsersQuery();

        if ($this->canSelectAnyHost($actor)) {
            return $query->get();
        }

        if ($actor->assignedSiteIds()->isNotEmpty() && ($actor->can('ViewSite:Visit') || $actor->can('EditSite:Visit') || $actor->can('DeleteSite:Visit') || $actor->can('CreateForSite:Visit'))) {
            return $this->whereCanAccessAnySite($query, $actor->assignedSiteIds()->all())->get();
        }

        if ($actor->department_id !== null && ($actor->can('ViewDepartment:Visit') || $actor->can('EditDepartment:Visit') || $actor->can('DeleteDepartment:Visit') || $actor->can('CreateForDepartment:Visit'))) {
            return $query->where('department_id', $actor->department_id)->get();
        }

        return $query->whereKey($actor->id)->get();
    }

    private function selectableSubstituteUsers(User $actor)
    {
        $query = $this->selectableUsersQuery();

        if ($this->canSelectAnyHost($actor)) {
            return $query->get();
        }

        if ($actor->assignedSiteIds()->isNotEmpty() && ($actor->can('ViewSite:Visit') || $actor->can('EditSite:Visit') || $actor->can('DeleteSite:Visit') || $actor->can('CreateForSite:Visit'))) {
            return $this->whereCanAccessAnySite($query, $actor->assignedSiteIds()->all())->get();
        }

        if ($actor->department_id !== null && ($actor->can('ViewDepartment:Visit') || $actor->can('EditDepartment:Visit') || $actor->can('DeleteDepartment:Visit') || $actor->can('CreateForDepartment:Visit'))) {
            return $query->where('department_id', $actor->department_id)->get();
        }

        return collect();
    }

    private function selectableUsersQuery()
    {
        return User::query()
            ->select('id', 'first_name', 'name', 'title', 'department_id', 'site_id')
            ->with('sites:id')
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['welcome monitor', 'welcome_monitor']))
            ->orderBy('first_name')
            ->orderBy('name');
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

    private function recurrenceMeta(Visit $visit): ?array
    {
        $series = $visit->recurringSeries;

        if (! $visit->recurring_visit_series_id || ! $series) {
            return null;
        }

        $isModified = (bool) $visit->recurrence_is_modified;

        return [
            'is_modified' => $isModified,
            'label' => $isModified ? __('Serientermin (manuell angepasst)') : __('Serientermin'),
            'badge_label' => __('Recurring'),
            'rule' => $this->formatRecurrenceRule($series),
            'progress' => $this->formatRecurrenceProgress($visit, $series),
            'modified_note' => __('Dieser Termin wurde manuell angepasst und weicht von der Serienregel ab.'),
        ];
    }

    private function formatRecurrenceRule(RecurringVisitSeries $series): string
    {
        $localizedStart = $series->starts_at?->copy()->locale(app()->getLocale());
        $weekday = $localizedStart?->translatedFormat('l') ?: '-';
        $yearlyDateFormat = app()->getLocale() === 'en' ? 'F j' : 'd. F';

        if (app()->getLocale() === 'de' && $weekday !== '-') {
            $weekday .= 's';
        }

        return match ($series->frequency) {
            RecurringVisitSeries::FREQUENCY_DAILY => __('Täglich'),
            RecurringVisitSeries::FREQUENCY_WEEKLY => __('Wöchentlich, :weekday', [
                'weekday' => $weekday,
            ]),
            RecurringVisitSeries::FREQUENCY_MONTHLY => __('Monatlich, am :day. Tag', [
                'day' => $series->starts_at?->format('j') ?: '-',
            ]),
            RecurringVisitSeries::FREQUENCY_YEARLY => __('Jährlich, am :date', [
                'date' => $localizedStart?->translatedFormat($yearlyDateFormat) ?: '-',
            ]),
            RecurringVisitSeries::FREQUENCY_EVERY_X_DAYS => $this->formatEveryXDaysRule($series),
            default => __('Wiederkehrender Termin'),
        };
    }

    private function formatEveryXDaysRule(RecurringVisitSeries $series): string
    {
        $intervalDays = max(1, (int) ($series->interval_days ?: 1));

        return $intervalDays === 1
            ? __('Täglich')
            : __('Alle :count Tage', ['count' => $intervalDays]);
    }

    private function formatRecurrenceProgress(Visit $visit, RecurringVisitSeries $series): ?string
    {
        if ($series->ends === RecurringVisitSeries::END_COUNT && $series->occurrence_count && $visit->recurrence_occurrence_number) {
            return __('Dies ist Termin :current von :total', [
                'current' => $visit->recurrence_occurrence_number,
                'total' => $series->occurrence_count,
            ]);
        }

        $plannedUntil = $series->ends === RecurringVisitSeries::END_DATE
            ? $series->end_date
            : $series->generated_until;

        if (! $plannedUntil) {
            return null;
        }

        return __('Geplant bis :date', ['date' => $plannedUntil->format('d.m.Y')]);
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
            return ['label' => __('Ausweis bereit'), 'class' => 'badge-info badge-outline'];
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
