<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Contracts\View\View;

class VisitController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Visit::class);

        return view('reception.all-visits');
    }
}
