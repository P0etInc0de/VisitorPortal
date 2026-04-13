<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorSlideRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
    public function index()
    {
        //
    }

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
            ->with('todayVisits', $todayVisits)
            ->with('availableTags', $todayVisitors);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitorSlideRequest $request)
    {
        $validated = $request->validated();

        $validated['visitors'] = 'test';

        $this->monitor->monitorSlides()->create($validated);

        return redirect()->route('monitors.edit', $this->monitor);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
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
