<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ImageHelper
{
    /**
     * الحصول على رابط الصورة
     */
    public static function getImageUrl($image, $type = 'original')
    {
        if (!$image) {
            return asset('images/placeholder.jpg');
        }

        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (!$image) {
            return asset('images/placeholder.jpg');
        }

        // استخدام الكاش لتحسين الأداء
        $cacheKey = "image_url_{$type}_{$image}";
        return Cache::remember($cacheKey, 3600, function () use ($image, $type) {
            if ($type === 'thumbnail') {
                return Storage::url('products/thumbnails/' . $image);
            }
            return Storage::url('products/' . $image);
        });
    }

    /**
     * الحصول على جميع روابط الصور
     */
    public static function getAllImagesUrls($images)
    {
        if (!$images || !is_array($images)) {
            return [asset('images/placeholder.jpg')];
        }

        $urls = [];
        foreach ($images as $image) {
            $urls[] = self::getImageUrl($image);
        }

        return $urls;
    }

    /**
     * الحصول على رابط الصورة المصغرة
     */
    public static function getThumbnailUrl($image)
    {
        return self::getImageUrl($image, 'thumbnail');
    }

    /**
     * التحقق من وجود الصورة
     */
    public static function imageExists($image)
    {
        if (!$image) {
            return false;
        }

        if (is_array($image)) {
            $image = $image[0] ?? null;
        }

        if (!$image) {
            return false;
        }

        return Storage::exists('products/' . $image);
    }

    /**
     * حذف الصورة
     */
    public static function deleteImage($image)
    {
        if (!$image) {
            return false;
        }

        if (is_array($image)) {
            foreach ($image as $img) {
                Storage::delete('products/' . $img);
                Storage::delete('products/thumbnails/' . $img);
                // مسح الكاش
                Cache::forget("image_url_original_{$img}");
                Cache::forget("image_url_thumbnail_{$img}");
            }
        } else {
            Storage::delete('products/' . $image);
            Storage::delete('products/thumbnails/' . $image);
            // مسح الكاش
            Cache::forget("image_url_original_{$image}");
            Cache::forget("image_url_thumbnail_{$image}");
        }

        return true;
    }

    /**
     * معالجة رفع الصورة مع تحسين الأداء
     */
    public static function uploadImage($file, $path = 'products')
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        
        // حفظ الصورة الأصلية
        $file->storeAs('public/' . $path, $filename);
        
        return $filename;
    }

    /**
     * إنشاء نسخة مصغرة محسنة
     */
    public static function createThumbnail($imagePath, $width = 300, $height = 300)
    {
        try {
            $image = \Intervention\Image\Facades\Image::make($imagePath);
            $image->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            return $image;
        } catch (\Exception $e) {
            \Log::error('Error creating thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * تحسين الصورة للويب
     */
    public static function optimizeImage($file, $quality = 85)
    {
        try {
            $image = \Intervention\Image\Facades\Image::make($file);
            $image->encode('jpg', $quality);
            
            return $image;
        } catch (\Exception $e) {
            \Log::error('Error optimizing image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * إنشاء صور متعددة الأحجام
     */
    public static function createMultipleSizes($file, $filename)
    {
        $sizes = [
            'thumb' => [150, 150],
            'small' => [300, 300],
            'medium' => [600, 600],
            'large' => [1200, 1200]
        ];

        $createdImages = [];

        foreach ($sizes as $size => $dimensions) {
            try {
                $image = \Intervention\Image\Facades\Image::make($file);
                $image->resize($dimensions[0], $dimensions[1], function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                
                $sizeFilename = "{$size}_{$filename}";
                $image->save(storage_path('app/public/products/' . $sizeFilename));
                $createdImages[$size] = $sizeFilename;
            } catch (\Exception $e) {
                \Log::error("Error creating {$size} image: " . $e->getMessage());
            }
        }

        return $createdImages;
    }
} 