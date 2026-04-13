<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Contracts\View\View;

class MonitorController extends Controller
{
    public function show(): View
    {
        $todayVisits = Visit::query()
            ->with(['visitors'])
            ->whereBetween('scheduled_from', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_from')
            ->get();

        $visitors = $todayVisits
            ->flatMap(fn (Visit $visit) => $visit->visitors->map(function ($visitor) use ($visit) {
                return [
                    'time' => $visit->scheduled_from->format('H:i'),
                    'name' => $visitor->full_name,
                    'company' => $visitor->display_company,
                    'title' => $visit->display_title,
                ];
            }))
            ->take(4)
            ->values();

        return view('monitor.show', [
            'monitorVisitors' => $visitors,
        ]);
    }

    public function edit(): View
    {
        return view('monitor.editor');
    }
}
