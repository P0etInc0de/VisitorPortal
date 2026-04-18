<?php

namespace App\Http\Controllers;

use App\Enums\VisitorStatusEnum;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\Request;

/**
 * Controller for Check-In/-Out
 */
class CheckInOutController extends Controller
{
    public function index()
    {
		$this->authorizeAny(['checkIn', 'checkOut'], Visit::class);

        $visits = Visit::with(['responsibleUser', 'visitors'])->get();

        return view('portal.check_in_out.check_in_out', compact('visits'));
    }

    public function store(Request $request)
    {
        // validate stuff
        $request->validate([
            'email' => 'required|email|exists:visitors,email', // email has to be mail that exists in visitors table
            'action' => 'required|in:check_in,check_out', // action has to be check_in or check_out
        ], [
            'email.exists' => __('Diese E-Mail-Adresse wurde nicht im System gefunden.'),
        ]);

        try {
            // get visitor with mail
            $visitor = Visitor::where('email', $request->email)->firstOrFail();

            // change status of said visitor
            $visitor->status = match ($request->action) {
                'check_in' => VisitorStatusEnum::Checked_In,
                'check_out' => VisitorStatusEnum::Checked_Out,
            };

            // save changes back to db
            $visitor->save();

            return back()->with('success', __('Status erfolgreich geändert'));

        } catch (\Exception $exception) {
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }

    public function check_in_out_all(Request $request)
    {
        try {
            $request->validate([
                'visit' => 'required|exists:visits,id',
                'action' => 'required|in:check_in,check_out',
            ]);

            $status = match ($request->action) {
                'check_in' => VisitorStatusEnum::Checked_In,
                'check_out' => VisitorStatusEnum::Checked_Out,
            };

            $visit = Visit::with('visitors')->findOrFail($request->visit);
            foreach ($visit->visitors as $visitor) {
                $visitor->update(['status' => $status]);
            }

            return back()->with('success', __('Status erfolgreich geändert'));
        } catch (\Exception $exception) {
            return back()->with('error', __('Etwas ist schiefgelaufen'));
        }
    }
}
