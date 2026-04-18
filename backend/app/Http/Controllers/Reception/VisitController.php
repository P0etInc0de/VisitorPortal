<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class VisitController extends Controller
{
    public function index(): View
    {
        return view('reception.all-visits');
    }
}
