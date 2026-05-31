<?php

namespace App\Support;

/**
 * Platform-level permission registry (Spatie global roles).
 * Organization/project permissions live in CommandCentrePermissionRegistry (per tenant).
 */
class PermissionRegistry
{
    /**
     * @return array<string, array{label: string, description: string, permissions: array<string, string>}>
     */
    public static function groups(): array
    {
        return [
            'users' => [
                'label' => 'Users',
                'description' => 'Platform user accounts and role assignment.',
                'permissions' => [
                    'users.view' => 'View users',
                    'users.manage' => 'Manage users and role assignments',
                ],
            ],
            'roles' => [
                'label' => 'Roles',
                'description' => 'Platform roles and permission matrix.',
                'permissions' => [
                    'roles.view' => 'View roles and permissions',
                    'roles.manage' => 'Create, edit, and delete roles',
                ],
            ],
            'settings' => [
                'label' => 'Settings',
                'description' => 'Application-wide settings.',
                'permissions' => [
                    'settings.manage' => 'Manage application settings',
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect(self::groups())
            ->flatMap(fn (array $group): array => array_keys($group['permissions']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function forGroup(string $key): array
    {
        $groups = self::groups();

        if (! isset($groups[$key])) {
            return [];
        }

        return array_keys($groups[$key]['permissions']);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function forGroups(array $keys): array
    {
        return collect($keys)
            ->flatMap(fn (string $key): array => self::forGroup($key))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function systemRoles(): array
    {
        return ['super_admin'];
    }
}
