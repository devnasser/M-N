<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, $productId)
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $isFavorited = Favorite::toggleFavorite($userId, $productId);
        
        return response()->json([
            'success' => true,
            'isFavorited' => $isFavorited,
            'message' => $isFavorited ? 'تم إضافة المنتج للمفضلة' : 'تم إزالة المنتج من المفضلة'
        ]);
    }

    public function index()
    {
        $user = auth()->user();
        $favorites = \App\Models\Product::whereHas('favorites', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['category', 'shop'])->paginate(20);

        return view('buyer.favorites', compact('favorites'));
    }

    public function remove(Request $request, $productId)
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return response()->json(['error' => 'يجب تسجيل الدخول أولاً'], 401);
        }

        $favorite = Favorite::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['success' => true, 'message' => 'تم إزالة المنتج من المفضلة']);
        }

        return response()->json(['error' => 'المنتج غير موجود في المفضلة'], 404);
    }
} 