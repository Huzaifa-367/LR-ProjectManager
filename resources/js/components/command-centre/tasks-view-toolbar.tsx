import { CalendarDays, Columns3, List } from 'lucide-react';
import {
    ToggleGroup,
    ToggleGroupItem,
} from '@/components/ui/toggle-group';
import type { TasksViewMode } from '@/hooks/use-tasks-view-preference';
import type { CalendarPeriod } from '@/lib/task-calendar';

type TasksViewToolbarProps = {
    view: TasksViewMode;
    calendarPeriod: CalendarPeriod;
    onViewChange: (view: TasksViewMode) => void;
    onCalendarPeriodChange: (period: CalendarPeriod) => void;
};

export function TasksViewToolbar({
    view,
    calendarPeriod,
    onViewChange,
    onCalendarPeriodChange,
}: TasksViewToolbarProps) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            <ToggleGroup
                type="single"
                variant="outline"
                size="sm"
                value={view}
                onValueChange={(next) => {
                    if (next !== '') {
                        onViewChange(next as TasksViewMode);
                    }
                }}
                aria-label="Task view mode"
            >
                <ToggleGroupItem value="list" aria-label="List view">
                    <List className="size-4" />
                    <span className="hidden sm:inline">List</span>
                </ToggleGroupItem>
                <ToggleGroupItem value="kanban" aria-label="Kanban view">
                    <Columns3 className="size-4" />
                    <span className="hidden sm:inline">Kanban</span>
                </ToggleGroupItem>
                <ToggleGroupItem value="calendar" aria-label="Calendar view">
                    <CalendarDays className="size-4" />
                    <span className="hidden sm:inline">Calendar</span>
                </ToggleGroupItem>
            </ToggleGroup>

            {view === 'calendar' && (
                <ToggleGroup
                    type="single"
                    variant="outline"
                    size="sm"
                    value={calendarPeriod}
                    onValueChange={(next) => {
                        if (next !== '') {
                            onCalendarPeriodChange(next as CalendarPeriod);
                        }
                    }}
                    aria-label="Calendar period"
                >
                    <ToggleGroupItem value="month">Month</ToggleGroupItem>
                    <ToggleGroupItem value="week">Week</ToggleGroupItem>
                    <ToggleGroupItem value="day">Day</ToggleGroupItem>
                </ToggleGroup>
            )}
        </div>
    );
}
