/** Project role slugs materialized per project (see CommandCentreRoleTemplateRegistry). */
export const PROJECT_ROLE_SLUGS = [
    'project_owner',
    'project_lead',
    'contributor',
    'project_viewer',
] as const;

export type ProjectRoleSlug = (typeof PROJECT_ROLE_SLUGS)[number];

export const PROJECT_ROLE_LABELS: Record<ProjectRoleSlug, string> = {
    project_owner: 'Project owner',
    project_lead: 'Project lead',
    contributor: 'Contributor',
    project_viewer: 'Project viewer',
};

/** Roles assignable when adding someone to a project (creator keeps owner on bootstrap). */
export const PROJECT_ROLE_ASSIGNABLE_SLUGS: ProjectRoleSlug[] = [
    'project_lead',
    'contributor',
    'project_viewer',
];

export function formatProjectRoleSlug(slug: string): string {
    return (
        PROJECT_ROLE_LABELS[slug as ProjectRoleSlug] ??
        slug.replace(/_/g, ' ')
    );
}

export function defaultProjectRoleSlugForNewMember(): ProjectRoleSlug {
    return 'contributor';
}
