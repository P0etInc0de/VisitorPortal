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
                    'name' => $this->visitorFullName($visitor),
                    'company' => $this->visitorCompany($visitor),
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

    private function visitorFullName(object $visitor): string
    {
        $fullName = trim(implode(' ', array_filter([
            $visitor->first_name ?? null,
            $visitor->name ?? null,
        ])));

        return $fullName !== '' ? $fullName : '–';
    }

    private function visitorCompany(object $visitor): string
    {
        $company = trim((string) ($visitor->company ?? ''));

        if ($company !== '') {
            return $company;
        }

        $email = trim((string) ($visitor->email ?? ''));

        return $email !== '' ? $email : '–';
    }
}
