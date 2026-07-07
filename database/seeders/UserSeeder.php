<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Rodrigo',
            'email' => 'rodrigo@mail.com',
            'password' => '12345678',
        ]);
    }
}
