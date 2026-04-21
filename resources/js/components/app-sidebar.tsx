import { Link, usePage } from '@inertiajs/react';
import {
    AlarmClock,
    BookOpen,
    BookOpenCheck,
    Calendar,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    FileText,
    FolderGit2,
    FolderOpen,
    GraduationCap,
    Images,
    Layers,
    LayoutGrid,
    Newspaper,
    School,
    Users,
} from 'lucide-react';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import ClassroomController from '@/actions/App/Http/Controllers/Academic/ClassroomController';
import GradeController from '@/actions/App/Http/Controllers/Academic/GradeController';
import SubjectController from '@/actions/App/Http/Controllers/Academic/SubjectController';
import TeacherAssignmentController from '@/actions/App/Http/Controllers/Academic/TeacherAssignmentController';
import GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
import PostController from '@/actions/App/Http/Controllers/CMS/PostController';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
import SchoolProfileController from '@/actions/App/Http/Controllers/School/SchoolProfileController';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavGroups } from '@/components/nav-groups';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const slug = page.props.currentTeam?.slug ?? '';
    const dashboardUrl = slug ? dashboard(slug) : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
    ];

    const schoolNavItems: NavItem[] = [
        {
            title: 'Profil Sekolah',
            href: slug ? SchoolProfileController.edit.url(slug) : '/',
            icon: School,
        },
    ];

    const academicNavGroups: NavGroup[] = [
        {
            title: 'Akademik',
            icon: GraduationCap,
            items: [
                {
                    title: 'Tahun Ajaran',
                    href: slug ? AcademicYearController.index.url(slug) : '/',
                    icon: Calendar,
                },
                {
                    title: 'Tingkat',
                    href: slug ? GradeController.index.url(slug) : '/',
                    icon: Layers,
                },
                {
                    title: 'Mata Pelajaran',
                    href: slug ? SubjectController.index.url(slug) : '/',
                    icon: BookOpenCheck,
                },
                {
                    title: 'Kelas',
                    href: slug ? ClassroomController.index.url(slug) : '/',
                    icon: Users,
                },
                {
                    title: 'Penugasan Guru',
                    href: slug
                        ? TeacherAssignmentController.index.url(slug)
                        : '/',
                    icon: ClipboardList,
                },
            ],
        },
    ];

    const scheduleNavGroups: NavGroup[] = [
        {
            title: 'Jadwal & Absensi',
            icon: CalendarDays,
            items: [
                {
                    title: 'Jam Pelajaran',
                    href: slug ? TimeSlotController.index.url(slug) : '/',
                    icon: AlarmClock,
                },
                {
                    title: 'Jadwal',
                    href: slug ? ScheduleController.index.url(slug) : '/',
                    icon: CalendarDays,
                },
                {
                    title: 'Absensi',
                    href: slug ? AttendanceController.index.url(slug) : '/',
                    icon: ClipboardCheck,
                },
            ],
        },
    ];

    const cmsNavGroups: NavGroup[] = [
        {
            title: 'Manajemen Konten',
            icon: FolderOpen,
            items: [
                {
                    title: 'Halaman',
                    href: slug ? PageController.index.url(slug) : '/',
                    icon: FileText,
                },
                {
                    title: 'Artikel',
                    href: slug ? PostController.index.url(slug) : '/',
                    icon: Newspaper,
                },
                {
                    title: 'Galeri',
                    href: slug ? GalleryController.index.url(slug) : '/',
                    icon: Images,
                },
            ],
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={schoolNavItems} label="Sekolah" />
                <NavGroups groups={cmsNavGroups} label="Konten" />
                <NavGroups groups={academicNavGroups} label="Akademik" />
                <NavGroups
                    groups={scheduleNavGroups}
                    label="Jadwal & Absensi"
                />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
