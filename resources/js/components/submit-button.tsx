import { Button, type ButtonProps } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

type SubmitButtonProps = ButtonProps & {
    processing?: boolean;
};

export function SubmitButton({
    processing = false,
    disabled,
    children,
    ...props
}: SubmitButtonProps) {
    return (
        <Button type="submit" disabled={disabled || processing} {...props}>
            {processing && <Spinner />}
            {children}
        </Button>
    );
}
