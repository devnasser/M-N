<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Shop;
use App\Models\Driver;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyers = User::role('buyer')->get();
        $shops = Shop::all();
        $drivers = Driver::all();
        $products = Product::all();
        
        for ($i = 1; $i <= 500; $i++) {
            $buyer = $buyers->random();
            $shop = $shops->random();
            $driver = $drivers->random();
            $orderProducts = $products->random(rand(1, 3));
            
            $subtotal = 0;
            $orderItems = [];
            
            foreach ($orderProducts as $product) {
                $quantity = rand(1, 3);
                $price = $product->getFinalPrice();
                $total = $price * $quantity;
                $subtotal += $total;
                
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $total,
                ];
            }
            
            $taxAmount = $subtotal * 0.15;
            $shippingAmount = rand(20, 50);
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;
            
            $order = Order::create([
                'order_number' => 'ORD' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'user_id' => $buyer->id,
                'shop_id' => $shop->id,
                'driver_id' => $driver->id,
                'status' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered'][array_rand(['pending', 'confirmed', 'processing', 'shipped', 'delivered'])],
                'payment_status' => ['pending', 'paid'][array_rand(['pending', 'paid'])],
                'payment_method' => ['cash', 'card', 'mada', 'stc_pay'][array_rand(['cash', 'card', 'mada', 'stc_pay'])],
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => 'SAR',
                'shipping_address' => [
                    'street' => 'شارع ' . rand(1, 100),
                    'city' => $buyer->city,
                    'region' => $buyer->region,
                    'postal_code' => rand(10000, 99999),
                ],
                'billing_address' => [
                    'street' => 'شارع ' . rand(1, 100),
                    'city' => $buyer->city,
                    'region' => $buyer->region,
                    'postal_code' => rand(10000, 99999),
                ],
                'shipping_method' => ['local', 'express', 'standard'][array_rand(['local', 'express', 'standard'])],
                'tracking_number' => 'TRK' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'estimated_delivery' => now()->addDays(rand(1, 7)),
                'delivered_at' => rand(0, 1) ? now()->subDays(rand(1, 30)) : null,
                'notes' => rand(0, 1) ? 'ملاحظات خاصة بالطلب' : null,
            ]);
            
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['total'],
                ]);
            }
        }
    }
} 