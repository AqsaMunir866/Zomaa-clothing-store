<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->sentence(3),
            'kicker' => fake()->word(),
            'text' => fake()->sentence(),
            'image_url' => null,
            'link_label' => 'Shop Now',
            'link_url' => '/',
            'position' => 'hero',
            'sort_order' => 0,
            'active' => true,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
