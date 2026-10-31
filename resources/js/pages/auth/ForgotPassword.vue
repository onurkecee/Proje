<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Şifreni mi unuttun?',
        description:
            'Şifreni yenilemen için e-posta adresine bir bağlantı gönderelim.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Şifremi unuttum" />

    <div
        v-if="status"
        class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
    >
        {{ status }}
    </div>

    <div class="space-y-6">
        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
            <div class="grid gap-2">
                <Label for="email">E-posta adresi</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                    v-focus
                    placeholder="ornek@eposta.com"
                    class="h-11 rounded-xl"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="my-6 flex items-center justify-start">
                <Button
                    class="h-11 w-full rounded-xl bg-[#183e2b] font-semibold text-white hover:bg-[#24583d]"
                    :disabled="processing"
                    data-test="email-password-reset-link-button"
                >
                    <Spinner v-if="processing" />
                    Şifre yenileme bağlantısı gönder
                </Button>
            </div>
        </Form>

        <div class="space-x-1 text-center text-sm text-muted-foreground">
            <span>Şifreni hatırladın mı?</span>
            <TextLink :href="login()" class="font-semibold text-[#416149]"
                >Giriş yap</TextLink
            >
        </div>
    </div>
</template>
