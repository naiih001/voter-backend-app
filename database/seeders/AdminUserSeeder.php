<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_PASSWORD');
        if (! $password) {
            throw new RuntimeException('ADMIN_PASSWORD must be set before running AdminUserSeeder.');
        }

        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'isaachayab0@gmail.com')],
            [
                'name' => env('ADMIN_NAME', 'Isaac Hayab'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'matric_number' => null,
                'is_eligible' => false,
            ],
        );
    }
}
