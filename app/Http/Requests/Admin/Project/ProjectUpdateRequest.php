<?php

namespace App\Http\Requests\Admin\Project;

use App\Models\Admin\Project\Project;
use Illuminate\Validation\Rule;

class ProjectUpdateRequest extends ProjectStoreRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $project = $this->route('project');

        if ($project instanceof Project && $project->member_id && $project->appointment_id) {
            $rules['status'] = ['required', 'string', Rule::in([(string) $project->status])];
        }

        return $rules;
    }
}
