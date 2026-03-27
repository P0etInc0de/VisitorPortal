<?php

// adjust to match actual permissions!

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Check via Gate / helper
        if (!(auth()->user()->can('view own visits') or auth()->user()->can('view welcome monitor')) {
            abort(403);
        }

        return view('user.index', ['users' => User::all()]);
    }
/*
    public function destroy(Post $post)
    {
        // Check role
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Only admins can delete posts.');
        }

        $post->delete();
        return redirect('/posts');
    }
*/
}
