<?php

namespace Database\Factories;

use App\Models\ClinicBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicBranch>
 */
class ClinicBranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locations = ['التجمع الخامس', 'الشيخ زايد', 'مصر الجديدة', 'المعادي', 'وسط البلد'];

        return [
            'name' => 'فرع '.fake()->unique()->randomElement($locations),
            'address' => fake()->address(),
            'phone' => '012'.fake()->numerify('########'),
            'working_hours' => 'السبت - الأربعاء: 4م - 9م',
            'google_maps_url' => 'https://maps.google.com/?q='.fake()->latitude().','.fake()->longitude(),
            'google_maps_embed' => '<iframe src="https://www.google.com/maps/embed?pb=..."></iframe>',
            'is_active' => true,
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
