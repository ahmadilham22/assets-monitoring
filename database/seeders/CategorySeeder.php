<?php

namespace Database\Seeders;

use App\Models\DataMaster\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['kode_kategori' => 'L123', 'nama_kategori' => 'Laptop'],
            ['kode_kategori' => 'P123', 'nama_kategori' => 'Printer'],
            ['kode_kategori' => 'A123', 'nama_kategori' => 'Ac'],
            ['kode_kategori' => 'C123', 'nama_kategori' => 'CCTV'],
            ['kode_kategori' => 'K123', 'nama_kategori' => 'Keyboard'],
            ['kode_kategori' => 'M123', 'nama_kategori' => 'Mouse'],
            ['kode_kategori' => 'C234', 'nama_kategori' => 'Camera'],
            ['kode_kategori' => 'P234', 'nama_kategori' => 'Proyektor'],
            ['kode_kategori' => 'A234', 'nama_kategori' => 'All In One'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
