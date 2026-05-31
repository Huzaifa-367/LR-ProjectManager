import type { Auth } from '@/types/auth';
import type { OrganizationContext } from '@/types/organization';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            organizationContext: OrganizationContext;
            [key: string]: unknown;
        };
    }
}
