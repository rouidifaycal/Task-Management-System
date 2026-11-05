<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('team_members', 'user_id')->where('team_id', $this->currentTeam()->id),
            ],
        ];
    }

    private function currentTeam(): Team
    {
        $team = $this->route('current_team');

        if (! $team instanceof Team) {
            throw new \LogicException('The current team route parameter must be model-bound.');
        }

        return $team;
    }
}
