<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    private const REGULARS = [
        ['name' => 'Thomas Verghese', 'email' => 'thomas@example.com'],
        ['name' => 'Divya Ramesh', 'email' => 'divya@example.com'],
    ];

    public function run(): void
    {
        foreach (self::REGULARS as $regular) {
            Customer::updateOrCreate(['email' => $regular['email']], $regular);
        }

        Customer::factory()->count(8)->create();
    }
}
