<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FeaturedCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Pertanian' => 'images/produk-pertanian.png',
            'Otomotif' => 'images/produk-otomotif.png',
            'Kesehatan' => 'images/produk-kesehatan.png',
            'Kecantikan' => 'images/produk-kecantikan.png',
        ];

        foreach ($categories as $name => $image) {
            Category::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                [
                    'name' => $name,
                    'icon_image' => $image,
                    'is_featured' => true
                ]
            );
        }
    }
}
