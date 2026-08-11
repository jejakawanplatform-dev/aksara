<?php

namespace App\Http\Controllers\Users;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\User;
use App\Support\Access\PermissionCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $search = (string) $request->query('search', '');
        $roleFilter = (string) $request->query('role', '');
        $linksUserId = $request->query('linksUserId');
        $perPage = (int) $request->query('per_page', 10);
        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $users = User::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($roleFilter !== '', fn ($q) => $q->where('role', $roleFilter))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
                'roleLabel' => $user->role?->label(),
                'isStudent' => $user->isStudent(),
                'isParent' => $user->isParent(),
                'isHomeroomTeacher' => $user->isHomeroomTeacher(),
            ]);

        $linksUser = null;
        if ($linksUserId) {
            $lu = User::query()->with(['classes', 'children', 'homeroomClasses'])->find($linksUserId);
            if ($lu) {
                $linksUser = [
                    'id' => $lu->id,
                    'name' => $lu->name,
                    'role' => $lu->role?->value,
                    'isStudent' => $lu->isStudent(),
                    'isParent' => $lu->isParent(),
                    'isHomeroomTeacher' => $lu->isHomeroomTeacher(),
                    'classes' => $lu->classes->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])->values(),
                    'children' => $lu->children->map(fn ($c) => [
                        'id' => $c->id,
                        'name' => $c->name,
                    ])->values(),
                    'homeroomClassId' => $lu->homeroomClasses()->value('id'),
                ];
            }
        }

        return Inertia::render('Users/Index', [
            'pageTitle' => 'Manajemen Pengguna',
            'users' => $users,
            'filters' => [
                'search' => $search,
                'role' => $roleFilter,
                'per_page' => $perPage,
            ],
            'roles' => collect(UserRole::cases())->map(fn (UserRole $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ])->values(),
            'classes' => SchoolClass::query()->orderBy('grade')->orderBy('name')->get(['id', 'name', 'grade']),
            'students' => User::query()
                ->where('role', UserRole::Student)
                ->orderBy('name')
                ->get(['id', 'name']),
            'linksUser' => $linksUser,
            'urls' => [
                'index' => route('users.index'),
                'store' => route('users.store'),
                'update' => route('users.update', ['user' => '__ID__']),
                'destroy' => route('users.destroy', ['user' => '__ID__']),
                'attachClass' => route('users.attach-class', ['user' => '__ID__']),
                'detachClass' => route('users.detach-class', ['user' => '__UID__', 'class' => '__CID__']),
                'attachChild' => route('users.attach-child', ['user' => '__ID__']),
                'detachChild' => route('users.detach-child', ['user' => '__UID__', 'child' => '__CID__']),
                'homeroom' => route('users.homeroom', ['user' => '__ID__']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::assignable()))],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => UserRole::from($data['role']),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $user->syncAppRole();

        return redirect()->route('users.index')->with('message', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_map(fn (UserRole $r) => $r->value, UserRole::assignable()))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if ($user->id === Auth::id() && $data['role'] !== UserRole::Admin->value) {
            return back()->with('error', 'Tidak bisa menurunkan role akun admin yang sedang login.');
        }

        Role::firstOrCreate(['name' => $data['role'], 'guard_name' => 'web']);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = UserRole::from($data['role']);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncAppRole();

        return redirect()->route('users.index')->with('message', 'Pengguna diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        if ($user->isTeacher() && $user->learningPlans()->exists()) {
            return back()->with('error', 'Guru masih punya rencana pembelajaran — arsipkan/hapus rencana dulu.');
        }

        if ($user->isHomeroomTeacher() && $user->homeroomClasses()->exists()) {
            return back()->with('error', 'Wali kelas masih terikat rombel — lepas homeroom dulu.');
        }

        DB::transaction(function () use ($user) {
            $user->classes()->detach();
            $user->children()->detach();
            $user->parents()->detach();
            $user->delete();
        });

        return redirect()->route('users.index')->with('message', 'Pengguna dihapus.');
    }

    public function attachClass(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $data = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
        ]);

        if (! $user->isStudent()) {
            return back()->with('error', 'Hanya siswa yang bisa digabung ke rombel.');
        }

        $user->classes()->syncWithoutDetaching([$data['class_id']]);

        return redirect()
            ->route('users.index', ['linksUserId' => $user->id])
            ->with('message', 'Siswa ditambahkan ke rombel.');
    }

    public function detachClass(User $user, SchoolClass $class): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $user->classes()->detach($class->id);

        return redirect()
            ->route('users.index', ['linksUserId' => $user->id])
            ->with('message', 'Siswa dikeluarkan dari rombel.');
    }

    public function attachChild(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $data = $request->validate([
            'child_id' => 'required|exists:users,id',
        ]);

        if (! $user->isParent()) {
            return back()->with('error', 'Hanya akun wali murid yang bisa ditautkan ke anak.');
        }

        $child = User::query()->findOrFail($data['child_id']);
        if (! $child->isStudent()) {
            return back()->with('error', 'Anak harus ber-role siswa.');
        }

        $user->children()->syncWithoutDetaching([$child->id]);

        return redirect()
            ->route('users.index', ['linksUserId' => $user->id])
            ->with('message', 'Anak ditautkan ke wali murid.');
    }

    public function detachChild(User $user, User $child): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        $user->children()->detach($child->id);

        return redirect()
            ->route('users.index', ['linksUserId' => $user->id])
            ->with('message', 'Tautan anak dilepas.');
    }

    public function saveHomeroom(Request $request, User $user): RedirectResponse
    {
        abort_unless(Auth::user()?->can(PermissionCatalog::USERS_MANAGE), 403);

        if (! $user->isHomeroomTeacher()) {
            return back()->with('error', 'Hanya wali kelas yang bisa ditetapkan sebagai homeroom.');
        }

        $data = $request->validate([
            'homeroom_class_id' => 'nullable|exists:school_classes,id',
        ]);

        SchoolClass::query()
            ->where('homeroom_teacher_id', $user->id)
            ->update(['homeroom_teacher_id' => null]);

        if (! empty($data['homeroom_class_id'])) {
            SchoolClass::query()->whereKey($data['homeroom_class_id'])->update([
                'homeroom_teacher_id' => $user->id,
            ]);
            $message = 'Wali kelas ditetapkan ke rombel.';
        } else {
            $message = 'Penugasan wali kelas dikosongkan.';
        }

        return redirect()
            ->route('users.index', ['linksUserId' => $user->id])
            ->with('message', $message);
    }
}
