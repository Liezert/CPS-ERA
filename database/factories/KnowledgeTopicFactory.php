<?php

namespace Database\Factories;

use App\Models\KnowledgeTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeTopic>
 */
class KnowledgeTopicFactory extends Factory
{
    protected $model = KnowledgeTopic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }
}
