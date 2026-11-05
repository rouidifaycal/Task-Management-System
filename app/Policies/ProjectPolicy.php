<?php

namespace App\Policies;

use App\Enums\TeamPermission;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Determine whether the user can view projects for the team.
     */
    public function viewAny(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can view the project.
     */
    public function view(User $user, Project $project): bool
    {
        $team = $project->team;

        return $team !== null && $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create projects for the team.
     */
    public function create(User $user, Team $team): bool
    {
        return $user->belongsToTeam($team)
            && $user->hasTeamPermission($team, TeamPermission::ManageProjects);
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        $team = $project->team;

        return $team !== null
            && $user->belongsToTeam($team)
            && (
                $user->hasTeamPermission($team, TeamPermission::ManageProjects)
                || $project->created_by === $user->id
            );
    }

    /**
     * Determine whether the user can delete the project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }
}
