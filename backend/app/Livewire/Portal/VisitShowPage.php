<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Portal;

use App\Enums\VisitStatusEnum;
use App\Models\RecurringVisitSeries;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class VisitShowPage extends Component
{
    use AuthorizesRequests;

    public Visit $visit;

    public string $scheduledFrom = '';

    public string $scheduledUntil = '';

    public function mount(Visit $visit): void
    {
        $this->authorize('view', $visit);

        $this->visit = $visit;
        $this->refreshVisit();
        $this->fillScheduleFields();
    }

    public function saveSchedule(): void
    {
        $this->authorize('update', $this->visit->fresh());

        $payload = $this->validate([
            'scheduledFrom' => ['required', 'date'],
            'scheduledUntil' => ['required', 'date', 'after:scheduledFrom'],
        ]);

        $this->visit->update([
            'scheduled_from' => $payload['scheduledFrom'],
            'scheduled_until' => $payload['scheduledUntil'],
            'recurrence_is_modified' => $this->visit->recurring_visit_series_id ? true : $this->visit->recurrence_is_modified,
        ]);

        $this->refreshVisit();
        $this->fillScheduleFields();

        session()->flash('status', __('Termin wurde verschoben.'));
    }

    public function cancelVisit(): void
    {
        $this->authorize('cancel', $this->visit->fresh());

        $this->visit->update([
            'status' => VisitStatusEnum::Canceled->value,
            'canceled_at' => now(),
            'canceled_by_user_id' => auth()->id(),
            'recurrence_is_modified' => $this->visit->recurring_visit_series_id ? true : $this->visit->recurrence_is_modified,
        ]);

        $this->refreshVisit();

        session()->flash('status', __('Besuch wurde abgesagt.'));
    }

    public function reopenVisit(): void
    {
        $this->authorize('update', $this->visit->fresh());

        $this->visit->update([
            'status' => VisitStatusEnum::Planned->value,
            'canceled_at' => null,
            'canceled_by_user_id' => null,
            'recurrence_is_modified' => $this->visit->recurring_visit_series_id ? true : $this->visit->recurrence_is_modified,
        ]);

        $this->refreshVisit();

        session()->flash('status', __('Besuch wurde wieder geöffnet.'));
    }

    public function render(): View
    {
        return view('livewire.portal.visit-show-page', [
            'participants' => $this->participantItems(),
            'recurrenceMeta' => $this->recurrenceMeta(),
            'visitStatus' => $this->resolveVisitStatus(),
            'visitMeta' => $this->visitMeta(),
        ])->layout('layouts.app', [
            'header' => new HtmlString(
                view('partials.page-header', [
                    'title' => $this->visit->display_title,
                ])->render()
            ),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function participantItems(): array
    {
        return $this->visit->visitors
            ->map(function (Visitor $visitor): array {
                $pivot = $visitor->pivot;

                return [
                    'id' => $visitor->id,
                    'display_name' => trim(implode(' ', array_filter([
                        $visitor->title,
                        $visitor->first_name,
                        $visitor->name,
                    ]))) ?: __('Ohne Namen'),
                    'company' => $visitor->company,
                    'email' => $visitor->email,
                    'phone' => $visitor->phone,
                    'status' => $this->resolveParticipantStatus((string) $this->visit->status, $pivot),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string|int>
     */
    private function visitMeta(): array
    {
        return [
            'date' => $this->visit->scheduled_from?->format('d.m.Y') ?? '–',
            'timeRange' => $this->visit->scheduled_from
                ? $this->visit->scheduled_from->format('H:i').($this->visit->scheduled_until ? ' bis '.$this->visit->scheduled_until->format('H:i') : '')
                : '–',
            'host' => $this->displayUserName($this->visit->host),
            'substitute' => $this->displayUserName($this->visit->substituteUser),
            'participants' => $this->visit->participant_count,
        ];
    }

    private function recurrenceMeta(): ?array
    {
        $series = $this->visit->recurringSeries;

        if (! $this->visit->recurring_visit_series_id || ! $series) {
            return null;
        }

        $isModified = (bool) $this->visit->recurrence_is_modified;

        return [
            'is_modified' => $isModified,
            'label' => $isModified ? __('Serientermin (manuell angepasst)') : __('Serientermin'),
            'badge_label' => __('Recurring'),
            'rule' => $this->formatRecurrenceRule($series),
            'progress' => $this->formatRecurrenceProgress($series),
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

    private function formatRecurrenceProgress(RecurringVisitSeries $series): ?string
    {
        if ($series->ends === RecurringVisitSeries::END_COUNT && $series->occurrence_count && $this->visit->recurrence_occurrence_number) {
            return __('Dies ist Termin :current von :total', [
                'current' => $this->visit->recurrence_occurrence_number,
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

    /**
     * @return array{label: string, class: string}
     */
    private function resolveVisitStatus(): array
    {
        if ($this->visit->status === VisitStatusEnum::Canceled->value || filled($this->visit->canceled_at)) {
            return ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        if ($this->visit->status === VisitStatusEnum::Completed->value) {
            return ['label' => __('Abgeschlossen'), 'class' => 'badge-success badge-outline'];
        }

        if ($this->visit->status === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }

    /**
     * @return array{label: string, class: string}
     */
    private function resolveParticipantStatus(string $visitStatus, object $pivot): array
    {
        if (filled($pivot->checked_out_at)) {
            return ['label' => __('Ausgecheckt'), 'class' => 'badge-neutral badge-outline'];
        }

        if (filled($pivot->checked_in_at)) {
            return ['label' => __('Eingecheckt'), 'class' => 'badge-success badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return ['label' => __('Entwurf'), 'class' => 'badge-error badge-outline'];
        }

        if ($visitStatus === VisitStatusEnum::Canceled->value) {
            return ['label' => __('Abgesagt'), 'class' => 'badge-neutral badge-outline'];
        }

        return ['label' => __('Geplant'), 'class' => 'badge-warning badge-outline'];
    }

    private function refreshVisit(): void
    {
        $this->visit->load([
            'host:id,first_name,name',
            'substituteUser:id,first_name,name',
            'recurringSeries',
            'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
        ]);
    }

    private function fillScheduleFields(): void
    {
        $this->scheduledFrom = optional($this->visit->scheduled_from)->format('Y-m-d\TH:i') ?? '';
        $this->scheduledUntil = optional($this->visit->scheduled_until)->format('Y-m-d\TH:i') ?? '';
    }

    private function displayUserName(?User $user): string
    {
        if (! $user) {
            return '–';
        }

        $name = trim(($user->first_name ?? '').' '.($user->name ?? ''));

        return $name !== '' ? $name : '–';
    }
}
