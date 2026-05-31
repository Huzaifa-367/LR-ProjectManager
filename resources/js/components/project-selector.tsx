import { router } from '@inertiajs/react';
import { FolderKanban } from 'lucide-react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useOrganizationContext } from '@/hooks/use-organization-context';
import ProjectContextController from '@/actions/App/Http/Controllers/ProjectContextController';

const ALL_PROJECTS_VALUE = 'all';

export function ProjectSelector() {
    const { selectedOrganization, selectedProject, projects } =
        useOrganizationContext();

    if (selectedOrganization === null || projects.length === 0) {
        return null;
    }

    const value = selectedProject?.id?.toString() ?? ALL_PROJECTS_VALUE;

    return (
        <Select
            data-test="project-selector"
            value={value}
            onValueChange={(nextValue: string) => {
                router.post(
                    ProjectContextController.update.url(
                        selectedOrganization.id,
                    ),
                    {
                        project_id:
                            nextValue === ALL_PROJECTS_VALUE
                                ? null
                                : Number(nextValue),
                    },
                    { preserveScroll: true },
                );
            }}
        >
            <SelectTrigger size="sm" className="tcm-context-select">
                <FolderKanban className="size-4 shrink-0 text-muted-foreground" />
                <SelectValue placeholder="All projects" />
            </SelectTrigger>
            <SelectContent align="start">
                <SelectItem value={ALL_PROJECTS_VALUE}>All projects</SelectItem>
                {projects.map((project) => (
                    <SelectItem
                        key={project.id}
                        value={project.id.toString()}
                    >
                        {project.name}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
