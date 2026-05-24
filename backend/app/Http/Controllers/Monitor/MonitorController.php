<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\UpdateMonitorFallbackPageRequest;
use App\Http\Requests\Monitor\UpdateMonitorRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Support\RasterImageUpload;
use App\Tasks\WelcomeMonitorAutoGeneration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MonitorController extends Controller
{
    /**
     * Display the specified resource.
     * (Show the Welcome Monitor)
     */
    public function show(Monitor $monitor)
    {
        $this->authorize('view', $monitor);

        return view('monitor.show')
            ->with('monitor', $monitor);
    }

    /**
     * Show the form for editing the specified resource.
     * Edit Monitor and See Monitor_Slide resource
     */
    public function edit(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

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
    public function update(UpdateMonitorRequest $request, Monitor $monitor)
    {
        $validated = $request->validated();

        $imagePath = $monitor->image_path;

        $this->authorize('update', $monitor);

        if (! empty($validated['deleteImage'])) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;

            if (($validated['background_source'] ?? null) === 'upload') {
                $validated['background_source'] = Monitor::DEFAULT_BACKGROUND_SOURCE;
            }

            Log::info('User ID '.auth()->id()." deleted background image for Monitor ID: {$monitor->id}");
        } elseif (! empty($request->file('image'))) {
            $path = RasterImageUpload::store($request->file('image'));

            if ($imagePath != null) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = $path;
            $validated['background_source'] = 'upload';

            Log::info('User ID '.auth()->id()." updated background image for Monitor ID: {$monitor->id}");
        }

        unset($validated['deleteImage'], $validated['image']);

        if (($validated['background_source'] ?? null) === 'upload' && blank($imagePath)) {
            $validated['background_source'] = Monitor::DEFAULT_BACKGROUND_SOURCE;
        }

        $monitor->update(array_merge($validated, [
            'image_path' => $imagePath,
        ]));

        if ($monitor->auto_generation) {
            app(WelcomeMonitorAutoGeneration::class)($monitor);
        }

        Log::info('User ID '.auth()->id()." updated settings for Monitor ID: {$monitor->id}");

        return redirect()->route('monitors.edit', $monitor)
            ->with('success', __('Monitor Einstellungen gespeichert'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function editFallbackPage(Monitor $monitor)
    {
        $this->authorize('update', $monitor);

        return view('monitor.fallback-edit')
            ->with('monitor', $monitor);
    }

    public function updateFallbackPage(UpdateMonitorFallbackPageRequest $request, Monitor $monitor)
    {
        $validated = $request->validated();
        $imagePath = $monitor->fallback_image_path;

        $this->authorize('update', $monitor);

        if (! empty($validated['deleteImage']) || ($validated['background_source'] ?? 'inherit') !== 'upload') {
            if (filled($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            $imagePath = null;
        }

        if (($validated['background_source'] ?? null) === 'upload') {
            if ($request->hasFile('image')) {
                if (filled($monitor->fallback_image_path)) {
                    Storage::disk('public')->delete($monitor->fallback_image_path);
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

        $monitor->update([
            'fallback_heading' => $validated['heading'],
            'fallback_subheading' => $validated['subheading'],
            'fallback_show_logo' => $validated['show_logo'],
            'fallback_show_date' => $validated['show_date'],
            'fallback_background_source' => ($validated['background_source'] ?? 'inherit') === 'inherit' ? null : $validated['background_source'],
            'fallback_image_path' => $imagePath,
        ]);

        Log::info('User ID '.auth()->id()." updated fallback page for Monitor ID: {$monitor->id}");

        return redirect(route('monitors.edit', $monitor).'#monitor-pages')
            ->with('success', __('Allgemeine Begrüßungsseite gespeichert'));
    }
}
