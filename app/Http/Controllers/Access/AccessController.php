<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */

namespace App\Http\Controllers\Access;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Support\Access\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessController extends Controller
{
    public function index(): Response
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::ACCESS_MANAGE), 403);

        return Inertia::render('Access/Index', [
            'pageTitle' => 'Hak Akses (RBAC)',
            'matrix' => $this->buildMatrix(),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ])->values(),
            'permissions' => collect(PermissionCatalog::definitions())->map(fn (string $label, string $name) => [
                'name' => $name,
                'label' => $label,
                'wireKey' => PermissionCatalog::wireKey($name),
            ])->values(),
            'lockedAdmin' => PermissionCatalog::lockedForAdmin(),
            'urls' => [
                'save' => route('access.save'),
                'resetDefaults' => route('access.reset-defaults'),
            ],
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::ACCESS_MANAGE), 403);

        /** @var array<string, array<string, bool>> $matrix */
        $matrix = $request->input('matrix', []);

        foreach (PermissionCatalog::lockedForAdmin() as $locked) {
            $key = PermissionCatalog::wireKey($locked);
            if (! ($matrix[UserRole::Admin->value][$key] ?? false)) {
                return back()->with('error', 'Permission wajib admin tidak boleh dicabut: '.$locked);
            }
        }

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $selected = [];
            foreach (PermissionCatalog::names() as $perm) {
                $key = PermissionCatalog::wireKey($perm);
                if ($matrix[$roleEnum->value][$key] ?? false) {
                    $selected[] = $perm;
                }
            }
            $role->syncPermissions($selected);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('message', 'Matrix hak akses disimpan.');
    }

    public function resetDefaults(): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::ACCESS_MANAGE), 403);

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $role->syncPermissions(PermissionCatalog::defaultMatrix()[$roleEnum->value] ?? []);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        return back()->with('message', 'Matrix dikembalikan ke default katalog.');
    }

    /**
     * @return array<string, array<string, bool>>
     */
    private function buildMatrix(): array
    {
        $matrix = [];

        foreach (UserRole::cases() as $roleEnum) {
            $role = Role::findOrCreate($roleEnum->value, 'web');
            $owned = $role->permissions->pluck('name')->all();
            foreach (PermissionCatalog::names() as $perm) {
                $matrix[$roleEnum->value][PermissionCatalog::wireKey($perm)] = in_array($perm, $owned, true);
            }
        }

        return $matrix;
    }
}
