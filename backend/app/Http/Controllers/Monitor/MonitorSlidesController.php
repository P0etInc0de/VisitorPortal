<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreMonitorSlideRequest;
use App\Http\Requests\Monitor\UpdateMonitorSlideRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MonitorSlidesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     */
    public function create(Monitor $monitor)
    {
        $this->authorize('create', MonitorSlide::class);
        $this->authorize('update', $monitor);

        $currentMax = MonitorSlide::query()
            ->where('monitor_id', $monitor->id)
            ->max('monitor_slides.slide_number') ?? 0; // Default to 0 if null

        $defaultSlideNumber = $currentMax + 1;
        $todayVisits = Visit::query()
            ->whereDate('visits.scheduled_from', Carbon::today())->with('visitors')
            ->orderBy('visits.scheduled_from')
            ->with('responsibleUser')
            ->get();
        $todayVisitors = $todayVisits->pluck('visitors')->collapse()->unique('id');

        return view('monitorSlides.create')
            ->with('monitor', $monitor)
            ->with('defaultSlideNumber', $defaultSlideNumber)
            ->with('todayVisitors', $todayVisitors)
            ->with('todayVisits', $todayVisits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMonitorSlideRequest $request, Monitor $monitor)
    {
        $validated = $request->validated();
        $validated['visitors'] = json_decode($request->visitors, true) ?? [];

        $monitor_slide = $monitor->monitorSlides()->create($validated);

        Log::info('User ID '.auth()->id()." created Monitor-Slide with ID: {$monitor_slide->id}");

        return redirect()->route('monitors.edit', $monitor)
            ->with('success', __('Neue Seite erstellt'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) {}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Monitor $monitor, MonitorSlide $slide)
    {
        $this->authorize('update', $slide);
        $this->authorize('update', $monitor);

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
    public function update(UpdateMonitorSlideRequest $request, Monitor $monitor, MonitorSlide $slide)
    {
        $validated = $request->validated();
        $validated['visitors'] = json_decode($validated['visitors'] ?? '[]', true);

        $slide->update($validated);

        Log::info('User ID '.auth()->id()." updated Monitor-Slide with ID: {$slide->id}");

        return redirect()->route('monitors.edit', $monitor)
            ->with('success', __('Seite aktualisiert'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Monitor $monitor, MonitorSlide $slide)
    {
        $this->authorize('delete', $slide);

        $slide->delete();

        Log::info('User ID '.auth()->id()." deleted Monitor-Slide with ID: {$slide->id}");

        return redirect()->route('monitors.edit', $monitor)->with('success', __('Seite gelöscht'));
    }
}
