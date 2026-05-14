<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expense::create([
            'amount' => 15.50,
            'description' => 'Lunch',
            'category_id' => 1,
            'user_id' => 1,
        ]);
    }
}
