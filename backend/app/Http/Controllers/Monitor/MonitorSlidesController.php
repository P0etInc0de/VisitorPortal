<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreMonitorSlideRequest;
use App\Http\Requests\Monitor\UpdateMonitorSlideRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visit;
use Carbon\Carbon;

class MonitorSlidesController extends Controller
{
    private $monitor;

    public function __construct()
    {
        $this->monitor = Monitor::first();
    }

    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $defaultSlideNumber = MonitorSlide::query()
            ->where('monitor_id', $this->monitor->id)
            ->max('monitor_slides.slide_number') + 1;
        $todayVisits = Visit::query()
            ->whereDate('visits.scheduled_from', Carbon::today())->with('visitors')
            ->orderBy('visits.scheduled_from')
            ->with('responsibleUser')
            ->get();
        $todayVisitors = $todayVisits->pluck('visitors')->collapse()->unique('id');

        return view('monitorSlides.create')
            ->with('monitor', $this->monitor)
            ->with('defaultSlideNumber', $defaultSlideNumber)
            ->with('todayVisitors', $todayVisitors)
            ->with('todayVisits', $todayVisits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitorSlideRequest $request)
    {
        $validated = $request->validated();
        $validated['visitors'] = json_decode($request->visitors, true) ?? [];

        $this->monitor->monitorSlides()->create($validated);

        return redirect()->route('monitors.edit', $this->monitor)
            ->with('success', __('Neue Seite erstellt'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $slide = MonitorSlide::query()->findOrFail($id);
        $monitor = $this->monitor;

        $todayVisits = Visit::query()
            ->whereDate('visits.scheduled_from', '<=', Carbon::today())
            ->whereDate('visits.scheduled_until', '>=', Carbon::today())
            ->orderBy('visits.scheduled_from')
            ->with('responsibleUser')
            ->with('visitors')
            ->get();
        $todayVisitors = $todayVisits->pluck('visitors')->collapse()->unique('id');

        return view('monitorSlides.edit')
            ->with('slide', $slide)
            ->with('monitor', $monitor)
            ->with('todayVisitors', $todayVisitors)
            ->with('todayVisits', $todayVisits);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMonitorSlideRequest $request, string $id)
    {
        $validated = $request->validated();
        $validated['visitors'] = json_decode($request->visitors, true) ?? [];

        MonitorSlide::query()->findOrFail($id)->update($validated);

        return redirect()->route('monitors.edit', $this->monitor)
            ->with('success', __('Seite aktualisiert'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        MonitorSlide::query()->findOrFail($id)->delete();

        return redirect()->route('monitors.edit', $this->monitor)->with('success', __('Seite gelöscht'));
    }
}
