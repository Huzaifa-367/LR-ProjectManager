<?php

namespace App\Http\Requests\CommandCentre;

use App\Concerns\ProjectRoleValidationRules;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRoleRequest extends FormRequest
{
    use ProjectRoleValidationRules;

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

        return $this->projectRoleRules($project);
    }
}
