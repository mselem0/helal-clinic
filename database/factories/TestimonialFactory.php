<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = [
            'أستاذ أحمد', 'الحاج محمود', 'المهندس علي', 'المستشار سيد', 'الأستاذ إبراهيم',
            'دكتور مصطفى', 'أستاذ خالد', 'أستاذ وليد', 'أستاذ ياسر', 'المحاسب تامر',
        ];
        $initials = ['أ', 'م', 'ع', 'م', 'س', 'إ', 'م', 'خ', 'و', 'ي'];
        $colors = ['cyan', 'navy', 'green', 'purple'];

        return [
            'patient_name' => fake()->randomElement($names).' '.fake()->randomElement($initials).'.',
            'patient_initial' => fake()->randomElement($initials),
            'avatar_color' => fake()->randomElement($colors),
            'review_text' => 'تجربة ممتازة مع الدكتور محمد هلال في عيادة المهندسين. '.fake()->realText(100),
            'rating' => fake()->numberBetween(4, 5),
            'is_active' => true,
            'order' => fake()->numberBetween(0, 100),
        ];
    }
}
