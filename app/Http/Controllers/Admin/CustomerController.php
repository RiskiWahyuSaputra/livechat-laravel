<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

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

        $customers = $query->latest()->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'is_blocked' => 'required|boolean',
        ]);

        $customer->update(['is_blocked' => $request->is_blocked]);

        $status = $request->is_blocked ? 'diblokir' : 'diaktifkan kembali';
        return redirect()->route('admin.customers.index')->with('success', "Akun pelanggan berhasil $status.");
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Data pelanggan berhasil dihapus secara permanen.');
    }
}
