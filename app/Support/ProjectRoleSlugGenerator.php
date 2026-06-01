<?php

namespace App\Support;

use App\Models\Project;
use App\Models\ProjectRole;
use Illuminate\Support\Str;

final class ProjectRoleSlugGenerator
{
    public static function uniqueForProject(Project $project, string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'role';
        }

        $slug = $base;
        $suffix = 2;

        while (
            ProjectRole::query()
                ->where('project_id', $project->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /** @return list<string> */
    public static function systemSlugs(): array
    {
        return array_map(
            fn (array $template): string => $template['slug'],
            CommandCentreRoleTemplateRegistry::projectRoles(),
        );
    }

    public static function isSystemSlug(string $slug): bool
    {
        return in_array($slug, self::systemSlugs(), true);
    }
}
