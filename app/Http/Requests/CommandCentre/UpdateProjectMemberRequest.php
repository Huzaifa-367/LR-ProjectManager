<?php

namespace App\Http\Requests\CommandCentre;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'project_role_id' => [
                'required',
                'integer',
                Rule::exists('project_roles', 'id')->where('project_id', $project->id),
            ],
        ];
    }
}
