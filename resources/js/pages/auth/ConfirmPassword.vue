<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Şifreni doğrula',
        description:
            'Güvenliğin için devam etmeden önce mevcut şifreni doğrula.',
    },
});
</script>

<template>
    <Head title="Şifreyi doğrula" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-2">
                <Label htmlFor="password">Şifre</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center">
                <Button
                    class="h-11 w-full rounded-xl bg-[#183e2b] font-semibold text-white hover:bg-[#24583d]"
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" />
                    Devam et
                </Button>
            </div>
        </div>
    </Form>
</template>
