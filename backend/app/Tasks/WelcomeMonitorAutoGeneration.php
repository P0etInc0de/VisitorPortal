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
    public function __invoke(): void
    {
        foreach (Monitor::all() as $monitor) {

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
                        'heading' => __('Herzlich willkommen!'),
                        'slide_number' => $slidenumber++,
                        'is_active' => true,
                        'is_auto_generated' => true,
                        'show_logo' => true,
                        'show_date' => true,
                    ]);

                    $payload = $chunk->map(function ($visitor) {
                        $name = trim(implode(' ', array_filter([
                            $visitor->title,
                            $visitor->first_name,
                            $visitor->name,
                        ])));

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
}
