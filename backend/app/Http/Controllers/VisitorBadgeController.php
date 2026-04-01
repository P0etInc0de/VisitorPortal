<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class VisitorBadgeController extends Controller
{
    public function index(){
        return view('reception.visitor_badge.visitor_badge');
    }

    public function store(Request $request){
        //validate stuff
        $request->validate([
            'email' => 'required|email|exists:visitors,email', //email has to be mail that exists in visitors table
            'paper_format' => 'required|in:a4,badge',
        ], [
            'email.exists' => __('Diese E-Mail-Adresse wurde nicht im System gefunden.')
        ]);

        try {
            //get visitor with mail
            $visitor = Visitor::where('email', $request->email)->firstOrFail();

            $pdf = Pdf::loadView('pdf.visitor_badge', ['visitor' => $visitor]);

            if ($request->paper_format === 'badge') {
                $pdf->setPaper([0, 0, 241, 156], 'landscape');
            } else {
                $pdf->setPaper('a4', 'portrait');
            }
            return $pdf->download('Besucherausweis_' . $visitor->last_name . '.pdf');
        }catch (\Exception $exception){
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }
}
