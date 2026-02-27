<?php

namespace Database\Factories;

use App\Models\BackupLog;
use App\Models\RestoreLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RestoreLogFactory extends Factory
{
    protected $model = RestoreLog::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['pending', 'running', 'success', 'failed']);
        $startedAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $finishedAt = $status === 'success' || $status === 'failed'
            ? (clone $startedAt)->modify('+' . rand(10, 600) . ' seconds')
            : null;

        return [
            'backup_log_id' => BackupLog::factory(),
            'user_id' => User::factory(),
            'restore_type' => $this->faker->randomElement(['full', 'db_only', 'files_only']),
            'status' => $status,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'duration_seconds' => $finishedAt
                ? $finishedAt->getTimestamp() - $startedAt->getTimestamp()
                : null,
            'restored_db_name' => $status === 'success' ? $this->faker->word() . '_restored' : null,
            'restored_path' => $status === 'success' ? '/data/' . $this->faker->word() . '_restored' : null,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'meta' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'finished_at' => now(),
            'duration_seconds' => rand(10, 300),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'finished_at' => now(),
            'duration_seconds' => rand(5, 60),
            'error_message' => 'Test restore failure',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'finished_at' => null,
            'duration_seconds' => null,
        ]);
    }
}
