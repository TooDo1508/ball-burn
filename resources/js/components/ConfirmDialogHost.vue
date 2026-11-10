<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { confirmDialogState, resolveConfirmAction } from '@/lib/confirmDialog';

function onOpenChange(open: boolean) {
    if (!open) resolveConfirmAction(false);
}
</script>

<template>
    <Dialog :open="confirmDialogState.open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md" :show-close-button="false">
            <DialogHeader>
                <DialogTitle>{{ confirmDialogState.title }}</DialogTitle>
                <DialogDescription>{{ confirmDialogState.description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="resolveConfirmAction(false)">{{ confirmDialogState.cancelLabel }}</Button>
                <Button :variant="confirmDialogState.destructive ? 'destructive' : 'default'" @click="resolveConfirmAction(true)">
                    {{ confirmDialogState.confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
