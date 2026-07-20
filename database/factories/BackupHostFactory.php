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
                'ssh' => $this->sshBlock(),
            ],
            'is_active' => true,
        ];
    }

    public function sshOnly(): static
    {
        return $this->state(fn () => ['config' => ['ssh' => $this->sshBlock()]]);
    }

    public function withMysql(): static
    {
        return $this->state(fn (array $attrs) => [
            'config' => array_merge($attrs['config'] ?? [], [
                'mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'username' => 'root', 'password' => $this->faker->password()],
            ]),
        ]);
    }

    public function withMongodb(): static
    {
        return $this->state(fn (array $attrs) => [
            'config' => array_merge($attrs['config'] ?? [], [
                'mongodb' => ['host' => '127.0.0.1', 'port' => 27017, 'username' => 'admin', 'password' => $this->faker->password()],
            ]),
        ]);
    }

    public function withFilesystem(): static
    {
        return $this->state(fn (array $attrs) => [
            'config' => array_merge($attrs['config'] ?? [], [
                'filesystem' => ['enabled' => true],
            ]),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    private function sshBlock(): array
    {
        return [
            'enabled' => true,
            'host' => $this->faker->domainName(),
            'port' => 22,
            'user' => 'ubuntu',
            'auth_method' => 'key',
            'key_path' => '/home/ubuntu/.ssh/id_rsa',
            'password' => '',
        ];
    }
}
