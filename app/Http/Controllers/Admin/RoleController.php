<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $admins = Admin::orderBy('is_superadmin', 'desc')->latest()->get();
        $permissions = $this->availablePermissions;
        return view('admin.roles.index', compact('admins', 'permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:admins',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => 'required|string|min:8',
            'permissions' => 'nullable|array',
            'is_superadmin' => 'nullable|boolean',
        ]);

        Admin::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin', // default for backward compat
            'is_superadmin' => $request->is_superadmin ?? false,
            'permissions' => $request->permissions ?? [],
        ]);

        return redirect()->route('admin.roles.index')->with('success', 'Admin baru berhasil ditambahkan.');
    }

    public function update(Request $request, Admin $role)
    {
        $admin = $role; // alias
        $request->validate([
            'username' => 'required|string|max:255|unique:admins,username,' . $admin->id,
            'email' => 'required|string|email|max:255|unique:admins,email,' . $admin->id,
            'password' => 'nullable|string|min:8',
            'permissions' => 'nullable|array',
            'is_superadmin' => 'nullable|boolean',
        ]);

        $data = [
            'username' => $request->username,
            'email' => $request->email,
            'is_superadmin' => $request->is_superadmin ?? false,
            'permissions' => $request->permissions ?? [],
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        // Jika mengupdate diri sendiri dan mencabut hak akses role, maka mungkin butuh redirect ke dashboard
        if (auth('admin')->id() === $admin->id && !$admin->hasPermission('manage_roles')) {
            return redirect()->route('admin.dashboard')->with('success', 'Akses Anda telah diperbarui.');
        }

        return redirect()->route('admin.roles.index')->with('success', 'Data admin berhasil diperbarui.');
    }

    public function destroy(Admin $role)
    {
        $admin = $role; // alias
        if (auth('admin')->id() === $admin->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $admin->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Admin berhasil dihapus.');
    }
}
