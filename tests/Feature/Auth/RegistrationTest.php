<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $this->get(route('register'))->assertOk();
});

test('users can register and receive a personal team', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticated();
    $this->get('/dashboard')->assertRedirect(route('dashboard'));
    $this->assertDatabaseHas('users', [
        'name' => 'Taylor Otwell',
        'email' => 'taylor@example.com',
    ]);

    $user = User::query()->where('email', 'taylor@example.com')->firstOrFail();

    $this->assertDatabaseHas('teams', [
        'id' => $user->current_team_id,
        'name' => "Taylor Otwell's Team",
        'is_personal' => true,
    ]);
    $this->assertDatabaseHas('team_members', [
        'team_id' => $user->current_team_id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);
});

test('registration rejects an email address that is already in use', function () {
    $user = User::factory()->create();

    $response = $this->post(route('register.store'), [
        'name' => 'Another User',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});
