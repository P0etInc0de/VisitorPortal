<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreMonitorSlideRequest;
use App\Http\Requests\Monitor\UpdateMonitorSlideRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visit;
use App\Models\Visitor;
use App\Support\RasterImageUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MonitorSlidesController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Monitor $monitor)
    {
        $this->authorize('create', MonitorSlide::class);
        $this->authorize('update', $monitor);

        $currentMax = MonitorSlide::query()
            ->where('monitor_id', $monitor->id)
            ->where('is_auto_generated', false)
            ->max('monitor_slides.slide_number') ?? 0; // Default to 0 if null

        $defaultSlideNumber = $currentMax + 1;
        $todayVisits = Visit::query()
            ->where('site_id', $monitor->site_id)
            ->where('is_confidential', false)
            ->whereDate('visits.scheduled_from', '<=', Carbon::today())
            ->whereDate('visits.scheduled_until', '>=', Carbon::today())
            ->with('visitors')
            ->orderBy('visits.scheduled_from')
            ->with('host')
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
        $this->authorize('create', MonitorSlide::class);
        $this->authorize('update', $monitor);

        $validated = $request->validated();
        $validated['visitors'] = $this->normalizeVisitors($request->input('visitors'), $monitor);
        $validated = $this->prepareBackgroundData($request, $validated);

        $monitor_slide = $monitor->monitorSlides()->create($validated);

        Log::info('User ID '.auth()->id()." created Monitor-Slide with ID: {$monitor_slide->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')
            ->with('success', __('Neue Seite erstellt'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Monitor $monitor, MonitorSlide $slide)
    {
        $this->ensureSlideBelongsToMonitor($monitor, $slide);

        $this->authorize('update', $slide);
        $this->authorize('update', $monitor);

        $todayVisits = Visit::query()
            ->where('site_id', $monitor->site_id)
            ->where('is_confidential', false)
            ->whereDate('visits.scheduled_from', '<=', Carbon::today())
            ->whereDate('visits.scheduled_until', '>=', Carbon::today())
            ->orderBy('visits.scheduled_from')
            ->with('host')
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
        $this->ensureSlideBelongsToMonitor($monitor, $slide);

        $this->authorize('update', $monitor);
        $this->authorize('update', $slide);

        $validated = $request->validated();
        $validated['visitors'] = $this->normalizeVisitors($validated['visitors'] ?? null, $monitor);
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
        $this->ensureSlideBelongsToMonitor($monitor, $slide);

        $this->authorize('update', $monitor);
        $this->authorize('delete', $slide);

        if (filled($slide->image_path)) {
            Storage::disk('public')->delete($slide->image_path);
        }

        $slide->delete();

        Log::info('User ID '.auth()->id()." deleted Monitor-Slide with ID: {$slide->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')->with('success', __('Seite gelöscht'));
    }

    private function ensureSlideBelongsToMonitor(Monitor $monitor, MonitorSlide $slide): void
    {
        // Nested monitor routes must not allow cross-parent slide access.
        abort_unless((int) $slide->monitor_id === (int) $monitor->id, 404);
    }

    private function normalizeVisitors(?string $encodedVisitors, Monitor $monitor): array
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

            if ($numericId !== null && ! $this->visitorCanBeShownOnMonitor($numericId, $monitor)) {
                throw ValidationException::withMessages([
                    'visitors' => __('Dieser Besucher kann für diesen Monitor nicht ausgewählt werden.'),
                ]);
            }

            if ($numericId === null && mb_strlen($name) > 50) {
                throw ValidationException::withMessages([
                    'visitors' => __('Manuell hinzugefügte Namen dürfen maximal :count Zeichen lang sein.', ['count' => 50]),
                ]);
            }

            $idKey = $numericId !== null ? 'visit:'.$numericId : null;
            $nameKey = 'name:'.mb_strtolower($name);

            if (($idKey !== null && isset($seen[$idKey])) || isset($seen[$nameKey])) {
                continue;
            }

            $normalized[] = [
                'id' => $numericId,
                'name' => $name,
            ];
            if ($idKey !== null) {
                $seen[$idKey] = true;
            }

            $seen[$nameKey] = true;
        }

        if (count($normalized) > 6) {
            throw ValidationException::withMessages([
                'visitors' => __('Maximal 6 Besucher je Seite'),
            ]);
        }

        return $normalized;
    }

    private function visitorCanBeShownOnMonitor(int $visitorId, Monitor $monitor): bool
    {
        return Visitor::query()
            ->whereKey($visitorId)
            ->whereHas('visits', fn ($query) => $query
                ->where('visits.site_id', $monitor->site_id)
                ->where('visits.is_confidential', false))
            ->exists();
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

                $imagePath = RasterImageUpload::store($request->file('image'));
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
