import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toDateTimeLocalValue } from '@/lib/task-options';

type TaskDueDateFieldProps = {
    id: string;
    defaultValue?: string | null;
    disabled?: boolean;
    error?: string;
};

export function TaskDueDateField({
    id,
    defaultValue = '',
    disabled = false,
    error,
}: TaskDueDateFieldProps) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>Due date & time</Label>
            <Input
                id={id}
                name="deadline_date"
                type="datetime-local"
                disabled={disabled}
                defaultValue={toDateTimeLocalValue(defaultValue)}
            />
            <InputError message={error} />
        </div>
    );
}
