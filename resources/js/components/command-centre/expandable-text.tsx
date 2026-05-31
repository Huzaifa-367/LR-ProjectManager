import { useState } from 'react';
import { cn } from '@/lib/utils';

type ExpandableTextProps = {
    text: string;
    maxLength?: number;
    className?: string;
    textClassName?: string;
};

export function ExpandableText({
    text,
    maxLength = 220,
    className,
    textClassName,
}: ExpandableTextProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const trimmedText = text.trim();
    const needsTruncation = trimmedText.length > maxLength;
    const displayText =
        isExpanded || !needsTruncation
            ? trimmedText
            : `${trimmedText.slice(0, maxLength).trimEnd()}…`;

    return (
        <div className={className}>
            <p
                className={cn(
                    'text-sm leading-relaxed whitespace-pre-wrap',
                    textClassName,
                )}
            >
                {displayText}
            </p>
            {needsTruncation && (
                <button
                    type="button"
                    onClick={() => setIsExpanded((expanded) => !expanded)}
                    className="mt-1.5 text-xs font-semibold text-primary hover:underline"
                >
                    {isExpanded ? 'Show less' : 'Show more'}
                </button>
            )}
        </div>
    );
}
