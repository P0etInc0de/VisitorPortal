<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WelcomeMonitorController extends Controller
{
    public function show(): View
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

    public function live(): View
    {
        return $this->show();
    }

    public function edit(): View
    {
        $layoutOptions = [
            [
                'id' => 'classic',
                'name' => 'Klassisch',
                'description' => 'Große Begrüßung mit Besuchsliste',
            ],
            [
                'id' => 'hero',
                'name' => 'Hero',
                'description' => 'Große Fläche mit Fokus auf Willkommen',
            ],
            [
                'id' => 'compact',
                'name' => 'Kompakt',
                'description' => 'Reduzierte Darstellung für mehr Termine',
            ],
        ];

        $backgroundOptions = [
            [
                'id' => 'none',
                'name' => 'Neutral',
                'class' => 'from-slate-100 to-slate-200',
            ],
            [
                'id' => 'lobby',
                'name' => 'Lobby',
                'class' => 'from-amber-100 via-stone-100 to-slate-200',
            ],
            [
                'id' => 'building',
                'name' => 'Gebäude',
                'class' => 'from-blue-100 via-slate-100 to-zinc-200',
            ],
            [
                'id' => 'abstract',
                'name' => 'Abstrakt',
                'class' => 'from-indigo-100 via-violet-100 to-slate-100',
            ],
        ];

        $visits = [
            ['time' => '09:30', 'name' => 'Max Mustermann', 'company' => 'Beispiel GmbH'],
            ['time' => '11:00', 'name' => 'Anna Becker', 'company' => 'ACME AG'],
            ['time' => '14:00', 'name' => 'David Keller', 'company' => 'Tech Solutions'],
        ];

        return view('monitor.editor')
            ->with('layoutOptions', $layoutOptions)
            ->with('backgroundOptions', $backgroundOptions)
            ->with('visits', $visits);
    }
}
