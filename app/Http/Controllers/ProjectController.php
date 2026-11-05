<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $currentTeam): View
    {
        $this->authorize('viewAny', [Project::class, $currentTeam]);

        return view('projects.index', [
            'projects' => $currentTeam->projects()
                ->with('creator')
                ->withCount('tasks')
                ->latest()
                ->paginate(10),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Team $currentTeam): View
    {
        $this->authorize('create', [Project::class, $currentTeam]);

        return view('projects.create', [
            'project' => new Project,
            'statuses' => Project::STATUSES,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request, Team $currentTeam): RedirectResponse
    {
        $project = $currentTeam->projects()->create($request->validated());
        $project->creator()->associate($request->user());
        $project->save();

        return to_route('projects.show', $project)
            ->with('status', __('Project created.'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $currentTeam, Project $project): View
    {
        $this->authorize('view', $project);

        return view('projects.show', [
            'project' => $project
                ->loadCount('tasks')
                ->load(['creator', 'tasks' => fn ($query) => $query
                    ->with('assignedTo')
                    ->latest()
                    ->limit(10)]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $currentTeam, Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', [
            'project' => $project,
            'statuses' => Project::STATUSES,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Team $currentTeam, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return to_route('projects.show', $project)
            ->with('status', __('Project updated.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $currentTeam, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return to_route('projects.index')
            ->with('status', __('Project and its tasks were deleted.'));
    }
}
