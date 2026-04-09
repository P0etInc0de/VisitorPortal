<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all monitors from the database
        $monitors = Monitor::all();

        return view('monitor.index', compact('monitors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
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
