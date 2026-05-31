export type CalendarPeriod = 'month' | 'week' | 'day';

export function startOfDay(date: Date): Date {
    const next = new Date(date);
    next.setHours(0, 0, 0, 0);

    return next;
}

export function addDays(date: Date, days: number): Date {
    const next = new Date(date);
    next.setDate(next.getDate() + days);

    return startOfDay(next);
}

export function addMonths(date: Date, months: number): Date {
    const next = new Date(date);
    next.setMonth(next.getMonth() + months);

    return startOfDay(next);
}

export function startOfWeek(date: Date): Date {
    const next = startOfDay(date);
    const day = next.getDay();
    const diff = day === 0 ? -6 : 1 - day;

    return addDays(next, diff);
}

export function startOfMonth(date: Date): Date {
    const next = startOfDay(date);
    next.setDate(1);

    return next;
}

export function isSameDay(left: Date, right: Date): boolean {
    return (
        left.getFullYear() === right.getFullYear() &&
        left.getMonth() === right.getMonth() &&
        left.getDate() === right.getDate()
    );
}

export function formatDateKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function parseDateKey(key: string): Date {
    const [year, month, day] = key.split('-').map(Number);

    return startOfDay(new Date(year, month - 1, day));
}

export function getMonthGrid(focusDate: Date): Date[] {
    const monthStart = startOfMonth(focusDate);
    const gridStart = startOfWeek(monthStart);
    const days: Date[] = [];

    for (let index = 0; index < 42; index += 1) {
        days.push(addDays(gridStart, index));
    }

    return days;
}

export function getWeekDays(focusDate: Date): Date[] {
    const weekStart = startOfWeek(focusDate);
    const days: Date[] = [];

    for (let index = 0; index < 7; index += 1) {
        days.push(addDays(weekStart, index));
    }

    return days;
}

export function formatMonthYear(date: Date): string {
    return date.toLocaleDateString(undefined, {
        month: 'long',
        year: 'numeric',
    });
}

export function formatWeekRange(focusDate: Date): string {
    const weekDays = getWeekDays(focusDate);
    const start = weekDays[0];
    const end = weekDays[6];
    const sameMonth = start.getMonth() === end.getMonth();
    const startLabel = start.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
    const endLabel = end.toLocaleDateString(undefined, {
        month: sameMonth ? undefined : 'short',
        day: 'numeric',
        year: 'numeric',
    });

    return `${startLabel} – ${endLabel}`;
}

export function formatDayLabel(date: Date): string {
    return date.toLocaleDateString(undefined, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}

export function navigateCalendarPeriod(
    focusDate: Date,
    period: CalendarPeriod,
    direction: -1 | 1,
): Date {
    if (period === 'month') {
        return addMonths(focusDate, direction);
    }

    if (period === 'week') {
        return addDays(focusDate, direction * 7);
    }

    return addDays(focusDate, direction);
}

export const WEEKDAY_LABELS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
