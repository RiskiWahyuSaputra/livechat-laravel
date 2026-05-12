<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RoleController extends Controller
{
    // Daftar Permissions yang tersedia di sistem
    private $availablePermissions = [
        'view_chat' => 'Akses Live Chat Workspace',
        'view_history' => 'Lihat Riwayat & Arsip Chat',
        'manage_quick_replies' => 'Kelola Balasan Cepat',
        'manage_customers' => 'Kelola Data Pelanggan',
        'manage_settings' => 'Kelola Pengaturan & Jam Operasional',
        'manage_roles' => 'Kelola Role & Akses (Superadmin)',
    ];

    public function index()
    {
        // Auto-sync data (one-time logic)
        $this->syncLegacyRoles();

        $admins = Admin::with('roleModel')->orderBy('is_superadmin', 'desc')->latest()->get();
        $permissions = $this->availablePermissions;
        $rolesQuery = Role::query();

        if ($this->rolesTableHasLevelColumn()) {
            $rolesQuery->orderBy('level');
        }

        $rolesList = $rolesQuery
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'level' => $this->resolveRoleLevel($role->slug, $role),
                ];
            })
            ->values();

        return view('admin.roles.admins', compact('admins', 'permissions', 'rolesList'));
    }

    private function syncLegacyRoles()
    {
        // Pastikan role default ada dengan deskripsi dan level
        foreach ($this->defaultRoles() as $slug => $data) {
            $payload = [
                'name' => $data['name'],
                'description' => $data['description'],
            ];

            if ($this->rolesTableHasLevelColumn()) {
                $payload['level'] = $data['level'];
            }

            Role::updateOrCreate(
                ['slug' => $slug],
                $payload
            );
        }

        $admins = Admin::whereNull('role_id')->whereNotNull('role')->get();
        foreach ($admins as $admin) {
            $role = Role::where('slug', $admin->role)->first();
            if ($role) {
                $admin->update(['role_id' => $role->id]);
            }
        }
    }

    private function defaultRoles(): array
    {
        return [
            'super_admin' => [
                'name' => 'Superadmin',
                'description' => 'Akses penuh ke seluruh sistem, modul, dan pengaturan keamanan.',
                'level' => 1,
            ],
            'agent1' => [
                'name' => 'Agent 1 (Supervisor)',
                'description' => 'Atasan/Supervisor Agent - Memiliki wewenang untuk menangani eskalasi dari Agent 2.',
                'level' => 2,
            ],
            'agent2' => [
                'name' => 'Agent 2 (Staff)',
                'description' => 'Staff Agent - Menangani percakapan awal dan dapat melakukan eskalasi ke Agent 1.',
                'level' => 3,
            ],
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:admins',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'role' => 'required|string', // Ini sekarang slug role
            'permissions' => 'nullable|array',
            'level' => 'required|integer|min:1',
        ]);

        $role = Role::where('slug', $request->role)->first();
        $is_superadmin = $request->role === 'super_admin';
        $permissions = $is_superadmin ? array_keys($this->availablePermissions) : ($request->permissions ?? []);

        // Level otomatis berdasarkan Role, atau input manual jika diberikan
        $level = $request->level;
        if ($is_superadmin) {
            $level = 1;
        } elseif ($role) {
            $level = $this->resolveRoleLevel($request->role, $role, (int) $request->level);
        }

        Admin::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role ? $role->id : null,
            'role' => $request->role, // simpan slug sebagai fallback
            'is_superadmin' => $is_superadmin,
            'permissions' => $permissions,
            'level' => $level,
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin baru berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:admins,username,' . $admin->id,
            'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:8',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'level' => 'required|integer|min:1',
        ]);

        $role = Role::where('slug', $request->role)->first();
        $is_superadmin = $request->role === 'super_admin';
        $permissions = $is_superadmin ? array_keys($this->availablePermissions) : ($request->permissions ?? []);

        // Level otomatis berdasarkan Role, atau input manual jika diberikan
        $level = $request->level;
        if ($is_superadmin) {
            $level = 1;
        } elseif ($role) {
            $level = $this->resolveRoleLevel($request->role, $role, (int) $request->level);
        }

        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $role ? $role->id : null,
            'role' => $request->role,
            'is_superadmin' => $is_superadmin,
            'permissions' => $permissions,
            'level' => $level,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return redirect()->route('admin.admins.index')->with('success', 'Data admin berhasil diperbarui.');
    }

    public function destroy(Admin $admin)
    {
        if (auth('admin')->id() === $admin->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', 'Admin berhasil dihapus.');
    }

    private function rolesTableHasLevelColumn(): bool
    {
        static $hasLevelColumn;

        if ($hasLevelColumn === null) {
            $hasLevelColumn = Schema::hasColumn('roles', 'level');
        }

        return $hasLevelColumn;
    }

    private function resolveRoleLevel(string $roleSlug, ?Role $role = null, int $fallback = 2): int
    {
        if ($this->rolesTableHasLevelColumn() && $role && $role->level !== null) {
            return (int) $role->level;
        }

        return $this->defaultRoles()[$roleSlug]['level'] ?? $fallback;
    }
}
