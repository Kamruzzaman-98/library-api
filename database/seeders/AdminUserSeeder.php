<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@library.com'],
            [
                'name'     => 'Library Admin',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
                'phone'    => '01700000000',
                'address'  => 'Library Office',
                'status'   => 'active',
            ]
        );
    }
}
