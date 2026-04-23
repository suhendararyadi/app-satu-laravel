import { Head } from '@inertiajs/react';

import AdminDashboard from '@/components/dashboard/admin-dashboard';
import ParentDashboard from '@/components/dashboard/parent-dashboard';
import StudentDashboard from '@/components/dashboard/student-dashboard';
import TeacherDashboard from '@/components/dashboard/teacher-dashboard';
import OnboardingBanner from '@/components/onboarding-banner';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard(props: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {!props.hasSchoolTeam && <OnboardingBanner />}

                {props.hasSchoolTeam && props.role === 'owner' && (
                    <AdminDashboard data={props.data} />
                )}
                {props.hasSchoolTeam && props.role === 'admin' && (
                    <AdminDashboard data={props.data} />
                )}
                {props.hasSchoolTeam && props.role === 'teacher' && (
                    <TeacherDashboard data={props.data} />
                )}
                {props.hasSchoolTeam && props.role === 'student' && (
                    <StudentDashboard data={props.data} />
                )}
                {props.hasSchoolTeam && props.role === 'parent' && (
                    <ParentDashboard data={props.data} />
                )}
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
