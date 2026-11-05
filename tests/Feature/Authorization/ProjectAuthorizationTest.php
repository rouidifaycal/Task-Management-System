<?php

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('project policy separates team visibility from project management', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->create(['created_by' => $admin->id]);

    foreach ([$owner, $admin, $member] as $user) {
        expect(Gate::forUser($user)->allows('viewAny', [Project::class, $team]))->toBeTrue();
        expect(Gate::forUser($user)->allows('view', $project))->toBeTrue();
    }

    expect(Gate::forUser($outsider)->allows('viewAny', [Project::class, $team]))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('view', $project))->toBeFalse();

    foreach ([$owner, $admin] as $user) {
        expect(Gate::forUser($user)->allows('create', [Project::class, $team]))->toBeTrue();
    }

    expect(Gate::forUser($member)->allows('create', [Project::class, $team]))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('create', [Project::class, $team]))->toBeFalse();
});

test('project management is limited to privileged team roles and the project creator', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $creator = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($creator, ['role' => TeamRole::Member->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->create(['created_by' => $creator->id]);

    foreach ([$owner, $admin, $creator] as $user) {
        expect(Gate::forUser($user)->allows('update', $project))->toBeTrue()
            ->and(Gate::forUser($user)->allows('delete', $project))->toBeTrue();
    }

    expect(Gate::forUser($member)->allows('update', $project))->toBeFalse()
        ->and(Gate::forUser($member)->allows('delete', $project))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('update', $project))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('delete', $project))->toBeFalse();
});

test('a team admin can create a project through the authorized endpoint', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $this->actingAs($admin);

    $response = $this->post(route('projects.store', ['current_team' => $team]), [
        'name' => 'Admin project',
        'description' => 'Created by a team admin.',
        'status' => 'planning',
    ]);

    $project = Project::query()->where('name', 'Admin project')->firstOrFail();

    $response->assertRedirect(route('projects.show', [
        'current_team' => $team,
        'project' => $project,
    ]));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'team_id' => $team->id,
        'created_by' => $admin->id,
    ]);
});

test('project access is denied after its team is soft deleted', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->for($team)->create(['created_by' => $owner->id]);
    $team->delete();

    expect(Gate::forUser($owner)->allows('create', [Project::class, $team]))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('view', $project))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $project))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $project))->toBeFalse();
});

test('the project controller denies project writes to an ordinary team member', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->create(['created_by' => $admin->id]);
    $this->actingAs($member);

    $this->get(route('projects.show', ['current_team' => $team, 'project' => $project]))
        ->assertOk()
        ->assertDontSee('Edit project')
        ->assertDontSee('Delete');

    $this->get(route('dashboard', ['current_team' => $team]))
        ->assertOk()
        ->assertDontSee(route('projects.create', ['current_team' => $team]));

    $this->post(route('projects.store', ['current_team' => $team]), [
        'name' => 'Unapproved project',
        'description' => 'This request must be denied.',
        'status' => 'planning',
    ])->assertForbidden();

    $this->put(route('projects.update', ['current_team' => $team, 'project' => $project]), [
        'name' => 'Changed by member',
        'description' => 'This request must be denied.',
        'status' => 'planning',
    ])->assertForbidden();

    $this->delete(route('projects.destroy', ['current_team' => $team, 'project' => $project]))
        ->assertForbidden();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => $project->name,
    ]);
    $this->assertDatabaseMissing('projects', ['name' => 'Unapproved project']);
});

test('the existing team gate continues to use team permissions', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    expect(Gate::forUser($owner)->allows('update', $team))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('update', $team))->toBeTrue()
        ->and(Gate::forUser($member)->allows('update', $team))->toBeFalse();
});
