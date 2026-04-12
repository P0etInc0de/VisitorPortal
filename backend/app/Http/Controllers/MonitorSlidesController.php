<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorSlideRequest;
use App\Models\Monitor;
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
        return view('monitorSlides.create')->with('monitor', $this->monitor);
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
        //
    }
}
