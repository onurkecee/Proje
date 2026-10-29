<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Account = {
    id: number;
    name: string;
    type: 'bank' | 'cash' | 'credit_card';
    openingBalance: string;
    isActive: boolean;
    canDelete: boolean;
};

function confirmDelete(event: MouseEvent, name: string): void {
    if (
        !window.confirm(
            `“${name}” hesabını kalıcı olarak silmek istiyor musun?`,
        )
    ) {
        event.preventDefault();
    }
}

defineProps<{
    account: Account | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Hesaplar', href: '/accounts' },
            { title: 'Hesap formu', href: '/accounts/create' },
        ],
    },
});
</script>

<template>
    <Head :title="account ? 'Hesabı düzenle' : 'Yeni hesap'" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="AccountController.index()"
                    ><ArrowLeft />Hesaplara dön</Link
                >
            </Button>
            <Heading
                :title="account ? 'Hesabı düzenle' : 'Yeni hesap oluştur'"
                description="Hesap türünü ve başlangıç bakiyesini belirle. Tutar kuruş hassasiyetinde saklanır."
            />
        </header>

        <Form
            v-bind="
                account
                    ? AccountController.update.form(account.id)
                    : AccountController.store.form()
            "
            class="space-y-6 rounded-2xl border bg-card p-5 shadow-sm sm:p-8"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Hesap adı</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="account?.name"
                    placeholder="Örn. Maaş hesabım"
                    required
                    maxlength="80"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Hesap türü</Label>
                <select
                    id="type"
                    name="type"
                    :value="account?.type ?? 'bank'"
                    required
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="bank">Banka hesabı</option>
                    <option value="cash">Nakit</option>
                    <option value="credit_card">Kredi kartı</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="opening_balance">Açılış bakiyesi (₺)</Label>
                <Input
                    id="opening_balance"
                    name="opening_balance"
                    type="text"
                    inputmode="decimal"
                    :default-value="account?.openingBalance ?? '0,00'"
                    placeholder="0,00"
                    autocomplete="off"
                    required
                />
                <p class="text-xs text-muted-foreground">
                    Örnek: 1250,50. Kuruş için nokta veya virgül
                    kullanabilirsin. Kredi kartı açılış borcunu eksi tutar
                    olarak gir (ör. -2500,00).
                </p>
                <InputError :message="errors.opening_balance" />
            </div>

            <div
                v-if="account"
                class="flex items-start gap-3 rounded-lg border p-4"
            >
                <input
                    id="is_active"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    :checked="account.isActive"
                    class="mt-1 size-4 rounded border-input accent-primary"
                />
                <div>
                    <Label for="is_active">Hesap aktif</Label>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pasif hesaplar yeni hareket girişinde listelenmez.
                    </p>
                </div>
                <InputError :message="errors.is_active" />
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
            >
                <Button as-child variant="outline">
                    <Link :href="AccountController.index()">Vazgeç</Link>
                </Button>
                <Button :disabled="processing">
                    {{
                        processing
                            ? 'Kaydediliyor…'
                            : account
                              ? 'Değişiklikleri kaydet'
                              : 'Hesabı oluştur'
                    }}
                </Button>
            </div>
        </Form>

        <Card v-if="account?.canDelete" class="border-destructive/30">
            <CardHeader>
                <CardTitle class="text-base text-destructive"
                    >Hesabı sil</CardTitle
                >
                <CardDescription
                    >Bu hesapta henüz hareket yok. Silinen hesap geri
                    alınamaz.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form v-bind="AccountController.destroy.form(account.id)">
                    <Button
                        variant="destructive"
                        type="submit"
                        @click="confirmDelete($event, account.name)"
                    >
                        Hesabı sil
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
