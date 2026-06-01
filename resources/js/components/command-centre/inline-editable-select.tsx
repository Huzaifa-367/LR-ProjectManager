import { type ReactNode } from 'react';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const EMPTY_SENTINEL = '__empty__';

type SelectOption = {
    value: string;
    label: string;
};

type InlineEditableSelectProps = {
    value: string;
    options: SelectOption[];
    canEdit: boolean;
    onSave: (value: string) => void;
    renderValue?: (value: string) => ReactNode;
    placeholder?: string;
    allowEmpty?: boolean;
    emptyValue?: string;
};

function toSelectValue(
    value: string,
    emptyValue: string,
    allowEmpty: boolean,
): string {
    if (allowEmpty && (value === '' || value === emptyValue)) {
        return EMPTY_SENTINEL;
    }

    return value;
}

function fromSelectValue(selected: string, emptyValue: string): string {
    if (selected === EMPTY_SENTINEL) {
        return emptyValue;
    }

    return selected;
}

export function InlineEditableSelect({
    value,
    options,
    canEdit,
    onSave,
    renderValue,
    placeholder = 'Not set',
    allowEmpty = false,
    emptyValue = '',
}: InlineEditableSelectProps) {
    const resolvedValue = value || emptyValue;
    const selectValue = toSelectValue(resolvedValue, emptyValue, allowEmpty);

    const display =
        renderValue?.(resolvedValue === emptyValue ? '' : value) ??
        options.find((option) => option.value === value)?.label ??
        (resolvedValue === emptyValue ? placeholder : value) ??
        placeholder;

    const selectedLabel =
        options.find((option) => option.value === value)?.label ??
        (allowEmpty && resolvedValue === emptyValue ? placeholder : null);

    if (!canEdit) {
        return <div className="text-sm">{display}</div>;
    }

    return (
        <Select
            value={selectValue}
            onValueChange={(next) => {
                const parsed = fromSelectValue(next, emptyValue);

                if (parsed !== resolvedValue) {
                    onSave(parsed);
                }
            }}
        >
            <SelectTrigger className="h-9 w-full">
                {renderValue ? (
                    <span className="flex min-w-0 flex-1 items-center truncate">
                        {renderValue(
                            resolvedValue === emptyValue ? '' : value,
                        )}
                    </span>
                ) : (
                    <SelectValue placeholder={placeholder}>
                        {selectedLabel ?? placeholder}
                    </SelectValue>
                )}
            </SelectTrigger>
            <SelectContent>
                {allowEmpty && (
                    <SelectItem value={EMPTY_SENTINEL}>
                        {placeholder}
                    </SelectItem>
                )}
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
