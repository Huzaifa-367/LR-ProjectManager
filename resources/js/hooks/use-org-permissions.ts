import type { CommandCentrePermissions } from '@/types/organization';

export function canOrg(permissions: string[], slug: string): boolean {
    return permissions.includes(slug);
}

export function canOrgAny(
    permissions: string[],
    ...slugs: string[]
): boolean {
    return slugs.some((slug) => permissions.includes(slug));
}

export function canProject(
    permissions: CommandCentrePermissions,
    projectId: number,
    slug: string,
): boolean {
    return permissions.projects[projectId]?.includes(slug) ?? false;
}
