<?php

namespace App\Http\Requests\Organizations;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectContextRequest extends FormRequest
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
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(
                    fn ($query) => $query
                        ->where('organization_id', $organization->id)
                        ->whereNull('archived_at'),
                ),
            ],
        ];
    }

    public function selectedProjectId(): ?int
    {
        $projectId = $this->input('project_id');

        if ($projectId === null || $projectId === '') {
            return null;
        }

        return (int) $projectId;
    }
}
