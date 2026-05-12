<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\User;

class UserProductController extends Controller
{
    public function showCategoryProducts(Request $request, $slug)
    {
        $category = Category::where('slug', $slug)->firstOrFail();
        $products = $category->products;

        $isAuthenticated = false;
        $token = $request->cookie('guest_chat_token');
        if ($token && User::where('email', $token)->exists()) {
            $isAuthenticated = true;
        }

        return view('user.category-products', compact('category', 'products', 'isAuthenticated'));
    }
}
