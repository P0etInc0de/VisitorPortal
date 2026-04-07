<?php

// adjust to match actual permissions!

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserPermissionsController extends Controller
{
    public function index()
    {
		$user = auth()->user();
		
		$data = [
			'name' => $user->name,
			'roles' => $user->getRoleNames(),
			'permissions' => $user->getAllPermissions()
				->pluck('name')
				->filter(fn($p) => str_contains($p, ' '))
				->groupBy(fn($p) => explode(' ', $p)[1] ?? 'general'),
		];

        // Check via Gate / helper
        /*if (!(auth()->user()->can('view own visits') or auth()->user()->can('view welcome monitor'))) {
            abort(403);
        }*/

        return view('profile.user-permissions', compact('data'));
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
