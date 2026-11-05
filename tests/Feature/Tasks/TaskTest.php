<?php

use App\Enums\TeamRole;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

test('team members can view task pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $task = Task::factory()->for($project)->create();

    $this->actingAs($user);

    $this->get(route('projects.tasks.index', ['current_team' => $user->currentTeam, 'project' => $project]))->assertOk();
    $this->get(route('projects.tasks.create', ['current_team' => $user->currentTeam, 'project' => $project]))->assertOk();
    $this->get(route('projects.tasks.show', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $task,
    ]))->assertOk();
    $this->get(route('projects.tasks.edit', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $task,
    ]))->assertOk();
});

test('team members can create tasks and assign them to a teammate', function () {
    $user = User::factory()->create();
    $assignee = User::factory()->create();
    $team = $user->currentTeam;
    $team->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->create();

    $response = $this->actingAs($user)->post(route('projects.tasks.store', [
        'current_team' => $team,
        'project' => $project,
    ]), [
        'title' => 'Prepare release notes',
        'description' => 'Summarize the changes for this release.',
        'status' => 'todo',
        'priority' => 'high',
        'due_date' => '2026-12-01',
        'assigned_to' => $assignee->id,
    ]);

    $task = Task::query()->where('title', 'Prepare release notes')->firstOrFail();

    $response->assertRedirect(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]));
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'project_id' => $project->id,
        'created_by' => $user->id,
        'assigned_to' => $assignee->id,
        'status' => 'todo',
        'priority' => 'high',
    ]);
    $this->assertSame('2026-12-01', $task->fresh()->due_date->toDateString());
});

test('task validation rejects invalid status priority and outside team assignments', function () {
    $user = User::factory()->create();
    $outsider = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();

    $response = $this->actingAs($user)->post(route('projects.tasks.store', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]), [
        'title' => 'Prepare release notes',
        'status' => 'blocked',
        'priority' => 'urgent',
        'assigned_to' => $outsider->id,
    ]);

    $response->assertSessionHasErrors(['status', 'priority', 'assigned_to']);
    $this->assertDatabaseMissing('tasks', ['title' => 'Prepare release notes']);
});

test('team members can update tasks', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Draft notes',
        'status' => 'todo',
    ]);

    $response = $this->actingAs($user)->put(route('projects.tasks.update', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $task,
    ]), [
        'title' => 'Review release notes',
        'description' => 'Check the final draft.',
        'status' => 'completed',
        'priority' => 'low',
        'due_date' => null,
        'assigned_to' => null,
    ]);

    $response->assertRedirect(route('projects.tasks.show', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $task,
    ]));
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Review release notes',
        'status' => 'completed',
        'priority' => 'low',
        'assigned_to' => null,
    ]);
});

test('a task outside the selected project is not found', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->for($otherTeam)->create();
    $otherTask = Task::factory()->for($otherProject)->create();

    $response = $this->actingAs($user)->get(route('projects.tasks.show', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $otherTask,
    ]));

    $response->assertNotFound();
});

test('deleting a task also deletes its comments', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $task = Task::factory()->for($project)->create();
    $comment = Comment::factory()->for($task)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('projects.tasks.destroy', [
        'current_team' => $user->currentTeam,
        'project' => $project,
        'task' => $task,
    ]));

    $response->assertRedirect(route('projects.tasks.index', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]));
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
