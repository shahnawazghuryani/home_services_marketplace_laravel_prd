<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LaunchReadinessSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'AC Repair', 'icon' => 'snowflake', 'description' => 'Cooling, installation, gas refill, and AC diagnostics.'],
            ['name' => 'Plumbing', 'icon' => 'droplet', 'description' => 'Leaks, fittings, water pressure, and bathroom plumbing work.'],
            ['name' => 'Electrician', 'icon' => 'zap', 'description' => 'Wiring, switches, breakers, lights, and urgent electrical fixes.'],
            ['name' => 'Deep Cleaning', 'icon' => 'sparkles', 'description' => 'Home, kitchen, washroom, and move-in deep cleaning services.'],
            ['name' => 'Carpentry', 'icon' => 'hammer', 'description' => 'Furniture repair, fittings, shelves, doors, and woodwork.'],
            ['name' => 'Appliance Repair', 'icon' => 'tool', 'description' => 'Washing machine, refrigerator, microwave, and small appliance repair.'],
        ];

        foreach ($categories as $item) {
            Category::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                $item
            );
        }
    }
}
