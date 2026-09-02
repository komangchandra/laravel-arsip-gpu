<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'file_path' => 'documents/test.pdf',
            'status' => DocumentStatus::Uploaded,
            'created_by' => User::factory(),
        ];
    }
}
