<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $currentTeam, Project $project): View
    {
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

        return to_route('projects.tasks.show', [$project, $task])
            ->with('status', __('Task created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $currentTeam, Project $project, Task $task): View
    {
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
        $task->update($request->validated());

        return to_route('projects.tasks.show', [$project, $task])
            ->with('status', __('Task updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $currentTeam, Project $project, Task $task): RedirectResponse
    {
        $task->delete();

        return to_route('projects.tasks.index', $project)
            ->with('status', __('Task and its comments were deleted.'));
    }

    /**
     * Get the members available for assignment in the current team.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function teamMembers(Team $team): \Illuminate\Database\Eloquent\Collection
    {
        return $team->members()->orderBy('name')->get(['users.id', 'users.name', 'users.email']);
    }
}
