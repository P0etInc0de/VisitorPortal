<?php

namespace app\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Mail\MarkdownMail;
use App\Notifications\TestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class SendMailController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        Mail::to($request->user())->send(new MarkdownMail($request->user()->name));
        Notification::send($request->user(), new TestNotification());
        return Redirect::to('/');
    }
}
