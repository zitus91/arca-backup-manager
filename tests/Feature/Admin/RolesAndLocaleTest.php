<?php

use App\Livewire\Admin\UserForm;
use App\Models\User;
use Livewire\Livewire;

function admin(): User
{
    return User::factory()->create(['role' => 'admin']);
}

function standard(): User
{
    return User::factory()->create(['role' => 'standard']);
}

it('blocks standard users from admin-only pages', function () {
    $this->actingAs(standard());

    $this->get(route('admin.backup.system'))->assertForbidden();
    $this->get(route('admin.backup.users'))->assertForbidden();
});

it('allows admins into admin-only pages', function () {
    $this->actingAs(admin());

    $this->get(route('admin.backup.system'))->assertOk();
    $this->get(route('admin.backup.users'))->assertOk();
});

it('isAdmin reflects the role column', function () {
    expect(admin()->isAdmin())->toBeTrue();
    expect(standard()->isAdmin())->toBeFalse();
});

it('stores a language preference and switches the session locale', function () {
    $user = standard();
    $this->actingAs($user);

    $this->post(route('admin.backup.locale'), ['locale' => 'it'])->assertRedirect();

    expect($user->fresh()->locale)->toBe('it');
    expect(session('locale'))->toBe('it');
});

it('ignores an unsupported locale', function () {
    $user = standard();
    $this->actingAs($user);

    $this->post(route('admin.backup.locale'), ['locale' => 'de']);

    expect($user->fresh()->locale)->toBeNull();
});

it('lets an admin create a user with a role', function () {
    $this->actingAs(admin());

    Livewire::test(UserForm::class)
        ->set('name', 'New Admin')
        ->set('email', 'new@example.com')
        ->set('role', 'admin')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('save')
        ->assertDispatched('user-saved');

    expect(User::where('email', 'new@example.com')->first()->role)->toBe('admin');
});

it('prevents an admin from demoting themselves', function () {
    $me = admin();
    $this->actingAs($me);

    Livewire::test(UserForm::class, ['userId' => $me->id])
        ->set('role', 'standard')
        ->call('save')
        ->assertHasErrors('role');

    expect($me->fresh()->role)->toBe('admin');
});
