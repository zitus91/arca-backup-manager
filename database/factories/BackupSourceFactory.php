<?php

namespace Database\Factories;

use App\Models\BackupSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupSourceFactory extends Factory
{
    protected $model = BackupSource::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['mysql', 'mongodb', 'filesystem']);

        return [
            'name' => $this->faker->company() . ' ' . ucfirst($type),
            'type' => $type,
            'config' => match ($type) {
                'mysql' => $this->mysqlConfig(),
                'mongodb' => $this->mongodbConfig(),
                'filesystem' => $this->filesystemConfig(),
            },
            'is_active' => true,
        ];
    }

    public function mysql(): static
    {
        return $this->state(fn () => [
            'type' => 'mysql',
            'config' => $this->mysqlConfig(),
        ]);
    }

    public function mongodb(): static
    {
        return $this->state(fn () => [
            'type' => 'mongodb',
            'config' => $this->mongodbConfig(),
        ]);
    }

    public function filesystem(): static
    {
        return $this->state(fn () => [
            'type' => 'filesystem',
            'config' => $this->filesystemConfig(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    private function mysqlConfig(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => $this->faker->slug(2),
            'username' => 'root',
            'password' => $this->faker->password(8, 16),
            'tables' => null,
        ];
    }

    private function mongodbConfig(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 27017,
            'database' => $this->faker->slug(2),
            'username' => 'admin',
            'password' => $this->faker->password(8, 16),
            'collections' => null,
        ];
    }

    private function filesystemConfig(): array
    {
        return [
            'path' => '/var/www/' . $this->faker->slug(2),
            'exclude_patterns' => ['*.log', '*.tmp', 'node_modules'],
        ];
    }
}
