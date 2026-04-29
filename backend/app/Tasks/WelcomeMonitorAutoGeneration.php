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

            $monitor->monitorSlides()->delete();

            Log::info('WelcomeMonitorAutoGeneration started', [
                'monitorID' => $monitor->id,
            ]);

            $windowMinutes = max(1, (int) ($monitor->auto_generation_window_minutes ?: Monitor::DEFAULT_AUTO_GENERATION_WINDOW_MINUTES));

            $currentVisits = Visit::query()
                ->whereBetween('scheduled_from', [
                    Carbon::now()->subMinutes($windowMinutes),
                    Carbon::now()->addMinutes($windowMinutes),
                ])
                ->where('status', VisitStatusEnum::Planned)
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
                        'slide_number' => $slidenumber++,
                    ]);
                }
            }
        }
    }
}
