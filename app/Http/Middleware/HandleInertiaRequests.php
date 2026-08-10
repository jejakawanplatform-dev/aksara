<?php

namespace App\Http\Middleware;

use App\Support\Navigation\SidebarNav;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role instanceof \BackedEnum ? $user->role->value : $user->role,
                    'roleLabel' => method_exists($user->role, 'label')
                        ? $user->role->label()
                        : (string) ($user->role instanceof \BackedEnum ? $user->role->value : $user->role),
                ] : null,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name')->values() : [],
            ],
            'nav' => fn () => $user ? SidebarNav::groups($request) : [],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'appName' => config('app.name', 'Aksara'),
            'urls' => [
                'profile' => route('profile.edit'),
                'logout' => route('logout'),
                'dashboard' => route('dashboard'),
            ],
        ];
    }
}
