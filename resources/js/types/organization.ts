export type OrganizationListItem = {
    id: number;
    name: string;
    slug: string;
    membership: 'owner' | 'member' | 'invited';
    member_status: 'active' | 'invited';
    is_primary_org: boolean;
};

export type OrganizationContext = {
    selectedOrganization: {
        id: number;
        name: string;
        slug: string;
    } | null;
    selectedProject: {
        id: number;
        name: string;
    } | null;
    projects: Array<{
        id: number;
        name: string;
    }>;
    organizations: OrganizationListItem[];
    pendingInvitations: Array<{
        id: number;
        organization_name: string;
        role_name: string;
        expires_at: string;
        accept_url?: string;
    }>;
    permissions: CommandCentrePermissions | null;
    notifications: {
        unreadCount: number;
        recent: Array<{
            id: string;
            title: string;
            body: string;
            action_url: string | null;
            read_at: string | null;
            created_at: string;
        }>;
    } | null;
};

export type OrganizationSummary = {
    id: number;
    name: string;
    slug: string;
};

export type OrganizationMemberSummary = {
    id: number;
    display_name: string;
    role: {
        name: string | undefined;
        slug: string | undefined;
    };
};

export type CommandCentrePermissions = {
    org: string[];
    projects: Record<number, string[]>;
};
