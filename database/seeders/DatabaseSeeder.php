<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make(['name' => 'Test User'])->toArray()
        );

        $cat = Category::firstOrCreate(['name' => 'Electronics']);

        $items = [
            ['Wireless Headphones', 1299.99, 25],
            ['Smart Watch', 2499.00, 15],
            ['Desk Lamp', 449.00, 30],
            ['Coffee Maker', 1599.00, 0],
        ];

        foreach ($items as [$name, $price, $stock]) {
            Product::firstOrCreate(['name' => $name], [
                'description' => 'Sample product',
                'price'       => $price,
                'stock'       => $stock,
                'category_id' => $cat->id,
                'image_url'   => null,
            ]);
        }
    }
}