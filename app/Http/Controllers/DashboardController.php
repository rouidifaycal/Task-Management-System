<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Team;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Team $currentTeam): View
    {
        $projectIds = $currentTeam->projects()->select('id');

        return view('dashboard', [
            'projectCount' => $currentTeam->projects()->count(),
            'openTaskCount' => Task::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', '!=', Task::STATUS_COMPLETED)
                ->count(),
            'completedTaskCount' => Task::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', Task::STATUS_COMPLETED)
                ->count(),
            'projects' => $currentTeam->projects()
                ->withCount('tasks')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}
