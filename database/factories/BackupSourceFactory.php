<?php

namespace Database\Factories;

use App\Models\BackupSource;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupSourceFactory extends Factory
{
    protected $model = BackupSource::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'config' => [
                'mysql' => $this->mysqlConfig(),
            ],
            'is_active' => true,
        ];
    }

    public function mysql(): static
    {
        return $this->state(fn () => [
            'config' => [
                'mysql' => $this->mysqlConfig(),
            ],
        ]);
    }

    public function mongodb(): static
    {
        return $this->state(fn () => [
            'config' => [
                'mongodb' => $this->mongodbConfig(),
            ],
        ]);
    }

    public function filesystem(): static
    {
        return $this->state(fn () => [
            'config' => [
                'filesystem' => $this->filesystemConfig(),
            ],
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
            'path' => '/var/www/'.$this->faker->slug(2),
            'exclude_patterns' => ['*.log', '*.tmp', 'node_modules'],
        ];
    }
}
