<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class FeaturedProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::where('is_featured', true)->get();

        $productData = [
            'Pertanian' => [
                ['name' => 'Agrosawit', 'image' => 'images/pertanian/agrosawit.jpg'],
            ],
            'Otomotif' => [
                ['name' => 'Eco Diesel', 'image' => 'images/otomotif/ecodiesel.jpeg'],
                ['name' => 'Eco Racing', 'image' => 'images/otomotif/ecoracing.jpeg'],
                ['name' => 'Eco Racing Nano Tech', 'image' => 'images/otomotif/EcoRacingNanoTechatauNanoOil.jpg'],
            ],
            'Kesehatan' => [
                ['name' => 'B-MAXX', 'image' => 'images/kesehatan/B-MAXX.jpg'],
                ['name' => 'ECO VICO', 'image' => 'images/kesehatan/ECO-VICO.jpg'],
                ['name' => 'HABSPRO', 'image' => 'images/kesehatan/HABSPRO.jpg'],
                ['name' => 'ECOMAXX Coffee', 'image' => 'images/produk-minuman/ECOMAXXCoffee.jpg'],
                ['name' => 'ECONAXX Coffee', 'image' => 'images/produk-minuman/ECONAXXCoffee.jpg'],
                ['name' => 'Evitgo 100', 'image' => 'images/produk-minuman/Evitgo100.jpg'],
            ],
            'Kecantikan' => [
                ['name' => 'LVN Day and Night Cream', 'image' => 'images/kecantikan/LVN-Day-and-Night-Cream.jpeg'],
                ['name' => 'LVN Lipcream', 'image' => 'images/kecantikan/lvn-lipcream.jpg'],
                ['name' => 'LVN Serum', 'image' => 'images/kecantikan/lvn-serum.jpg'],
                ['name' => 'RED ONE BOOST', 'image' => 'images/kecantikan/RED-ONE-BOOST.jpg'],
                ['name' => 'LVN Crystal V', 'image' => 'images/produk-pembersih/LVN-CRYSTAL-V-Q.jpg'],
                ['name' => 'LVN Hand Moist', 'image' => 'images/produk-pembersih/LVN-Hand-Moist.jpg'],
                ['name' => 'LVN Hygiene Spray', 'image' => 'images/produk-pembersih/LVN-HYGIENE-SPRAY-FOR-MAN.jpg'],
            ],
        ];

        foreach ($categories as $category) {
            $products = $productData[$category->name] ?? [];
            
            foreach ($products as $data) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $data['name'],
                    'price' => rand(150000, 500000),
                    'description' => "Produk berkualitas {$data['name']} dari kategori {$category->name}. Solusi terbaik untuk kebutuhan Anda.",
                    'image' => $data['image'],
                ]);
            }
        }
    }
}
