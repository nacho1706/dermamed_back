<?php

namespace Database\Factories;

use App\Models\MedicalRecord;
use App\Models\MedicalRecordAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecordAttachment>
 */
class MedicalRecordAttachmentFactory extends Factory
{
    protected $model = MedicalRecordAttachment::class;

    public function definition(): array
    {
        return [
            'medical_record_id' => MedicalRecord::factory(),
            'path' => 'private/medical_records/1/'.$this->faker->uuid().'.jpg',
            'original_name' => $this->faker->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(10000, 5000000),
        ];
    }
}
