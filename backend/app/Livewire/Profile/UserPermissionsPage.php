<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;

class UserPermissionsPage extends Component
{
    public string $search = '';

    public function render(): View
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $permissions = $this->permissionGroups($user);

        return view('livewire.profile.user-permissions-page', [
            'summary' => [
                'name' => $this->displayUserName($user),
                'roles' => $user->getRoleNames()->values(),
                'role_count' => $user->getRoleNames()->count(),
                'permission_count' => $user->getAllPermissions()->count(),
                'resource_count' => $permissions->count(),
            ],
            'permissionGroups' => $permissions,
        ])->layout('layouts.app', [
            'header' => new HtmlString(
                view('partials.page-header', [
                    'title' => __('Meine Berechtigungen'),
                ])->render()
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function permissionGroups(User $user): Collection
    {
        $search = Str::lower(trim($this->search));

        return $user->getAllPermissions()
            ->pluck('name')
            ->filter(fn (string $permission): bool => str_contains($permission, ':'))
            ->map(function (string $permission): array {
                [$action, $resource] = array_pad(explode(':', $permission, 2), 2, 'Allgemein');

                return [
                    'action' => $action,
                    'action_label' => $this->permissionActionLabel($action),
                    'resource' => $resource,
                    'resource_label' => $this->resourceLabel($resource),
                    'description' => $this->permissionActionDescription($action, $resource),
                    'sort_order' => $this->permissionActionSortOrder($action),
                ];
            })
            ->groupBy('resource')
            ->map(function (Collection $group, string $resource): array {
                $items = $group
                    ->sortBy([
                        ['sort_order', 'asc'],
                        ['action_label', 'asc'],
                    ])
                    ->values();

                return [
                    'resource' => $resource,
                    'resource_label' => $this->resourceLabel($resource),
                    'items' => $items->all(),
                    'count' => $items->count(),
                ];
            })
            ->values()
            ->filter(function (array $group) use ($search): bool {
                if ($search === '') {
                    return true;
                }

                $haystack = Str::lower(implode(' ', array_filter([
                    $group['resource_label'],
                    ...collect($group['items'])->flatMap(fn (array $item): array => [
                        $item['action_label'] ?? null,
                        $item['description'] ?? null,
                    ])->all(),
                ])));

                return Str::contains($haystack, $search);
            })
            ->sortBy('resource_label')
            ->values();
    }

    private function displayUserName(User $user): string
    {
        $fullName = trim(implode(' ', array_filter([
            $user->first_name,
            $user->name,
        ])));

        return $fullName !== '' ? $fullName : (string) ($user->name ?: $user->email);
    }

    private function resourceLabel(string $resource): string
    {
        return match ($resource) {
            'User' => __('Benutzer'),
            'Role' => __('Rollen'),
            'Department' => __('Abteilungen'),
            'Visit' => __('Besuche'),
            'Visitor' => __('Besucher'),
            'Monitor' => __('Monitore'),
            'MonitorSlide' => __('Monitor-Folien'),
            'UserPermissions' => __('Berechtigungen'),
            default => Str::headline($resource),
        };
    }

    private function permissionActionLabel(string $action): string
    {
        return match ($action) {
            'ViewAny' => __('Alle ansehen'),
            'View' => __('Einzeln ansehen'),
            'ViewOwn' => __('Eigene ansehen'),
            'ViewDepartment' => __('Im eigenen Bereich ansehen'),
            'Create' => __('Anlegen'),
            'Update' => __('Bearbeiten'),
            'Edit' => __('Bearbeiten'),
            'EditAny' => __('Alle bearbeiten'),
            'EditOwn' => __('Eigene bearbeiten'),
            'EditDepartment' => __('Im eigenen Bereich bearbeiten'),
            'Delete' => __('Löschen'),
            'DeleteAny' => __('Alle löschen'),
            'DeleteOwn' => __('Eigene löschen'),
            'DeleteDepartment' => __('Im eigenen Bereich löschen'),
            'Restore' => __('Wiederherstellen'),
            'RestoreAny' => __('Alle wiederherstellen'),
            'ForceDelete' => __('Endgültig löschen'),
            'ForceDeleteAny' => __('Alle endgültig löschen'),
            'Replicate' => __('Duplizieren'),
            'Reorder' => __('Reihenfolge ändern'),
            'DeactivateAny' => __('Alle deaktivieren'),
            'DeactivateDepartment' => __('Im eigenen Bereich deaktivieren'),
            'CheckIn' => __('Check-in durchführen'),
            'CheckOut' => __('Check-out durchführen'),
            'Print' => __('Ausweise drucken'),
            default => Str::headline($action),
        };
    }

    private function permissionActionDescription(string $action, string $resource): string
    {
        $resourceLabel = Str::lower($this->resourceLabel($resource));

        if ($action === 'View' && $resource === 'Dashboard') {
            return __('Dashboard öffnen.');
        }

        if ($action === 'View' && $resource === 'UserPermissions') {
            return __('Seite mit Berechtigungen öffnen.');
        }

        return match ($action) {
            'ViewAny' => __('Zugriff auf alle :resource.', ['resource' => $resourceLabel]),
            'View' => __('Einzelne :resource öffnen.', ['resource' => $resourceLabel]),
            'ViewOwn' => __('Eigene :resource sehen.', ['resource' => $resourceLabel]),
            'ViewDepartment' => __(':resource im eigenen Bereich sehen.', ['resource' => $resourceLabel]),
            'Create' => __('Neue :resource anlegen.', ['resource' => $resourceLabel]),
            'Update', 'Edit' => __('Vorhandene :resource bearbeiten.', ['resource' => $resourceLabel]),
            'EditAny' => __('Alle :resource bearbeiten.', ['resource' => $resourceLabel]),
            'EditOwn' => __('Eigene :resource bearbeiten.', ['resource' => $resourceLabel]),
            'EditDepartment' => __(':resource im eigenen Bereich bearbeiten.', ['resource' => $resourceLabel]),
            'Delete' => __(':resource löschen.', ['resource' => $resourceLabel]),
            'DeleteAny' => __('Alle :resource löschen.', ['resource' => $resourceLabel]),
            'DeleteOwn' => __('Eigene :resource löschen.', ['resource' => $resourceLabel]),
            'DeleteDepartment' => __(':resource im eigenen Bereich löschen.', ['resource' => $resourceLabel]),
            'Restore' => __('Gelöschte :resource wiederherstellen.', ['resource' => $resourceLabel]),
            'RestoreAny' => __('Alle gelöschten :resource wiederherstellen.', ['resource' => $resourceLabel]),
            'ForceDelete' => __(':resource endgültig löschen.', ['resource' => $resourceLabel]),
            'ForceDeleteAny' => __('Alle :resource endgültig löschen.', ['resource' => $resourceLabel]),
            'Replicate' => __(':resource duplizieren.', ['resource' => $resourceLabel]),
            'Reorder' => __('Reihenfolge von :resource ändern.', ['resource' => $resourceLabel]),
            'DeactivateAny' => __('Alle :resource deaktivieren.', ['resource' => $resourceLabel]),
            'DeactivateDepartment' => __(':resource im eigenen Bereich deaktivieren.', ['resource' => $resourceLabel]),
            'CheckIn' => __('Check-ins für :resource durchführen.', ['resource' => $resourceLabel]),
            'CheckOut' => __('Check-outs für :resource durchführen.', ['resource' => $resourceLabel]),
            'Print' => __('Ausweise oder Dokumente für :resource drucken.', ['resource' => $resourceLabel]),
            default => __('Berechtigung für :resource.', ['resource' => $resourceLabel]),
        };
    }

    private function permissionActionSortOrder(string $action): int
    {
        return match ($action) {
            'ViewAny' => 10,
            'View' => 20,
            'ViewOwn' => 30,
            'ViewDepartment' => 40,
            'Create' => 50,
            'Update', 'Edit' => 60,
            'EditAny' => 70,
            'EditOwn' => 80,
            'EditDepartment' => 90,
            'CheckIn' => 100,
            'CheckOut' => 110,
            'Print' => 120,
            'Delete' => 130,
            'DeleteAny' => 140,
            'DeleteOwn' => 150,
            'DeleteDepartment' => 160,
            'Restore' => 170,
            'RestoreAny' => 180,
            'ForceDelete' => 190,
            'ForceDeleteAny' => 200,
            'DeactivateAny' => 210,
            'DeactivateDepartment' => 220,
            'Replicate' => 230,
            'Reorder' => 240,
            default => 999,
        };
    }
}
