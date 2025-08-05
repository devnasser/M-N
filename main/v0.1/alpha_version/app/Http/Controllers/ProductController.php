<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'shop'])
            ->where('is_active', true)
            ->paginate(20);
        
        $categories = Category::where('is_active', true)->get();
        
        return view('products.index', compact('products', 'categories'));
    }

    public function show($slug)
    {
        $product = Product::with(['category', 'shop', 'reviews.user'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
        
        // زيادة عدد المشاهدات
        $product->incrementViewCount();
        
        // الحصول على المنتجات ذات الصلة المحسنة
        $relatedProducts = $product->getRelatedProducts(4);
        
        // الحصول على المنتجات المماثلة
        $similarProducts = $product->getSimilarProducts(4);
        
        return view('products.show', compact('product', 'relatedProducts', 'similarProducts'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $shops = Shop::where('is_active', true)->get();
        
        return view('products.create', compact('categories', 'shops'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'sku' => 'required|string|unique:products,sku',
        ]);

        $product = Product::create($request->except('images'));

        // معالجة الصور
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // حفظ الصورة الأصلية
                $image->storeAs('public/products', $filename);
                
                // إنشاء نسخة مصغرة
                $thumbnail = Image::make($image);
                $thumbnail->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $thumbnail->save(storage_path('app/public/products/thumbnails/' . $filename));
                
                $images[] = $filename;
            }
            
            $product->update(['images' => $images]);
        }

        return redirect()->route('products.show', $product->slug)
            ->with('success', 'تم إنشاء المنتج بنجاح');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        $shops = Shop::where('is_active', true)->get();
        
        return view('products.edit', compact('product', 'categories', 'shops'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'required|string',
            'description_en' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'shop_id' => 'required|exists:shops,id',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'sku' => 'required|string|unique:products,sku,' . $id,
        ]);

        $product->update($request->except('images'));

        // معالجة الصور الجديدة
        if ($request->hasFile('images')) {
            $currentImages = $product->images ?? [];
            
            foreach ($request->file('images') as $image) {
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                
                // حفظ الصورة الأصلية
                $image->storeAs('public/products', $filename);
                
                // إنشاء نسخة مصغرة
                $thumbnail = Image::make($image);
                $thumbnail->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $thumbnail->save(storage_path('app/public/products/thumbnails/' . $filename));
                
                $currentImages[] = $filename;
            }
            
            $product->update(['images' => $currentImages]);
        }

        return redirect()->route('products.show', $product->slug)
            ->with('success', 'تم تحديث المنتج بنجاح');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // حذف الصور
        if ($product->images) {
            foreach ($product->images as $image) {
                Storage::delete('public/products/' . $image);
                Storage::delete('public/products/thumbnails/' . $image);
            }
        }
        
        $product->delete();
        
        return redirect()->route('products.index')
            ->with('success', 'تم حذف المنتج بنجاح');
    }

    public function deleteImage(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $imageIndex = $request->input('image_index');
        
        if ($product->images && isset($product->images[$imageIndex])) {
            $imageToDelete = $product->images[$imageIndex];
            
            // حذف الصورة من التخزين
            Storage::delete('public/products/' . $imageToDelete);
            Storage::delete('public/products/thumbnails/' . $imageToDelete);
            
            // حذف الصورة من المصفوفة
            $images = $product->images;
            unset($images[$imageIndex]);
            $images = array_values($images); // إعادة ترتيب المصفوفة
            
            $product->update(['images' => $images]);
            
            return response()->json(['success' => true]);
        }
        
        return response()->json(['success' => false], 400);
    }
} 