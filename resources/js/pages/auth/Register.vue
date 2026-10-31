<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Hesabını oluştur',
        description:
            'Kişisel finansını takip etmeye başlamak için bilgilerini gir.',
    },
});
</script>

<template>
    <Head title="Kayıt ol" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-4">
            <div class="grid gap-2">
                <Label for="name">Ad soyad</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Adını ve soyadını yaz"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">E-posta adresi</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="ornek@eposta.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Şifre</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="En az 8 karakter"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
                <p class="text-xs leading-5 text-[#849087]">
                    En az 8 karakter kullan. Büyük harf, rakam ve özel karakter
                    içeren güçlü bir şifre tercih et.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Şifre tekrarı</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Şifreni tekrar gir"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-1 h-11 w-full rounded-xl bg-[#183e2b] font-semibold text-white hover:bg-[#24583d]"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Hesap oluştur
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Zaten hesabın var mı?
            <TextLink
                :href="login()"
                class="font-semibold text-[#416149] underline-offset-4 hover:underline"
                :tabindex="6"
                >Giriş yap</TextLink
            >
        </div>
    </Form>
</template>
