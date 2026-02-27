<?php

namespace Database\Factories;

use App\Models\BackupStorageDestination;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupStorageDestinationFactory extends Factory
{
    protected $model = BackupStorageDestination::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['s3', 'ftp']);

        return [
            'name' => $this->faker->company() . ' ' . strtoupper($type),
            'type' => $type,
            'config' => $type === 's3' ? $this->s3Config() : $this->ftpConfig(),
            'is_active' => true,
        ];
    }

    public function s3(): static
    {
        return $this->state(fn () => [
            'type' => 's3',
            'config' => $this->s3Config(),
        ]);
    }

    public function ftp(): static
    {
        return $this->state(fn () => [
            'type' => 'ftp',
            'config' => $this->ftpConfig(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    private function s3Config(): array
    {
        return [
            'bucket' => $this->faker->slug(2),
            'region' => $this->faker->randomElement(['us-east-1', 'eu-west-1', 'ap-southeast-1']),
            'access_key' => 'AKIA' . strtoupper($this->faker->bothify('################')),
            'secret_key' => $this->faker->sha256(),
            'endpoint' => null,
        ];
    }

    private function ftpConfig(): array
    {
        return [
            'host' => $this->faker->domainName(),
            'port' => 21,
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(12, 20),
            'root_path' => '/backups',
            'passive' => true,
            'ssl' => true,
        ];
    }
}
