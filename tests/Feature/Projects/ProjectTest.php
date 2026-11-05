<?php

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

test('guests are redirected when they try to view projects', function () {
    $user = User::factory()->create();

    $this->get(route('projects.index', ['current_team' => $user->currentTeam]))
        ->assertRedirect(route('login'));
});

test('team members can view project pages', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();

    $this->actingAs($user);

    $this->get(route('projects.index', ['current_team' => $user->currentTeam]))->assertOk();
    $this->get(route('projects.create', ['current_team' => $user->currentTeam]))->assertOk();
    $this->get(route('projects.show', ['current_team' => $user->currentTeam, 'project' => $project]))->assertOk();
    $this->get(route('projects.edit', ['current_team' => $user->currentTeam, 'project' => $project]))->assertOk();
});

test('team members can create projects', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store', ['current_team' => $user->currentTeam]), [
        'name' => 'Website launch',
        'description' => 'Coordinate the product launch.',
        'status' => 'active',
    ]);

    $project = Project::query()->where('name', 'Website launch')->firstOrFail();

    $response->assertRedirect(route('projects.show', ['current_team' => $user->currentTeam, 'project' => $project]));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'team_id' => $user->current_team_id,
        'created_by' => $user->id,
        'status' => 'active',
    ]);
});

test('project creation rejects an unknown status', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store', ['current_team' => $user->currentTeam]), [
        'name' => 'Website launch',
        'description' => null,
        'status' => 'archived',
    ]);

    $response->assertSessionHasErrors('status');
    $this->assertDatabaseCount('projects', 0);
});

test('team members can update projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create([
        'name' => 'Old name',
        'status' => 'planning',
    ]);

    $response = $this->actingAs($user)->put(route('projects.update', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]), [
        'name' => 'Updated name',
        'description' => 'Updated project description.',
        'status' => 'completed',
    ]);

    $response->assertRedirect(route('projects.show', ['current_team' => $user->currentTeam, 'project' => $project]));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated name',
        'status' => 'completed',
    ]);
});

test('a project from another team is not found', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();
    $otherProject = Project::factory()->for($otherTeam)->create();

    $response = $this->actingAs($user)->get(route('projects.show', [
        'current_team' => $user->currentTeam,
        'project' => $otherProject,
    ]));

    $response->assertNotFound();
});

test('deleting a project also deletes its tasks and comments', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $task = Task::factory()->for($project)->create();
    $comment = Comment::factory()->for($task)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('projects.destroy', [
        'current_team' => $user->currentTeam,
        'project' => $project,
    ]));

    $response->assertRedirect(route('projects.index'));
    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
});
