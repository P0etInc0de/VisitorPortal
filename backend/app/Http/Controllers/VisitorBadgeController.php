<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;

class VisitorBadgeController extends Controller
{
    public function index(){
        $visits = Visit::with(['responsibleUser', 'visitors'])->get();
        return view('reception.visitor_badge.visitor_badge', compact('visits'));
    }

    public function store(Request $request){
        //validate stuff
        $request->validate([
            'email' => 'required|email|exists:visitors,email', //email has to be mail that exists in visitors table
        ], [
            'email.exists' => __('Diese E-Mail-Adresse wurde nicht im System gefunden.')
        ]);

        try {
            //get visitor with mail
            $visitor = Visitor::where('email', $request->email)->firstOrFail();
            $pdf = Pdf::view('pdf.visitor_badge', [
                'visitor' => $visitor
            ])
                ->driver('gotenberg')
                ->format('A4')
                ->landscape();

            return $pdf->download(__('Besucherausweis_') . $visitor->last_name . '.pdf');

        }catch (\Exception $exception){
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }
}
