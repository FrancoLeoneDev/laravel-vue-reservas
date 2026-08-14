<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock,
    FolderGit2,
    Scissors,
    Store,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { home } from '@/routes';
import { agenda } from '@/routes/admin';
import { index as availabilityIndex } from '@/routes/admin/availability';
import { index as servicesIndex } from '@/routes/admin/services';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Agenda',
        href: agenda(),
        icon: CalendarDays,
    },
    {
        title: 'Servicios',
        href: servicesIndex(),
        icon: Scissors,
    },
    {
        title: 'Disponibilidad',
        href: availabilityIndex(),
        icon: Clock,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Ver sitio público',
        href: home(),
        icon: Store,
    },
    {
        title: 'Código en GitHub',
        href: 'https://github.com/FrancoLeoneDev/laravel-vue-reservas',
        icon: FolderGit2,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="agenda()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
