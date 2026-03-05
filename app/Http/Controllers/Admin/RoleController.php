<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    // Daftar Permissions yang tersedia di sistem
    private $availablePermissions = [
        'view_chat' => 'Akses Live Chat Workspace',
        'view_history' => 'Lihat Riwayat & Arsip Chat',
        'manage_quick_replies' => 'Kelola Balasan Cepat',
        'manage_customers' => 'Kelola Data Pelanggan',
        'manage_roles' => 'Kelola Role & Akses (Superadmin)',
    ];

    public function index()
    {
        // Auto-sync data (one-time logic)
        $this->syncLegacyRoles();

        $admins = Admin::with('roleModel')->orderBy('is_superadmin', 'desc')->latest()->get();
        $permissions = $this->availablePermissions;
        $rolesList = Role::all();
        return view('admin.roles.admins', compact('admins', 'permissions', 'rolesList'));
    }

    private function syncLegacyRoles()
    {
        $defaultDescriptions = [
            'super_admin' => 'Akses penuh ke seluruh sistem, modul, dan pengaturan keamanan.',
            'agent' => 'Menangani pesan pelanggan dan mengelola percakapan di Live Chat Workspace.',
        ];

        // Pastikan role default ada dengan deskripsi
        foreach ($defaultDescriptions as $slug => $desc) {
            Role::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::title(str_replace('_', ' ', $slug)),
                    'description' => $desc
                ]
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

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:admins',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'role' => 'required|string', // Ini sekarang slug role
            'permissions' => 'nullable|array',
        ]);

        $role = Role::where('slug', $request->role)->first();
        $is_superadmin = $request->role === 'super_admin';
        $permissions = $is_superadmin ? array_keys($this->availablePermissions) : ($request->permissions ?? []);

        Admin::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $role ? $role->id : null,
            'role' => $request->role, // simpan slug sebagai fallback
            'is_superadmin' => $is_superadmin,
            'permissions' => $permissions,
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
        ]);

        $role = Role::where('slug', $request->role)->first();
        $is_superadmin = $request->role === 'super_admin';
        $permissions = $is_superadmin ? array_keys($this->availablePermissions) : ($request->permissions ?? []);

        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'role_id' => $role ? $role->id : null,
            'role' => $request->role,
            'is_superadmin' => $is_superadmin,
            'permissions' => $permissions,
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
}
