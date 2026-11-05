<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Notifications\TaskAssigned as TaskAssignedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $currentTeam, Project $project): View
    {
        $this->authorize('viewAny', [Task::class, $project]);

        return view('tasks.index', [
            'project' => $project,
            'tasks' => $project->tasks()
                ->with(['assignedTo', 'creator'])
                ->latest()
                ->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Team $currentTeam, Project $project): View
    {
        $this->authorize('create', [Task::class, $project]);

        return view('tasks.create', [
            'project' => $project,
            'task' => new Task,
            'members' => $this->teamMembers($currentTeam),
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request, Team $currentTeam, Project $project): RedirectResponse
    {
        $task = $project->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->getAuthIdentifier(),
        ]);

        $this->notifyNewAssignee($task, $request->user());

        return to_route('projects.tasks.show', [$project, $task])
            ->with('status', __('Task created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $currentTeam, Project $project, Task $task): View
    {
        $this->authorize('view', $task);

        return view('tasks.show', [
            'project' => $project,
            'task' => $task->load(['assignedTo', 'creator', 'comments.user']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $currentTeam, Project $project, Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', [
            'project' => $project,
            'task' => $task,
            'members' => $this->teamMembers($currentTeam),
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTaskRequest $request, Team $currentTeam, Project $project, Task $task): RedirectResponse
    {
        $previousAssigneeId = $task->assigned_to === null ? null : (int) $task->assigned_to;
        $task->update($request->validated());

        if ($task->wasChanged('assigned_to')) {
            $this->notifyNewAssignee($task, $request->user(), $previousAssigneeId);
        }

        return to_route('projects.tasks.show', [$project, $task])
            ->with('status', __('Task updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $currentTeam, Project $project, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return to_route('projects.tasks.index', $project)
            ->with('status', __('Task and its comments were deleted.'));
    }

    /**
     * Get the members available for assignment in the current team.
     *
     * @return Collection<int, User>
     */
    private function teamMembers(Team $team): Collection
    {
        return $team->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);
    }

    private function notifyNewAssignee(Task $task, User $assignedBy, ?int $previousAssigneeId = null): void
    {
        $assigneeId = $task->assigned_to;

        if (
            $assigneeId === null
            || (int) $assigneeId === (int) $assignedBy->getKey()
            || ($previousAssigneeId !== null && (int) $assigneeId === $previousAssigneeId)
        ) {
            return;
        }

        $project = $task->project;
        $team = $project->team;

        abort_if($team === null, 404);

        $assignee = $task->assignedTo()->firstOrFail();

        $assignee->notify(new TaskAssignedNotification(
            taskId: $task->id,
            taskTitle: $task->title,
            projectName: $project->name,
            assignedByName: $assignedBy->name,
            taskUrl: route('projects.tasks.show', [
                'current_team' => $team,
                'project' => $project,
                'task' => $task,
            ]),
        ));
    }
}
