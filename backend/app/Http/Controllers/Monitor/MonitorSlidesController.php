<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreMonitorSlideRequest;
use App\Http\Requests\Monitor\UpdateMonitorSlideRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
            ->whereDate('visits.scheduled_from', '<=', Carbon::today())
            ->whereDate('visits.scheduled_until', '>=', Carbon::today())
            ->with('visitors')
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
        $validated['visitors'] = $this->normalizeVisitors($request->input('visitors'));
        $validated = $this->prepareBackgroundData($request, $validated);

        $monitor_slide = $monitor->monitorSlides()->create($validated);

        Log::info('User ID '.auth()->id()." created Monitor-Slide with ID: {$monitor_slide->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')
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
        $validated['visitors'] = $this->normalizeVisitors($validated['visitors'] ?? null);
        $validated = $this->prepareBackgroundData($request, $validated, $slide);

        $slide->update($validated);

        Log::info('User ID '.auth()->id()." updated Monitor-Slide with ID: {$slide->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')
            ->with('success', __('Seite aktualisiert'));

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Monitor $monitor, MonitorSlide $slide)
    {
        $this->authorize('delete', $slide);

        if (filled($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
        }

        $slide->delete();

        Log::info('User ID '.auth()->id()." deleted Monitor-Slide with ID: {$slide->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')->with('success', __('Seite gelöscht'));
    }

    private function normalizeVisitors(?string $encodedVisitors): array
    {
        $decoded = json_decode($encodedVisitors ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($decoded as $visitor) {
            if (! is_array($visitor)) {
                continue;
            }

            $name = trim((string) ($visitor['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $numericId = is_numeric($visitor['id'] ?? null) ? (int) $visitor['id'] : null;
            $key = $numericId !== null ? 'visit:'.$numericId : 'manual:'.mb_strtolower($name);

            if (isset($seen[$key])) {
                continue;
            }

            $normalized[] = [
                'id' => $numericId,
                'name' => $name,
            ];
            $seen[$key] = true;
        }

        if (count($normalized) > 6) {
            throw ValidationException::withMessages([
                'visitors' => __('Maximal 6 Besucher je Seite'),
            ]);
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function prepareBackgroundData(Request $request, array $validated, ?MonitorSlide $slide = null): array
    {
        $backgroundSource = $validated['background_source'] ?? 'inherit';
        $originalImagePath = $slide?->image_path;
        $imagePath = $originalImagePath;

        if ($backgroundSource !== 'upload' || ! empty($validated['deleteImage'])) {
            if (filled($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = null;
        }

        if ($backgroundSource === 'upload') {
            if ($request->hasFile('image')) {
                if (filled($originalImagePath)) {
                    Storage::disk('public')->delete($originalImagePath);
                }

                $imagePath = $request->file('image')->store('images', 'public');
            }

            if (blank($imagePath)) {
                throw ValidationException::withMessages([
                    'image' => __('Bitte wählen Sie zuerst ein eigenes Hintergrundbild aus.'),
                ]);
            }
        }

        unset($validated['deleteImage'], $validated['image']);

        $validated['image_path'] = $imagePath;
        $validated['background_source'] = $backgroundSource === 'inherit' ? null : $backgroundSource;

        return $validated;
    }
}
