import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    onConfirm: () => void | Promise<void>;
    processing?: boolean;
}

export default function ConfirmDeleteDialog({
    open,
    onOpenChange,
    title = 'Konfirmasi Hapus',
    description = 'Tindakan ini tidak bisa dibatalkan.',
    onConfirm,
    processing = false,
}: Props) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={(e) => {
                            if (processing) {
                                e.preventDefault();
                            }

                            onConfirm();
                        }}
                        disabled={processing}
                        className="bg-destructive text-white hover:bg-destructive/90"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
