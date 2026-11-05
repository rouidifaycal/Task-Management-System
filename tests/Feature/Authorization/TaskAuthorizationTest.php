<?php

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('guests are redirected when they access a nested task', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create();

    $this->get(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]))->assertRedirect(route('login'));
});

test('task policy checks team membership, assignment, and ownership', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $member = User::factory()->create();
    $outsider = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($creator, ['role' => TeamRole::Member->value]);
    $team->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);

    foreach ([$owner, $admin, $creator, $assignee, $member] as $user) {
        expect(Gate::forUser($user)->allows('viewAny', [Task::class, $project]))->toBeTrue()
            ->and(Gate::forUser($user)->allows('view', $task))->toBeTrue()
            ->and(Gate::forUser($user)->allows('create', [Task::class, $project]))->toBeTrue();
    }

    expect(Gate::forUser($outsider)->allows('viewAny', [Task::class, $project]))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('view', $task))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('create', [Task::class, $project]))->toBeFalse();

    foreach ([$owner, $admin, $creator, $assignee] as $user) {
        expect(Gate::forUser($user)->allows('update', $task))->toBeTrue();
    }

    foreach ([$owner, $admin, $creator] as $user) {
        expect(Gate::forUser($user)->allows('delete', $task))->toBeTrue();
    }

    expect(Gate::forUser($member)->allows('update', $task))->toBeFalse()
        ->and(Gate::forUser($member)->allows('delete', $task))->toBeFalse()
        ->and(Gate::forUser($assignee)->allows('delete', $task))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('update', $task))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('delete', $task))->toBeFalse();
});

test('task access is denied after its project team is soft deleted', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create();
    $team->delete();

    expect(Gate::forUser($owner)->allows('viewAny', [Task::class, $project]))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('create', [Task::class, $project]))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('view', $task))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('update', $task))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('delete', $task))->toBeFalse();
});

test('an assigned team member can update their task through the authorized endpoint', function () {
    $team = Team::factory()->create();
    $creator = User::factory()->create();
    $assignee = User::factory()->create();
    $team->members()->attach($creator, ['role' => TeamRole::Member->value]);
    $team->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create([
        'created_by' => $creator->id,
        'assigned_to' => $assignee->id,
    ]);
    $this->actingAs($assignee);

    $response = $this->put(route('projects.tasks.update', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]), [
        'title' => 'Updated by assignee',
        'description' => 'Progress notes.',
        'status' => 'in_progress',
        'priority' => 'medium',
        'due_date' => null,
        'assigned_to' => $assignee->id,
    ]);

    $response->assertRedirect(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]));
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Updated by assignee',
        'assigned_to' => $assignee->id,
    ]);
});

test('the task controller denies writes from a team member who is neither creator nor assignee', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $creator = User::factory()->create();
    $member = User::factory()->create();

    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $team->members()->attach($creator, ['role' => TeamRole::Member->value]);
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);

    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create([
        'created_by' => $creator->id,
        'assigned_to' => null,
        'title' => 'Protected task',
    ]);
    $this->actingAs($member);

    $this->get(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]))->assertOk()
        ->assertDontSee('Edit task')
        ->assertDontSee('Delete');

    $this->put(route('projects.tasks.update', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]), [
        'title' => 'Unauthorized update',
        'status' => 'todo',
        'priority' => 'medium',
        'assigned_to' => null,
    ])->assertForbidden();

    $this->delete(route('projects.tasks.destroy', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]))->assertForbidden();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Protected task',
    ]);
});

test('task assignment rejects a user who is not a member of the task project team', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $outsider = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $project = Project::factory()->for($team)->create();
    $this->actingAs($owner);

    $this->post(route('projects.tasks.store', [
        'current_team' => $team,
        'project' => $project,
    ]), [
        'title' => 'Task with invalid assignee',
        'status' => 'todo',
        'priority' => 'medium',
        'assigned_to' => $outsider->id,
    ])->assertSessionHasErrors('assigned_to');

    $this->assertDatabaseMissing('tasks', ['title' => 'Task with invalid assignee']);
});

test('nested task routes do not expose tasks from another project', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;
    $project = Project::factory()->for($team)->create();
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->for($otherTeam)->create();
    $otherTask = Task::factory()->for($otherProject)->create();

    $this->actingAs($user)
        ->get(route('projects.tasks.show', [
            'current_team' => $team,
            'project' => $project,
            'task' => $otherTask,
        ]))
        ->assertNotFound();
});
