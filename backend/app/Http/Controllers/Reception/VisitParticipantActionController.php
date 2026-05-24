<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use App\Support\BadgePdfDimensions;
use App\Support\BadgePdfMediaBoxCropper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class VisitParticipantActionController extends Controller
{
    public function printBadge(Visit $visit, Visitor $visitor, VisitActionService $visitActionService)
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('view', $visit);
        $this->authorize('print', Visitor::class);

        $visit->loadMissing('host');
        $participant = $visitActionService->printBadge($visit, $visitor);

        Log::channel('web')->info('Visit participant badge downloaded', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => auth()->id(),
        ]);

        $filename = __('Besucherausweis_').trim(($participant->first_name ?? '').'_'.($participant->name ?? 'Gast')).'.pdf';
        $fallbackFilename = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $filename) ?: 'Besucherausweis.pdf';

        $pdfContent = Pdf::view('pdf.visitor_badge', [
            'visit' => $visit,
            'visitor' => $participant,
        ])
            ->driver('gotenberg')
            ->paperSize(BadgePdfDimensions::REQUEST_WIDTH_PT, BadgePdfDimensions::REQUEST_HEIGHT_PT, 'pt')
            ->margins(0, 0, 0, 0, 'pt')
            ->scale(1)
            ->generatePdfContent();

        return response(BadgePdfMediaBoxCropper::cropBottomWhitespace($pdfContent), Response::HTTP_OK, [
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename, $fallbackFilename),
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function checkIn(Request $request, Visit $visit, Visitor $visitor, VisitActionService $visitActionService): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $this->authorize('viewAny', Visit::class);
        $this->authorize('view', $visit);
        $this->authorize('checkIn', Visitor::class);

        $visitActionService->checkInParticipant($visit, $visitor, $user);

        Log::channel('web')->info('Visit participant checked in', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => $user->id,
        ]);

        return back();
    }

    public function checkOut(Request $request, Visit $visit, Visitor $visitor, VisitActionService $visitActionService): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $this->authorize('viewAny', Visit::class);
        $this->authorize('view', $visit);
        $this->authorize('checkOut', Visitor::class);

        $visitActionService->checkOutParticipant($visit, $visitor, $user);

        Log::channel('web')->info('Visit participant checked out', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => $user->id,
        ]);

        return back();
    }
}
