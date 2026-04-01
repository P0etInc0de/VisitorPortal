<?php

namespace App\Http\Controllers;

use App\Models\Visitor;
use Illuminate\Http\Request;

class VisitorBadgeController extends Controller
{
    public function index(){
        return view('reception.visitor_badge.visitor_badge');
    }

    public function store(Request $request){
    }
}
