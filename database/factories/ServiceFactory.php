<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $services = [
            'علاج سرعة القذف',
            'تفتيت حصوات الكلى',
            'علاج البروستاتا بالليزر',
            'جراحة الفتق الإربي',
            'علاج المثانة العصبية',
            'استئصال أورام الكلى',
            'جراحة المناظير المسالك',
            'علاج السلس البولي',
            'جراحة الحالب ميكروسكوبياً',
            'علاج الخصية المعلقة',
        ];

        return [
            'name' => fake()->unique()->randomElement($services),
            'description' => fake()->realText(200),
            'is_active' => true,
            'order' => fake()->numberBetween(10, 100),
        ];
    }
}
