import { router } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { select } from '@/routes/organizations';

export function OrganizationSelector() {
    const { selectedOrganization, organizations } = useOrganizationContext();

    const activeOrganizations = organizations.filter(
        (organization) => organization.member_status === 'active',
    );

    if (activeOrganizations.length === 0) {
        return null;
    }

    const value = selectedOrganization?.id?.toString() ?? '';

    return (
        <Select
            data-test="organization-selector"
            value={value}
            onValueChange={(organizationId: string) => {
                router.post(
                    select.url(),
                    { organization_id: Number(organizationId) },
                    { preserveScroll: false },
                );
            }}
        >
            <SelectTrigger size="sm" className="tcm-context-select">
                <Building2 className="size-4 shrink-0 text-muted-foreground" />
                <SelectValue placeholder="Select organization" />
            </SelectTrigger>
            <SelectContent align="start">
                {activeOrganizations.map((organization) => (
                    <SelectItem
                        key={organization.id}
                        value={organization.id.toString()}
                    >
                        {organization.name}
                        {organization.membership === 'owner' ? ' · Owner' : ''}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
