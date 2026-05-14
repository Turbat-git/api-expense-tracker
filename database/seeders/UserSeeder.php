<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Client user
        $client = User::create([
            'given_name' => 'Client',
            'family_name' => 'User',
            'email' => 'client@example.com',
            'password' => 'password',
        ]);

        $client->assignRole('client');

        // Admin user
        $admin = User::create([
            'given_name' => 'Admin',
            'family_name' => 'User',
            'email' => 'admin@example.com',
            'password' => 'password', // auto-hashed by your model casts
        ]);

        $admin->assignRole('admin');
    }
}
