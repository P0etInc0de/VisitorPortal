<?php

namespace App\Tasks;

use App\Enums\VisitStatusEnum;
use App\Models\Monitor;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class WelcomeMonitorAutoGeneration implements ShouldQueue
{
    /**
     * If a Monitor has `auto_generation` enabled it will generate slides for the visits automatically
     * Shall run every 5 min
     *
     * */
    public function __invoke(?Monitor $onlyMonitor = null): void
    {
        $monitors = $onlyMonitor ? collect([$onlyMonitor]) : Monitor::all();

        foreach ($monitors as $monitor) {

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
                ->where('monitor_display_mode', '!=', Visit::MONITOR_DISPLAY_HIDDEN)
                ->where('scheduled_from', '<=', $windowEnd)
                ->where('scheduled_until', '>=', $windowStart)
                ->where('status', VisitStatusEnum::Planned->value)
                ->orderBy('scheduled_from')
                ->with('visitors')
                ->get();

            $slidenumber = 1;
            foreach ($currentVisits as $currentVisit) {

                $visitors = $currentVisit->visitors->values();

                $chunks = $visitors->chunk(6);

                foreach ($chunks as $chunk) {

                    $slide = $monitor->monitorSlides()->create([
                        'heading' => config('branding.monitor_slide_heading', 'Welcome!'),
                        'slide_number' => $slidenumber++,
                        'is_active' => true,
                        'is_auto_generated' => true,
                        'show_logo' => true,
                        'show_date' => true,
                    ]);

                    $payload = $chunk->map(function ($visitor) use ($currentVisit) {
                        $name = $this->monitorDisplayName($currentVisit, $visitor);

                        return [
                            'id' => $visitor->id,
                            'name' => $name,
                        ];
                    })->values()->toArray();

                    $slide->update([
                        'visitors' => $payload,
                    ]);
                }
            }
        }
    }

    private function monitorDisplayName(Visit $visit, mixed $visitor): string
    {
        $company = trim((string) $visitor->company);
        $firstName = trim((string) $visitor->first_name);
        $lastName = trim((string) $visitor->name);
        $fullName = trim(implode(' ', array_filter([$visitor->title, $firstName, $lastName])));
        $initials = trim(implode(' ', array_filter([
            $firstName !== '' ? mb_substr($firstName, 0, 1).'.' : null,
            $lastName !== '' ? mb_substr($lastName, 0, 1).'.' : null,
        ])));

        return match ($visit->monitor_display_mode) {
            Visit::MONITOR_DISPLAY_FULL_NAME => $fullName !== '' ? $fullName : ($company ?: __('Gast')),
            Visit::MONITOR_DISPLAY_FIRST_NAME_COMPANY => trim(implode(' - ', array_filter([$firstName, $company]))) ?: __('Gast'),
            Visit::MONITOR_DISPLAY_INITIALS_COMPANY => trim(implode(' - ', array_filter([$initials, $company]))) ?: __('Gast'),
            default => $company !== '' ? $company : __('Gast'),
        };
    }
}
