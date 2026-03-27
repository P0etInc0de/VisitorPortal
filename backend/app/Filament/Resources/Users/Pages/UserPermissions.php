<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Pages\Page;

class UserPermissions extends Page
{
 	protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'My Permissions';
    protected static ?string $title = 'My Permissions';
    protected static string $view = 'filament.pages.user-permissions';

    // Only show this page in the nav for logged in users
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    // The data we pass to the view
    public function getUserData(): array
    {
        $user = auth()->user();

        return [
            'name'        => $user->name,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()
                                ->pluck('name')
                                ->groupBy(fn($p) => explode(' ', $p)[1] ?? 'general'),
                                // groups by resource e.g. 'posts', 'users', 'reports'
        ];
    }   

	public static function canAccess(): bool
	{
		return auth()->check();
	}
}
