<?php

namespace Database\Factories;

use App\Models\BackupJob;
use App\Models\BackupSource;
use App\Models\BackupStorageDestination;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupJobFactory extends Factory
{
    protected $model = BackupJob::class;

    public function definition(): array
    {
        $scheduleType = $this->faker->randomElement(['manual', 'hourly', 'daily', 'weekly', 'monthly']);

        return [
            'name' => 'Backup ' . $this->faker->words(2, true),
            'backup_source_id' => BackupSource::factory(),
            'backup_storage_destination_id' => BackupStorageDestination::factory(),
            'schedule_type' => $scheduleType,
            'schedule_cron' => null,
            'schedule_time' => in_array($scheduleType, ['daily', 'weekly', 'monthly']) ? '03:00' : null,
            'schedule_day_of_week' => $scheduleType === 'weekly' ? $this->faker->numberBetween(0, 6) : null,
            'schedule_day_of_month' => $scheduleType === 'monthly' ? $this->faker->numberBetween(1, 28) : null,
            'retention_count' => $this->faker->randomElement([3, 5, 7, 14, 30]),
            'compression' => $this->faker->randomElement(['none', 'gzip', 'zip']),
            'notify_on_success' => false,
            'notify_on_failure' => true,
            'notification_email' => $this->faker->safeEmail(),
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => now()->addHours($this->faker->numberBetween(1, 24)),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn () => [
            'schedule_type' => 'manual',
            'schedule_cron' => null,
            'schedule_time' => null,
            'schedule_day_of_week' => null,
            'schedule_day_of_month' => null,
            'next_run_at' => null,
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn () => [
            'schedule_type' => 'daily',
            'schedule_time' => '03:00',
            'schedule_day_of_week' => null,
            'schedule_day_of_month' => null,
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn () => [
            'schedule_type' => 'weekly',
            'schedule_time' => '02:00',
            'schedule_day_of_week' => 1, // Monday
            'schedule_day_of_month' => null,
        ]);
    }

    public function monthly(): static
    {
        return $this->state(fn () => [
            'schedule_type' => 'monthly',
            'schedule_time' => '01:00',
            'schedule_day_of_week' => null,
            'schedule_day_of_month' => 1,
        ]);
    }

    public function custom(): static
    {
        return $this->state(fn () => [
            'schedule_type' => 'custom',
            'schedule_cron' => '0 */6 * * *',
            'schedule_time' => null,
            'schedule_day_of_week' => null,
            'schedule_day_of_month' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
