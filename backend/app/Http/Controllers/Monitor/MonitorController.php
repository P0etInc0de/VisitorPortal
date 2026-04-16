<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\UpdateMonitorRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use Illuminate\Support\Facades\Storage;
use Request;

class MonitorController extends Controller
{
    /**
     * Display a listing of monitors.
     * Not needed yet
     */
    public function index() {}

    /**
     * Show the form for creating a new resource.
     * Not needed yet
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     * Not needed yet
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     * (Show the Welcome Monitor)
     */
    public function show(string $id)
    {
        $monitor = Monitor::query()->findOrFail($id);

        return view('monitor.show')
            ->with('monitor', $monitor);
    }

    /**
     * Show the form for editing the specified resource.
     * Edit Monitor and See Monitor_Slide resource
     */
    public function edit(string $id)
    {
        $monitor = Monitor::query()->findOrFail($id);
        $monitor_slides = MonitorSlide::query()->where('monitor_id', $monitor->id)->orderBy('monitor_slides.slide_number')->get();

        $response = response()->view('monitor.edit', ['monitor' => $monitor, 'monitor_slides' => $monitor_slides]);

        if (session()->has('success')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return $response;
    }

    /**
     * Update the specified resource in storage.
     * Not needed yet
     */
    public function update(UpdateMonitorRequest $request, string $id)
    {
        $validated = $request->validated();

        // get current monitor
        $monitor = Monitor::query()->findOrFail($id);
        $current_image_path = $monitor->image_path;

        // Check if image shall be deleted
        if (! empty($validated['deleteImage'])) {
            Storage::disk('public')->delete($current_image_path);
            $monitor->image_path = null;
        } elseif (! empty($request->file('image'))) {
            // Store the image inside storage/app/private/images
            $path = $request->file('image')->store('images', 'public');

            // Add the image path
            $validated['image_path'] = $path;

            // Delete old image

            if ($current_image_path != null) {
                Storage::disk('public')->delete($current_image_path);
            }
        }
        // Update monitor
        $monitor->update($validated);

        return redirect()->route('monitors.edit', $id)
            ->with('success', __('Monitor Einstellungen gespeichert'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Remove the specified resource from storage.
     * not needed yet
     */
    public function destroy(string $id)
    {
        //
    }
}
