<?php

use App\Enums\TeamRole;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('a newly assigned teammate receives a queued database notification', function () {
    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $team = $assigner->currentTeam;
    $team->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->create(['name' => 'Website launch']);
    Notification::fake();

    $response = $this->actingAs($assigner)->post(route('projects.tasks.store', [
        'current_team' => $team,
        'project' => $project,
    ]), [
        'title' => 'Prepare release notes',
        'description' => 'Summarize this release.',
        'status' => 'todo',
        'priority' => 'high',
        'assigned_to' => $assignee->id,
    ]);

    $task = Task::query()->where('title', 'Prepare release notes')->firstOrFail();

    $response->assertRedirect(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]));
    Notification::assertSentTo($assignee, TaskAssigned::class, function (TaskAssigned $notification) use ($assignee, $assigner, $project, $task) {
        expect($notification)->toBeInstanceOf(ShouldQueue::class)
            ->and($notification->afterCommit)->toBeTrue()
            ->and($notification->via($assignee))->toBe(['database'])
            ->and($notification->toArray($assignee))->toBe([
                'title' => 'Task assigned to you',
                'task_id' => $task->id,
                'task_title' => 'Prepare release notes',
                'project_name' => 'Website launch',
                'assigned_by' => $assigner->name,
                'url' => route('projects.tasks.show', [
                    'current_team' => $project->team,
                    'project' => $project,
                    'task' => $task,
                ]),
            ]);

        return true;
    });
    Notification::assertNothingSentTo($assigner);
});

test('the database channel stores task assignment notification data', function () {
    $user = User::factory()->create();
    $notification = new TaskAssigned(
        taskId: 42,
        taskTitle: 'Prepare release notes',
        projectName: 'Website launch',
        assignedByName: 'Alex Manager',
        taskUrl: 'http://localhost/tasks/42',
    );

    Notification::sendNow($user, $notification);
    $storedNotification = $user->notifications()->firstOrFail();

    $this->assertDatabaseHas('notifications', [
        'id' => $storedNotification->id,
        'notifiable_id' => $user->id,
        'type' => TaskAssigned::class,
    ]);
    expect($storedNotification->data)->toBe([
        'title' => 'Task assigned to you',
        'task_id' => 42,
        'task_title' => 'Prepare release notes',
        'project_name' => 'Website launch',
        'assigned_by' => 'Alex Manager',
        'url' => 'http://localhost/tasks/42',
    ]);
});

test('a newly selected assignee receives a notification when a task is reassigned', function () {
    $assigner = User::factory()->create();
    $previousAssignee = User::factory()->create();
    $newAssignee = User::factory()->create();
    $team = $assigner->currentTeam;
    $team->members()->attach($previousAssignee, ['role' => TeamRole::Member->value]);
    $team->members()->attach($newAssignee, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create([
        'title' => 'Review the release',
        'created_by' => $assigner->id,
        'assigned_to' => $previousAssignee->id,
    ]);
    Notification::fake();

    $response = $this->actingAs($assigner)->put(route('projects.tasks.update', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]), [
        'title' => $task->title,
        'description' => $task->description,
        'status' => $task->status,
        'priority' => $task->priority,
        'due_date' => null,
        'assigned_to' => $newAssignee->id,
    ]);

    $response->assertRedirect(route('projects.tasks.show', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]));
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'assigned_to' => $newAssignee->id,
    ]);
    Notification::assertSentTo($newAssignee, TaskAssigned::class);
    Notification::assertNothingSentTo($previousAssignee);
    Notification::assertNothingSentTo($assigner);
});

test('updating unrelated task fields does not send an assignment notification', function () {
    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $team = $assigner->currentTeam;
    $team->members()->attach($assignee, ['role' => TeamRole::Member->value]);
    $project = Project::factory()->for($team)->create();
    $task = Task::factory()->for($project)->create([
        'created_by' => $assigner->id,
        'assigned_to' => $assignee->id,
    ]);
    Notification::fake();

    $this->actingAs($assigner)->put(route('projects.tasks.update', [
        'current_team' => $team,
        'project' => $project,
        'task' => $task,
    ]), [
        'title' => 'Updated task title',
        'description' => $task->description,
        'status' => 'in_progress',
        'priority' => $task->priority,
        'due_date' => null,
        'assigned_to' => $assignee->id,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

test('assigning a task to yourself does not send an assignment notification', function () {
    $assignee = User::factory()->create();
    $team = $assignee->currentTeam;
    $project = Project::factory()->for($team)->create();
    Notification::fake();

    $this->actingAs($assignee)->post(route('projects.tasks.store', [
        'current_team' => $team,
        'project' => $project,
    ]), [
        'title' => 'My own task',
        'status' => 'todo',
        'priority' => 'medium',
        'assigned_to' => $assignee->id,
    ])->assertRedirect();

    Notification::assertNothingSent();
});

test('an authenticated user sees only their own notifications', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => [
            'title' => 'Task assigned to you',
            'task_title' => 'My private task',
            'project_name' => 'My project',
            'assigned_by' => 'My teammate',
            'url' => route('home'),
        ],
    ]);
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you', 'task_title' => 'Previously read task'],
        'read_at' => now(),
    ]);
    $otherUser->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you', 'task_title' => 'Someone else’s task'],
    ]);

    $this->actingAs($user)->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('My private task')
        ->assertSee('Previously read task')
        ->assertSee('Unread')
        ->assertSee('Read')
        ->assertSee('Open task')
        ->assertSee(route('home'))
        ->assertDontSee('Someone else’s task');
});

test('a user cannot mark another users notification as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $notification = $otherUser->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you'],
    ]);

    $this->actingAs($user)->patch(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('a user can mark their own notification as read', function () {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you'],
    ]);

    $this->actingAs($user)->patch(route('notifications.read', $notification->id))
        ->assertRedirect(route('notifications.index'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a user can mark all of their own notifications as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $notifications = collect(range(1, 2))->map(fn () => $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you'],
    ]));
    $otherNotification = $otherUser->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you'],
    ]);

    $this->actingAs($user)->patch(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    foreach ($notifications as $notification) {
        expect($notification->fresh()->read_at)->not->toBeNull();
    }

    expect($otherNotification->fresh()->read_at)->toBeNull();
});

test('guests cannot access notification pages or actions', function () {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => TaskAssigned::class,
        'data' => ['title' => 'Task assigned to you'],
    ]);

    $this->get(route('notifications.index'))->assertRedirect(route('login'));
    $this->patch(route('notifications.read', $notification->id))->assertRedirect(route('login'));
    $this->patch(route('notifications.read-all'))->assertRedirect(route('login'));
});
