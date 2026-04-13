<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorRequest;
use App\Models\Monitor;
use App\Models\MonitorSlide;
use App\Models\Visitor;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    /**
     * Display a listing of monitors.
     * Not needed yet
     */
    public function index()
    {
        // Fetch all monitors from the database
        // $monitors = Monitor::all();

        // return view('monitor.index', compact('monitors'));
    }

    /**
     * Show the form for creating a new resource.
     * Not needed yet
     */
    public function create()
    {
        // $visitors = Visitor::all();

        // return view('monitor.create.php', compact('visitors'));
    }

    /**
     * Store a newly created resource in storage.
     * Not needed yet
     */
    public function store(StoreMonitorRequest $request)
    {
        // $validated = $request->validated();
        // Monitor::create($validated);

        // return redirect()->route('monitors.index')
        //    ->with('success', 'Monitor wurde erfolgreich angelegt.');
    }

    /**
     * Display the specified resource.
     * (Show the Welcome Monitor)
     */
    public function show(string $id)
    {
        $hasLogo = file_exists(public_path('images/logo.png'));
        $hasCustomBackground = file_exists(public_path('images/monitor-background.jpg')) || file_exists(public_path('images/monitor-background.png'));
        $backgroundAsset = file_exists(public_path('images/monitor-background.jpg'))
            ? asset('images/monitor-background.jpg')
            : (file_exists(public_path('images/monitor-background.png')) ? asset('images/monitor-background.png') : null);

        $visitors = [
            ['time' => '09:30', 'name' => 'Max Mustermann', 'company' => 'Beispiel GmbH'],
            ['time' => '11:00', 'name' => 'Anna Becker', 'company' => 'ACME AG'],
            ['time' => '13:30', 'name' => 'David Keller', 'company' => 'Tech Solutions'],
            ['time' => '15:00', 'name' => 'Sven Maurer', 'company' => 'Maurer Industrie'],
        ];

        return view('monitor.show')
            ->with('hasLogo', $hasLogo)
            ->with('hasCustomBackground', $hasCustomBackground)
            ->with('backgroundAsset', $backgroundAsset)
            ->with('visitors', $visitors);
    }

    /**
     * Show the form for editing the specified resource.
     * Edit Monitor and See Monitor_Slide resource
     */
    public function edit(string $id)
    {
        $monitor = Monitor::query()->findOrFail($id);
        $monitor_slides = MonitorSlide::query()->where('monitor_id', $monitor->id)->orderBy('monitor_slides.slide_number')->get();

        return view('monitor.edit')
            ->with('monitor', $monitor)
            ->with('monitor_slides', $monitor_slides);
    }

    /**
     * Update the specified resource in storage.
     * Not needed yet
     */
    public function update(Request $request, string $id)
    {
        //
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
