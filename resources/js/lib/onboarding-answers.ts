export type QuestionSuggestion = {
    label: string;
    value: string;
    mode: 'replace' | 'append';
};

function normalizeListLine(value: string): string {
    return value.startsWith('- ') ? value : `- ${value}`;
}

function answerContainsValue(answer: string, value: string): boolean {
    const normalized = normalizeListLine(value).trim();

    return answer
        .split('\n')
        .map((line) => line.trim())
        .some(
            (line) =>
                line === normalized ||
                line === value.trim() ||
                line === `- ${value.trim()}`,
        );
}

export function applySuggestion(
    current: string,
    suggestion: QuestionSuggestion,
): string {
    if (suggestion.mode === 'replace') {
        return suggestion.value;
    }

    const line = normalizeListLine(suggestion.value);
    const lines = current
        .split('\n')
        .map((entry) => entry.trim())
        .filter((entry) => entry !== '');

    if (answerContainsValue(current, suggestion.value)) {
        return lines
            .filter(
                (entry) =>
                    entry !== line.trim() &&
                    entry !== suggestion.value.trim() &&
                    entry !== `- ${suggestion.value.trim()}`,
            )
            .join('\n');
    }

    return current.trim() === '' ? line : `${current.trim()}\n${line}`;
}

export function isSuggestionSelected(
    current: string,
    suggestion: QuestionSuggestion,
): boolean {
    if (suggestion.mode === 'replace') {
        return current.trim() === suggestion.value.trim();
    }

    return answerContainsValue(current, suggestion.value);
}
