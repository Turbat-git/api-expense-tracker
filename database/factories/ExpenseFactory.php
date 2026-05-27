<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();

        return [
            'amount' => $this->faker->randomFloat(2, 1, 1000),
            'description' => $this->faker->sentence(),
            'user_id' => $user->id,

            'category_id' => Category::factory()->create([
                'user_id' => $user->id,
            ])->id,
        ];
    }
}
