import type { CommandCentrePermissions } from '@/types/organization';

export type CommandCentreStats = {
    active_focus: number;
    open_tasks: number;
    projects: number;
    done_today: number;
};

export type CommandCentreTask = {
    id: number;
    kind: string;
    project_id: number;
    project_name: string | undefined;
    title: string;
    description: string | null;
    priority: string | null;
    status: string;
    deadline_type: string | null;
    deadline_date: string | null;
    deadline_label: string | null;
    external_link: string | null;
    is_done: boolean;
    meta: Record<string, unknown> | null;
    deadline_ui: 'soon' | 'overdue' | 'week' | 'normal';
    assignees: Array<{ id: number; display_name: string | null }>;
};

export type FocusPin = {
    id: number;
    sort_order: number;
    is_auto: boolean;
    task: CommandCentreTask;
};

export type CommandCentreProject = {
    id: number;
    name: string;
    objective: string | null;
    progress_percent: number;
    next_action: string | null;
    health: string;
};

export type MemberNote = {
    id: number;
    body: string;
    sort_order: number;
    updated_at: string | null;
};

export type CommandCentreMember = {
    id: number;
    display_name: string;
};

export type CommandCentrePageProps = {
    organization: {
        id: number;
        name: string;
        logo_url: string | null;
    };
    currentMember: {
        id: number;
        display_name: string;
        role: { name: string | undefined; slug: string | undefined };
    };
    permissions: CommandCentrePermissions;
    stats: CommandCentreStats;
    focusPins: FocusPin[];
    tasks: CommandCentreTask[];
    reminders: CommandCentreTask[];
    projects: CommandCentreProject[];
    notes: MemberNote[];
    assignedToMe: CommandCentreTask[];
    members: CommandCentreMember[];
    focusCap: number;
    unreadNotificationsCount: number;
    filters: {
        focus_date: string;
        project_id: number | null;
        assignee_member_id: number | null;
    };
};
