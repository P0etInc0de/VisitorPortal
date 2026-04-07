<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use ZipArchive;

class VisitorBadgeController extends Controller
{
    public function index(){
        $visits = Visit::with(['responsibleUser', 'visitors'])->get();
        return view('reception.visitor_badge.visitor_badge', compact('visits'));
    }

    public function download_badge(Request $request){
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

    public function download_all_badges(Request $request)
    {
        try {
            $request->validate([
                'visit' => 'required|exists:visits,id',
            ]);

            $Visitor_Number = 1;
            $visit = Visit::with('visitors')->findOrFail($request->visit);

            $zipFileName = storage_path('app/public/Besucherausweise_' . $visit->id . '.zip');
            $zip = new \ZipArchive();
            if ($zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {

                foreach ($visit->visitors as $visitor) {
                    $Visitor_Number =$Visitor_Number +1;
                    $pdf = Pdf::view('pdf.visitor_badge', [
                        'visitor' => $visitor,
                        'visit' => $visit,
                    ])
                        ->driver('gotenberg')
                        ->format('A4')
                        ->landscape();

                    $pdfContent = base64_decode($pdf->base64());
                    $pdfFileName = __('Besucherausweis_') . $visitor->last_name . '_' . $Visitor_Number . '.pdf';

                    $zip->addFromString($pdfFileName, $pdfContent);
                }

                $zip->close();
            }
            return response()->download($zipFileName)->deleteFileAfterSend();
        }catch (\Exception $exception){
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }
}
