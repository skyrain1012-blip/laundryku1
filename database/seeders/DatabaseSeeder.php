<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@laundryku.com'],
            [
                'name' => 'Administrator LaundryKu',
                'password' => Hash::make('Admin12345'),
                'role' => 'admin',
                'phone' => '081234567890',
                'address' => 'Kantor LaundryKu',
            ]
        );
    }
}