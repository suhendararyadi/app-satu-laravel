import { School } from 'lucide-react';
import CreateTeamModal from '@/components/create-team-modal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

export default function OnboardingBanner() {
    return (
        <Alert>
            <School />
            <AlertTitle>Buat team sekolah</AlertTitle>
            <AlertDescription>
                <div className="flex items-center justify-between gap-4">
                    <span>
                        Buat team sekolah untuk mulai mengelola website sekolah
                        Anda. Team sekolah memungkinkan Anda mengelola konten,
                        profil, dan anggota tim.
                    </span>
                    <CreateTeamModal>
                        <Button size="sm" className="shrink-0">
                            Buat Team Sekolah
                        </Button>
                    </CreateTeamModal>
                </div>
            </AlertDescription>
        </Alert>
    );
}
