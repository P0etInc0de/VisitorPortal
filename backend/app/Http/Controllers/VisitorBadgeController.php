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
        try {
        $request->validate([
            'email' => 'required|email|exists:visitors,email', //email has to be mail that exists in visitors table
            'visit_id' => 'nullable|exists:visits,id',
        ]);

            //get visitor with mail
            $visitor = Visitor::where('email', $request->email)->firstOrFail();

            if ($request->visit_id) {
                $visit = Visit::findOrFail($request->visit_id);
            } else {
                $visit = $visitor->nextVisit();
            }

            if (!$visit) {
                return back()
                    ->with(['error' => __('Es existiert kein Besuch für diesen Besucher.')])
                    ->withInput();
            }

            $pdf = Pdf::view('pdf.visitor_badge', [
                'visitor' => $visitor,
                'visit' => $visit,
            ])
                ->driver('gotenberg')
                ->format('A4')
                ->landscape();

            return $pdf->download(__('Besucherausweis_') . $visitor->last_name . '.pdf');

        }catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->with('error', __('Die E-Mail-Adresse wurde nicht im System gefunden.'));
        }catch
         (\Exception $exception){
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }
}
