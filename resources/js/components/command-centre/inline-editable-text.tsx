import { Check, Pencil, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

type InlineEditableTextProps = {
    value: string;
    canEdit: boolean;
    onSave: (value: string) => void;
    multiline?: boolean;
    placeholder?: string;
    emptyLabel?: string;
    displayClassName?: string;
    inputClassName?: string;
    inputType?: 'text' | 'url';
};

export function InlineEditableText({
    value,
    canEdit,
    onSave,
    multiline = false,
    placeholder = 'Click to add…',
    emptyLabel = 'Empty',
    displayClassName,
    inputClassName,
    inputType = 'text',
}: InlineEditableTextProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [draft, setDraft] = useState(value);

    useEffect(() => {
        if (!isEditing) {
            setDraft(value);
        }
    }, [value, isEditing]);

    const commit = (): void => {
        const trimmed = draft.trim();

        if (trimmed !== value.trim()) {
            onSave(trimmed);
        }

        setIsEditing(false);
    };

    const cancel = (): void => {
        setDraft(value);
        setIsEditing(false);
    };

    if (!canEdit) {
        return (
            <p
                className={cn(
                    'text-sm whitespace-pre-wrap',
                    !value && 'text-muted-foreground italic',
                    displayClassName,
                )}
            >
                {value || emptyLabel}
            </p>
        );
    }

    if (isEditing) {
        return (
            <div className="space-y-2">
                {multiline ? (
                    <Textarea
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                        placeholder={placeholder}
                        rows={6}
                        className={cn('min-h-[8rem]', inputClassName)}
                        autoFocus
                        onKeyDown={(event) => {
                            if (event.key === 'Escape') {
                                cancel();
                            }
                        }}
                    />
                ) : (
                    <Input
                        type={inputType}
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                        placeholder={placeholder}
                        className={inputClassName}
                        autoFocus
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                commit();
                            }

                            if (event.key === 'Escape') {
                                cancel();
                            }
                        }}
                    />
                )}
                <div className="flex gap-1">
                    <Button type="button" size="sm" onClick={commit}>
                        <Check className="size-4" />
                        Save
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="ghost"
                        onClick={cancel}
                    >
                        <X className="size-4" />
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <button
            type="button"
            onClick={() => setIsEditing(true)}
            className={cn(
                'group -mx-2 w-[calc(100%+1rem)] rounded-md px-2 py-1.5 text-left transition-colors hover:bg-muted/50',
                !value && 'text-muted-foreground italic',
                displayClassName,
            )}
        >
            <span className="flex items-start justify-between gap-2">
                <span className={cn('min-w-0 flex-1', multiline && 'whitespace-pre-wrap')}>
                    {value || placeholder}
                </span>
                <Pencil className="size-3.5 shrink-0 opacity-0 transition-opacity group-hover:opacity-60" />
            </span>
        </button>
    );
}
