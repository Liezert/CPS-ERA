<?php

namespace Database\Factories;

use App\Models\Division;
use App\Models\KnowledgeDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KnowledgeDocument>
 */
class KnowledgeDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'division_id' => Division::inRandomOrder()->first()?->id ?? Division::firstOrCreate(['name' => 'General'])->id,
            'type' => fake()->randomElement(['dokumen', 'video', 'presentasi', 'lesson_learned', 'sop', 'link']),
            'file_url' => null,
            'external_link' => null,
            'description' => fake()->paragraph(),
            'source_ba_id' => null,
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'status' => 'published',
        ];
    }
}
