<?php

namespace Database\Factories;

use App\Models\BackupJob;
use App\Models\BackupLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupLogFactory extends Factory
{
    protected $model = BackupLog::class;

    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-7 days', 'now');
        $duration = $this->faker->numberBetween(5, 600);

        return [
            'backup_job_id' => BackupJob::factory(),
            'status' => 'success',
            'started_at' => $startedAt,
            'finished_at' => (clone $startedAt)->modify("+{$duration} seconds"),
            'duration_seconds' => $duration,
            'file_name' => 'backup_' . date('Ymd_His', $startedAt->getTimestamp()) . '.sql.gz',
            'file_size_bytes' => $this->faker->numberBetween(1024, 1024 * 1024 * 500),
            'storage_path' => '/backups/' . date('Y/m', $startedAt->getTimestamp()),
            'error_message' => null,
            'meta' => ['tables_dumped' => $this->faker->numberBetween(5, 50)],
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'finished_at' => null,
            'duration_seconds' => null,
            'file_name' => null,
            'file_size_bytes' => null,
            'storage_path' => null,
        ]);
    }

    public function running(): static
    {
        return $this->state(fn () => [
            'status' => 'running',
            'finished_at' => null,
            'duration_seconds' => null,
            'file_name' => null,
            'file_size_bytes' => null,
            'storage_path' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn () => ['status' => 'success']);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_message' => $this->faker->sentence(),
            'file_name' => null,
            'file_size_bytes' => null,
            'storage_path' => null,
        ]);
    }

    public function partial(): static
    {
        return $this->state(fn () => [
            'status' => 'partial',
            'error_message' => 'Some tables could not be dumped: ' . $this->faker->word(),
        ]);
    }
}
