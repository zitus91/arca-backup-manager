<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    RateLimiter::clear('login:test@example.com|127.0.0.1');
});

it('renders the login page', function () {
    Livewire::test(Login::class)
        ->assertStatus(200);
});

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('backup.dashboard'));

    expect(auth()->check())->toBeTrue();
    expect(auth()->id())->toBe($user->id);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('requires email field', function () {
    Livewire::test(Login::class)
        ->set('email', '')
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email']);
});

it('requires password field', function () {
    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', '')
        ->call('login')
        ->assertHasErrors(['password']);
});

it('throttles after 5 failed attempts', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $component = Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password');

    // Exhaust 5 attempts
    for ($i = 0; $i < 5; $i++) {
        $component->call('login');
    }

    // 6th attempt — should be throttled regardless of credentials
    $component
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});

it('clears rate limit on successful login', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // 3 failed attempts
    $component = Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'wrong-password');

    for ($i = 0; $i < 3; $i++) {
        $component->call('login');
    }

    // Successful login clears the limiter
    $component
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('backup.dashboard'));

    expect(auth()->check())->toBeTrue();
});

it('records audit log on successful login', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => bcrypt('secret'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'admin@example.com')
        ->set('password', 'secret')
        ->call('login');

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'login',
    ]);
});
