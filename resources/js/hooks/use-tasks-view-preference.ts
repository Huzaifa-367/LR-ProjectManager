import { useCallback, useEffect, useState } from 'react';
import type { CalendarPeriod } from '@/lib/task-calendar';

export type TasksViewMode = 'list' | 'kanban' | 'calendar';

type TasksViewPreference = {
    view: TasksViewMode;
    calendarPeriod: CalendarPeriod;
};

const STORAGE_KEY = 'tcm.tasks.view-preference';

const DEFAULT_PREFERENCE: TasksViewPreference = {
    view: 'list',
    calendarPeriod: 'month',
};

function readPreference(): TasksViewPreference {
    if (typeof window === 'undefined') {
        return DEFAULT_PREFERENCE;
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (raw === null) {
            return DEFAULT_PREFERENCE;
        }

        const parsed = JSON.parse(raw) as Partial<TasksViewPreference>;

        return {
            view:
                parsed.view === 'kanban' ||
                parsed.view === 'calendar' ||
                parsed.view === 'list'
                    ? parsed.view
                    : DEFAULT_PREFERENCE.view,
            calendarPeriod:
                parsed.calendarPeriod === 'week' ||
                parsed.calendarPeriod === 'day' ||
                parsed.calendarPeriod === 'month'
                    ? parsed.calendarPeriod
                    : DEFAULT_PREFERENCE.calendarPeriod,
        };
    } catch {
        return DEFAULT_PREFERENCE;
    }
}

function writePreference(preference: TasksViewPreference): void {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(preference));
}

export function useTasksViewPreference(): {
    view: TasksViewMode;
    calendarPeriod: CalendarPeriod;
    setView: (view: TasksViewMode) => void;
    setCalendarPeriod: (period: CalendarPeriod) => void;
} {
    const [preference, setPreference] =
        useState<TasksViewPreference>(readPreference);

    useEffect(() => {
        writePreference(preference);
    }, [preference]);

    const setView = useCallback((view: TasksViewMode): void => {
        setPreference((current) => ({ ...current, view }));
    }, []);

    const setCalendarPeriod = useCallback((calendarPeriod: CalendarPeriod): void => {
        setPreference((current) => ({ ...current, calendarPeriod }));
    }, []);

    return {
        view: preference.view,
        calendarPeriod: preference.calendarPeriod,
        setView,
        setCalendarPeriod,
    };
}
