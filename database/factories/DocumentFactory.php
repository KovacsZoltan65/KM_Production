<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_type' => Item::class,
            'documentable_id' => Item::factory(),
            'document_type' => fake()->randomElement(DocumentType::cases()),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'file_path' => fake()->optional()->filePath(),
            'original_filename' => fake()->optional()->lexify('document-????.pdf'),
            'mime_type' => fake()->optional()->mimeType(),
            'file_size' => fake()->optional()->numberBetween(1000, 5000000),
            'version' => 1,
            'is_current' => true,
            'uploaded_by' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
