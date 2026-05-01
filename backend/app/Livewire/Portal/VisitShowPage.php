<?php

namespace App\Livewire\Portal;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class VisitShowPage extends Component
{
    public Visit $visit;

    public string $scheduledFrom = '';

    public string $scheduledUntil = '';

    public function mount(Visit $visit): void
    {
        $this->visit = $visit;
        $this->refreshVisit();
        $this->fillScheduleFields();
    }

    public function saveSchedule(): void
    {
        $payload = $this->validate([
            'scheduledFrom' => ['required', 'date'],
            'scheduledUntil' => ['nullable', 'date', 'after:scheduledFrom'],
        ]);

        $this->visit->update([
            'scheduled_from' => $payload['scheduledFrom'],
            'scheduled_until' => $payload['scheduledUntil'] !== '' ? $payload['scheduledUntil'] : null,
        ]);

        $this->refreshVisit();
        $this->fillScheduleFields();

        session()->flash('status', __('Termin wurde verschoben.'));
    }

    public function cancelVisit(): void
    {
        $this->visit->update([
            'status' => VisitStatusEnum::Canceled->value,
            'canceled_at' => now(),
            'canceled_by_user_id' => auth()->id(),
        ]);

        $this->refreshVisit();

        session()->flash('status', __('Besuch wurde abgesagt.'));
    }

    public function reopenVisit(): void
    {
        $this->visit->update([
            'status' => VisitStatusEnum::Planned->value,
            'canceled_at' => null,
            'canceled_by_user_id' => null,
        ]);

        $this->refreshVisit();

        session()->flash('status', __('Besuch wurde wieder geöffnet.'));
    }

    public function render(): View
    {
        return view('livewire.portal.visit-show-page', [
            'participants' => $this->participantItems(),
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
            'timeframe' => $this->visit->scheduled_from
                ? $this->visit->scheduled_from->format('d.m.Y H:i').($this->visit->scheduled_until ? ' - '.$this->visit->scheduled_until->format('H:i') : '')
                : '–',
            'host' => $this->displayUserName($this->visit->host),
            'reception' => $this->displayUserName($this->visit->responsibleUser),
            'participants' => $this->visit->participant_count,
        ];
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
            'responsibleUser:id,first_name,name',
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
