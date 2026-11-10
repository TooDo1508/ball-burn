import { reactive } from 'vue';

export type ConfirmDialogOptions = {
    title: string;
    description: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
};

export const confirmDialogState = reactive({
    open: false,
    title: '',
    description: '',
    confirmLabel: 'Xác nhận',
    cancelLabel: 'Hủy',
    destructive: false,
});

let resolveCurrent: ((confirmed: boolean) => void) | null = null;

export function confirmAction(options: ConfirmDialogOptions): Promise<boolean> {
    if (resolveCurrent) {
        resolveCurrent(false);
    }

    return new Promise((resolve) => {
        resolveCurrent = resolve;
        Object.assign(confirmDialogState, {
            ...options,
            confirmLabel: options.confirmLabel ?? 'Xác nhận',
            cancelLabel: options.cancelLabel ?? 'Hủy',
            open: true,
        });
    });
}

export function resolveConfirmAction(confirmed: boolean): void {
    const resolve = resolveCurrent;
    resolveCurrent = null;
    confirmDialogState.open = false;
    resolve?.(confirmed);
}
