<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Controller for Check-In/-Out
 *
 */
class CheckInOutController extends Controller
{
    public function index(){
        return view('portal.check_in_out.check_in_out');
    }

    public function store(Request $request){

    }
}
