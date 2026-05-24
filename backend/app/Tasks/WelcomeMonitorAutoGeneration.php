<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Tasks;

use App\Enums\VisitStatusEnum;
use App\Models\Monitor;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WelcomeMonitorAutoGeneration implements ShouldQueue
{
    /**
     * If a Monitor has `auto_generation` enabled it will generate slides for the visits automatically
     * Shall run every minute
     *
     * */
    public function __invoke(?Monitor $onlyMonitor = null): void
    {
        $monitors = $onlyMonitor ? collect([$onlyMonitor]) : Monitor::all();

        foreach ($monitors as $monitor) {
            $monitor->loadMissing('site');

            if (! $monitor->site?->is_active) {
                continue;
            }

            if (! $monitor->auto_generation) {
                continue;
            }

            $monitor->monitorSlides()
                ->where('is_auto_generated', true)
                ->delete();

            Log::info('WelcomeMonitorAutoGeneration started', [
                'monitorID' => $monitor->id,
            ]);

            $windowMinutes = max(1, (int) ($monitor->auto_generation_window_minutes ?: Monitor::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES));
            $windowStart = Carbon::now()->subMinutes($windowMinutes);
            $windowEnd = Carbon::now()->addMinutes($windowMinutes);

            $currentVisits = Visit::query()
                ->where('site_id', $monitor->site_id)
                ->where('is_confidential', false)
                ->where('scheduled_from', '<=', $windowEnd)
                ->where('scheduled_until', '>=', $windowStart)
                ->where('status', VisitStatusEnum::Planned->value)
                ->orderBy('scheduled_from')
                ->with('visitors')
                ->get();

            $slidenumber = 1;
            foreach ($currentVisits as $currentVisit) {

                $visitors = $this->displayVisitors($currentVisit, $monitor->monitor_display_mode ?: Monitor::DEFAULT_MONITOR_DISPLAY_MODE);

                $chunks = $visitors->chunk(6);

                foreach ($chunks as $chunk) {

                    $slide = $monitor->monitorSlides()->create([
                        'heading' => config('branding.monitor_slide_heading', 'Welcome!'),
                        'slide_number' => $slidenumber++,
                        'is_active' => true,
                        'is_auto_generated' => true,
                        'monitor_display_mode' => $monitor->monitor_display_mode ?: Monitor::DEFAULT_MONITOR_DISPLAY_MODE,
                        'show_logo' => true,
                        'show_date' => true,
                    ]);

                    $payload = $chunk->values()->toArray();

                    $slide->update([
                        'visitors' => $payload,
                    ]);
                }
            }
        }
    }

    private function displayVisitors(Visit $visit, string $displayMode): Collection
    {
        $items = $visit->visitors
            ->map(fn (mixed $visitor): array => [
                'id' => $visitor->id,
                'name' => $this->monitorDisplayName($displayMode, $visitor),
                'company_key' => $this->companyKey($visitor),
            ]);

        if ($displayMode === Monitor::DISPLAY_COMPANY_ONLY) {
            $items = $items->unique('company_key');
        }

        return $items
            ->map(fn (array $visitor): array => [
                'id' => $displayMode === Monitor::DISPLAY_COMPANY_ONLY ? null : $visitor['id'],
                'name' => $visitor['name'],
            ])
            ->values();
    }

    private function monitorDisplayName(string $displayMode, mixed $visitor): string
    {
        $company = trim((string) $visitor->company);
        $title = trim((string) $visitor->title);
        $firstName = trim((string) $visitor->first_name);
        $lastName = trim((string) $visitor->name);
        $firstInitial = $firstName !== '' ? mb_substr($firstName, 0, 1).'.' : null;
        $lastInitial = $lastName !== '' ? mb_substr($lastName, 0, 1).'.' : null;

        $name = match ($displayMode) {
            Monitor::DISPLAY_TITLE_FULL_NAME => trim(implode(' ', array_filter([$title, $firstName, $lastName]))),
            Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME => trim(implode(' ', array_filter([$title, $firstInitial, $lastName]))),
            Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL => trim(implode(' ', array_filter([$title, $firstName, $lastInitial]))),
            default => $company,
        };

        return $name !== '' ? $name : __('Gast');
    }

    private function companyKey(mixed $visitor): string
    {
        $company = trim((string) $visitor->company);

        return mb_strtolower($company !== '' ? $company : __('Gast'));
    }
}
