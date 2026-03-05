<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact', 'like', "%{$search}%")
                  ->orWhere('origin', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            if ($request->status === 'blocked') {
                $query->where('is_blocked', true);
            } elseif ($request->status === 'active') {
                $query->where('is_blocked', false);
            }
        }

        $customers = $query->with(['conversations' => function($q) {
            $q->latest(); // Default: hanya yang non-trashed
        }])->latest()->paginate(15)->withQueryString();

        // Map customers to include their current active status
        $customers->getCollection()->transform(function($user) {
            $activeConv = $user->conversations->whereIn('status', ['pending', 'queued', 'active'])->first();
            $user->current_status = $activeConv ? $activeConv->status : 'no_session';
            return $user;
        });

        return view('admin.customers.index', compact('customers'));
    }

    public function update(Request $request, User $customer)
    {
        $request->validate([
            'is_blocked' => 'required|boolean',
        ]);

        $customer->update(['is_blocked' => $request->is_blocked]);

        $status = $request->is_blocked ? 'diblokir' : 'diaktifkan kembali';
        return redirect()->route('admin.customers.index')->with('success', "Akun pelanggan berhasil $status.");
    }

    public function destroy(User $customer)
    {
        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Data pelanggan berhasil dihapus secara permanen.');
    }
}
