<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarCheck, LayoutDashboard, LogIn, Scissors } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Toaster } from '@/components/ui/sonner';
import { home, login, register } from '@/routes';
import { agenda } from '@/routes/admin';
import { index as myBookings } from '@/routes/bookings';

const page = usePage();

const user = computed(() => page.props.auth?.user ?? null);
const isAdmin = computed(() => page.props.auth?.isAdmin ?? false);
const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <header
            class="sticky top-0 z-40 border-b border-border/60 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2.5 font-semibold tracking-tight"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-lg bg-foreground text-background"
                    >
                        <Scissors class="size-4.5" />
                    </span>
                    <span class="text-base leading-none">
                        Nova Studio
                        <span
                            class="block text-xs font-normal text-muted-foreground"
                        >
                            Peluquería
                        </span>
                    </span>
                </Link>

                <nav class="flex items-center gap-2">
                    <template v-if="user">
                        <Button
                            v-if="isAdmin"
                            as-child
                            variant="ghost"
                            size="sm"
                        >
                            <Link :href="agenda()">
                                <LayoutDashboard />
                                Panel
                            </Link>
                        </Button>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="myBookings()">
                                <CalendarCheck />
                                Mis reservas
                            </Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="login()">
                                <LogIn />
                                Iniciar sesión
                            </Link>
                        </Button>
                        <Button as-child size="sm" class="hidden sm:inline-flex">
                            <Link :href="register()">Crear cuenta</Link>
                        </Button>
                    </template>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-border/60 py-8">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <p>© {{ year }} Nova Studio — Sistema de reservas de turnos.</p>
                <a
                    href="https://github.com/FrancoLeoneDev/laravel-vue-reservas"
                    target="_blank"
                    rel="noopener"
                    class="underline-offset-4 transition-colors hover:text-foreground hover:underline"
                >
                    Ver el código en GitHub
                </a>
            </div>
        </footer>

        <Toaster rich-colors close-button />
    </div>
</template>
