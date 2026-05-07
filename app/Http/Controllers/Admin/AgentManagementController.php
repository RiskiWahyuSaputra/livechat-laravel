<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Division;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgentManagementController extends Controller
{
    public function index()
    {
        $divisions = Division::with(['supervisor', 'agents'])->orderBy('name')->get();
        $agents    = Admin::where('role', '!=', 'super_admin')->orderBy('username')->get();

        return view('admin.agents.index', compact('divisions', 'agents'));
    }

    // ── Divisions CRUD ──────────────────────────────────────────

    public function storeDivision(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100|unique:divisions,name',
            'description'   => 'nullable|string|max:255',
            'supervisor_id' => 'nullable|exists:admins,id',
        ]);

        Division::create([
            'name'          => $request->name,
            'slug'          => Str::slug($request->name),
            'description'   => $request->description,
            'supervisor_id' => $request->supervisor_id ?: null,
        ]);

        return back()->with('success', "Divisi \"{$request->name}\" berhasil ditambahkan.");
    }

    public function updateDivision(Request $request, Division $division)
    {
        $request->validate([
            'name'          => 'required|string|max:100|unique:divisions,name,' . $division->id,
            'description'   => 'nullable|string|max:255',
            'supervisor_id' => 'nullable|exists:admins,id',
        ]);

        $oldSlug = $division->slug;
        $newSlug = Str::slug($request->name);

        $division->update([
            'name'          => $request->name,
            'slug'          => $newSlug,
            'description'   => $request->description,
            'supervisor_id' => $request->supervisor_id ?: null,
        ]);

        // Update agents that had the old slug
        if ($oldSlug !== $newSlug) {
            Admin::where('division', $oldSlug)->update(['division' => $newSlug]);
        }

        return back()->with('success', "Divisi \"{$request->name}\" berhasil diperbarui.");
    }

    public function destroyDivision(Division $division)
    {
        // Unassign agents from this division
        Admin::where('division', $division->slug)->update(['division' => null]);
        $division->delete();

        return back()->with('success', "Divisi \"{$division->name}\" berhasil dihapus.");
    }

    // ── Agent assignment ────────────────────────────────────────

    public function updateAgentDivision(Request $request, Admin $admin)
    {
        $request->validate([
            'division' => 'nullable|exists:divisions,slug',
        ]);

        $admin->update(['division' => $request->division ?: null]);

        return back()->with('success', "Divisi {$admin->username} berhasil diperbarui.");
    }
}
