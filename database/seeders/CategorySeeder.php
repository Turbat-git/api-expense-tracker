<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::create([
            'name' => 'Food',
            'user_id' => 1,
        ]);

        Category::create([
            'name' => 'Transport',
            'user_id' => 1,
        ]);

        Category::create([
            'name' => 'Entertainment',
            'user_id' => 1,
        ]);
    }
}
