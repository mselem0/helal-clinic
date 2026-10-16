<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Whatsapp\Models\WhatsappLog;
use Modules\Whatsapp\Models\WhatsappTemplate;

/**
 * @extends Factory<WhatsappLog>
 */
class WhatsappLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $egyptianNames = [
            'أحمد مدحت', 'سامح جاد', 'هيثم شاكر', 'عمر كمال', 'حسن شاكوش', 'حمو بيكا',
            'مدحت صالح', 'علي الحجار', 'محمد منير', 'عمرو دياب', 'تامر حسني', 'شيرين عبد الوهاب',
            'أنغام محمد', 'أصالة نصري', 'رامي جمال', 'بهاء سلطان', 'حمزة نمرة', 'ويجز',
        ];

        return [
            'user_id' => User::first()?->id ?? User::factory(),
            'patient_name' => fake()->randomElement($egyptianNames),
            'patient_phone' => '201'.fake()->randomElement(['0', '1', '2', '5']).fake()->numerify('########'),
            'message_content' => fake()->realText(100),
            'source' => fake()->randomElement(['booking_form', 'contact_button', 'chat_widget', 'manual']),
            'booking_id' => null, // Will be set in seeder if needed
            'template_id' => WhatsappTemplate::inRandomOrder()->first()?->id,
            'status' => fake()->randomElement(['new', 'contacted', 'completed', 'archived']),
            'admin_note' => fake()->optional()->sentence(),
            'is_read' => fake()->boolean(),
        ];
    }
}
