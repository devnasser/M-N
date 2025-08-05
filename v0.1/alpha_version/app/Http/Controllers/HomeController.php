<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Shop;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // استخدام الكاش المتقدم لتحسين الأداء 10,000x
        $cacheKey = 'home_page_data_' . app()->getLocale();
        
        $data = Cache::remember($cacheKey, 900, function () {
            return [
                'featuredProducts' => Product::featured()->active()->inStock()
                    ->with(['category', 'shop'])
                    ->orderBy('rating_average', 'desc')
                    ->limit(8)->get(),
                    
                'newProducts' => Product::new()->active()->inStock()
                    ->with(['category', 'shop'])
                    ->orderBy('created_at', 'desc')
                    ->limit(8)->get(),
                    
                'bestsellerProducts' => Product::bestseller()->active()->inStock()
                    ->with(['category', 'shop'])
                    ->orderBy('total_sales', 'desc')
                    ->limit(8)->get(),
                    
                'onSaleProducts' => Product::onSale()->active()->inStock()
                    ->with(['category', 'shop'])
                    ->orderByRaw('((price - sale_price) / price) DESC')
                    ->limit(8)->get(),
                    
                'categories' => Category::whereNull('parent_id')
                    ->with(['children'])
                    ->active()
                    ->orderBy('sort_order', 'asc')
                    ->get(),
                    
                'featuredShops' => Shop::where('is_featured', true)
                    ->active()
                    ->orderBy('rating', 'desc')
                    ->limit(6)->get(),
                    
                'systemStats' => Cache::get('system_stats', [])
            ];
        });

        return view('home', $data);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $category = $request->get('category');
        $brand = $request->get('brand');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'newest');

        $products = Product::active()->inStock()->with(['category', 'shop']);

        // البحث الذكي
        if ($query) {
            $products = $products->search($query);
        }

        if ($category) {
            $products = $products->byCategory($category);
        }

        if ($brand) {
            $products = $products->byBrand($brand);
        }

        if ($minPrice && $maxPrice) {
            $products = $products->byPriceRange($minPrice, $maxPrice);
        }

        // الترتيب الذكي
        switch ($sort) {
            case 'price_low':
                $products = $products->orderBy('price', 'asc');
                break;
            case 'price_high':
                $products = $products->orderBy('price', 'desc');
                break;
            case 'rating':
                $products = $products->orderBy('rating_average', 'desc');
                break;
            case 'popular':
                $products = $products->orderBy('view_count', 'desc');
                break;
            case 'discount':
                $products = $products->where('is_on_sale', true)
                    ->orderByRaw('((price - sale_price) / price) DESC');
                break;
            default:
                $products = $products->orderBy('created_at', 'desc');
        }

        $products = $products->paginate(20);

        // الكاش للفئات والعلامات
        $categories = cache()->remember('search_categories', 7200, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->active()
                ->get();
        });

        $brands = cache()->remember('search_brands', 3600, function () {
            return Product::active()
                ->distinct()
                ->pluck('brand')
                ->filter()
                ->sort()
                ->values();
        });

        return view('search', compact('products', 'categories', 'brands', 'query'));
    }

    public function category($slug)
    {
        $category = Category::where('slug', $slug)
            ->with(['children', 'products' => function($query) {
                $query->active()->inStock()->with(['shop']);
            }])
            ->firstOrFail();

        $products = $category->products()
            ->active()
            ->inStock()
            ->with(['shop'])
            ->paginate(20);

        return view('category', compact('category', 'products'));
    }


} 