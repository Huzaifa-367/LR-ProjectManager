import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type SelectOption = {
    value: string;
    label: string;
};

type SelectFieldProps = {
    id: string;
    name: string;
    label: string;
    options: readonly SelectOption[];
    defaultValue?: string;
    required?: boolean;
    disabled?: boolean;
    error?: string;
    className?: string;
};

export function SelectField({
    id,
    name,
    label,
    options,
    defaultValue,
    required,
    disabled,
    error,
    className,
}: SelectFieldProps) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{label}</Label>
            <select
                id={id}
                name={name}
                required={required}
                disabled={disabled}
                defaultValue={defaultValue}
                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs disabled:opacity-60"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <InputError message={error} />
        </div>
    );
}
