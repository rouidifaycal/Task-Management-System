<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view tasks in the project.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $this->userBelongsToProjectTeam($user, $project);
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task): bool
    {
        $project = $task->project;

        return $project !== null && $this->userBelongsToProjectTeam($user, $project);
    }

    /**
     * Determine whether the user can create tasks in the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $this->userBelongsToProjectTeam($user, $project);
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task): bool
    {
        $project = $task->project;
        $team = $project?->team;

        return $team !== null
            && $user->belongsToTeam($team)
            && (
                $user->hasTeamPermission($team, TeamPermission::ManageTasks)
                || $task->created_by === $user->id
                || $task->assigned_to === $user->id
            );
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task): bool
    {
        $project = $task->project;
        $team = $project?->team;

        return $team !== null
            && $user->belongsToTeam($team)
            && (
                $user->hasTeamPermission($team, TeamPermission::ManageTasks)
                || $task->created_by === $user->id
            );
    }

    private function userBelongsToProjectTeam(User $user, Project $project): bool
    {
        $team = $project->team;

        return $team !== null && $user->belongsToTeam($team);
    }
}
