<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';
import InputError from '@/components/InputError.vue';
/* @chisel-passkeys */
import PasskeyVerify from '@/components/PasskeyVerify.vue';
/* @end-chisel-passkeys */
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { DemoAccount } from '@/types/auth';

defineOptions({
    layout: {
        title: 'Entrá a tu cuenta',
        description: 'Ingresá tu email y tu contraseña para continuar',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();

// Las cuentas demo llegan como prop compartida desde HandleInertiaRequests.
// Si el modo demo está apagado el backend manda null y este bloque no se pinta.
const demoAccounts = computed<DemoAccount[]>(() => page.props.demo ?? []);

// Los campos están ligados con v-model para que el acceso demo pueda
// completarlos a la vista del usuario antes de enviar el formulario.
const email = ref('');
const password = ref('');
const pendingDemo = ref<string | null>(null);

/**
 * Un clic = login. Rellenamos los inputs, esperamos a que el DOM se actualice
 * (el <Form> de Inertia arma el payload leyendo el formulario real) y disparamos
 * el mismo submit que usa el botón normal, así los errores de validación y el
 * estado de "procesando" siguen funcionando igual.
 */
async function loginAs(account: DemoAccount, submit: () => void) {
    email.value = account.email;
    password.value = account.password;
    pendingDemo.value = account.email;

    await nextTick();

    submit();
}
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <!-- @chisel-passkeys -->
    <PasskeyVerify
        label="Entrar con una passkey"
        separator="O seguí con tu email"
    />
    <!-- @end-chisel-passkeys -->

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing, submit }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="email@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Contraseña</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm"
                        :tabindex="5"
                    >
                        ¿Olvidaste tu contraseña?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    v-model="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span>Mantener la sesión iniciada</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                Iniciar sesión
            </Button>
        </div>

        <!-- @chisel-registration -->
        <div class="text-center text-sm text-muted-foreground">
            ¿No tenés cuenta?
            <TextLink :href="register()" :tabindex="5">Creá una</TextLink>
        </div>
        <!-- @end-chisel-registration -->

        <!-- Acceso demo: un clic entra como administrador o como cliente. -->
        <div v-if="demoAccounts.length" class="flex flex-col gap-3">
            <Separator />

            <p
                class="text-center text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                Probar la demo
            </p>

            <div
                v-for="account in demoAccounts"
                :key="account.email"
                class="rounded-lg border border-border bg-card p-3"
            >
                <p class="text-sm font-medium">{{ account.role }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ account.description }}
                </p>

                <dl class="mt-2 space-y-1 text-xs">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground">Email</dt>
                        <dd class="truncate font-mono">{{ account.email }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted-foreground">Contraseña</dt>
                        <dd class="truncate font-mono">
                            {{ account.password }}
                        </dd>
                    </div>
                </dl>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="mt-3 w-full"
                    :disabled="processing"
                    @click="loginAs(account, submit)"
                >
                    <Spinner
                        v-if="processing && pendingDemo === account.email"
                    />
                    Entrar como {{ account.role }}
                </Button>
            </div>

            <p class="text-center text-xs text-muted-foreground">
                Son cuentas de demostración con datos de ejemplo: podés crear,
                cancelar y editar sin problema.
            </p>
        </div>
    </Form>
</template>
