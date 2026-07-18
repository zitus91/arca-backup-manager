<?php

namespace Database\Factories;

use App\Models\BackupHost;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupHostFactory extends Factory
{
    protected $model = BackupHost::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->domainWord().'-host',
            'config' => [
                'host' => $this->faker->domainName(),
                'port' => 22,
                'user' => 'ubuntu',
                'auth_method' => 'key',
                'key_path' => '/home/ubuntu/.ssh/id_rsa',
                'password' => '',
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
