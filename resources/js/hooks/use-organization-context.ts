import type { OrganizationContext } from '@/types/organization';
import { usePage } from '@inertiajs/react';

export function useOrganizationContext(): OrganizationContext {
    const { organizationContext } = usePage().props;

    return (
        organizationContext ?? {
            selectedOrganization: null,
            selectedProject: null,
            projects: [],
            organizations: [],
            pendingInvitations: [],
            permissions: null,
            notifications: null,
            aiEnabled: true,
        }
    );
}
