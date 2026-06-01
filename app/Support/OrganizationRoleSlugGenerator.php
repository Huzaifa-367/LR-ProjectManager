<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\OrganizationRole;
use Illuminate\Support\Str;

final class OrganizationRoleSlugGenerator
{
    public static function uniqueForOrganization(Organization $organization, string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'role';
        }

        $slug = $base;
        $suffix = 2;

        while (
            OrganizationRole::query()
                ->where('organization_id', $organization->id)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
