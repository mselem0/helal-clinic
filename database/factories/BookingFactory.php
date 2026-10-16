<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Booking\Models\Booking;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $egyptianNames = [
            'محمد أحمد علي', 'محمود إبراهيم سيد', 'مصطفى كمال الدين', 'علاء حسني مبارك',
            'ياسر جلال عبده', 'هشام طلعت مصطفى', 'وليد سليمان حسن', 'عمرو جمال عبد الناصر',
            'إسلام محمد فوزي', 'عبد الرحمن صبحي جاد', 'طارق شوقي سليم', 'خالد عبد الجليل',
            'تامر حسني شريف', 'وائل جسار إبراهيم', 'هاني شاكر عبد العزيز', 'رامي صبري عادل',
            'كريم عبد العزيز حسن', 'أحمد السقا محمد', 'محمد رمضان علي', 'أمير كرارة فوزي',
        ];

        return [
            'user_id' => User::first()?->id ?? User::factory(),
            'patient_name' => fake()->randomElement($egyptianNames),
            'patient_phone' => '201'.fake()->randomElement(['0', '1', '2', '5']).fake()->numerify('########'),
            'booking_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'booking_time' => fake()->time('H:i'),
            'service_id' => Service::inRandomOrder()->first()?->id ?? Service::factory(),
            'patient_condition' => fake()->realText(150),
            'status' => fake()->randomElement(['new', 'confirmed', 'cancelled', 'completed']),
            'source' => fake()->randomElement(['website', 'facebook', 'instagram', 'google']),
            'medium' => 'cpc',
            'campaign' => 'egypt_promo_2026',
            'notes' => fake()->optional()->realText(100),
            'is_read' => fake()->boolean(),
            'is_alert_seen' => fake()->boolean(),
            'postponed_reason' => fake()->optional()->sentence(),
        ];
    }
}
