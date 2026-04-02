<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class UserPermissions extends Page
{
 	protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationLabel = 'My Permissions';
    protected static ?string $title = 'My Permissions';
    protected string $view = 'filament.pages.user-permissions';

	public static function canAccess(): bool
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
								->filter(fn($p) => str_contains($p, ' '))
                                ->groupBy(fn($p) => explode(' ', $p)[1] ?? 'general'),
                                // groups by resource e.g. 'posts', 'users', 'reports'
        ];
    }   
}
