<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Banknote } from '@lucide/vue';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import LoanPaymentController from '@/actions/App/Http/Controllers/LoanPaymentController';
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
import { formatMoney } from '@/lib/money';

type Option = { id: number; name: string };
const props = defineProps<{
    loan: { id: number; name: string; lender: string | null };
    installment: {
        id: number;
        number: number;
        dueOn: string;
        amountMinor: string;
        paidAmountMinor: string;
        remainingMinor: string;
        amount: string;
    };
    accounts: Option[];
    categories: Option[];
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Krediler', href: '/loans' },
            { title: 'Taksit ödemesi', href: '/loans' },
        ],
    },
});
</script>

<template>
    <Head :title="`${loan.name} taksit ödemesi`" />
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="LoanController.index()"
                    ><ArrowLeft />Kredilere dön</Link
                >
            </Button>
            <Heading
                title="Kredi taksit ödemesi"
                description="Ödeme, seçtiğin hesaptan düşer ve gider hareketi olarak kaydedilir."
            />
        </header>
        <Card class="border-primary/20 bg-primary/[0.03]">
            <CardHeader class="flex-row items-center gap-4 space-y-0">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <Banknote class="size-6" />
                </div>
                <div>
                    <CardDescription
                        >{{ loan.name }} · {{ installment.number }}. taksit ·
                        Vade {{ installment.dueOn }}</CardDescription
                    >
                    <CardTitle class="mt-1 text-2xl"
                        >{{
                            formatMoney(installment.remainingMinor)
                        }}
                        kaldı</CardTitle
                    >
                    <p class="mt-1 text-sm text-muted-foreground">
                        Taksit toplamı
                        {{ formatMoney(installment.amountMinor) }} · Ödenen
                        {{ formatMoney(installment.paidAmountMinor) }}
                    </p>
                </div>
            </CardHeader>
        </Card>
        <Card>
            <CardHeader>
                <CardTitle>Ödeme bilgileri</CardTitle>
                <CardDescription
                    >Kalan tutarın tamamını veya bir kısmını
                    ödeyebilirsin.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div
                    v-if="accounts.length === 0 || categories.length === 0"
                    class="space-y-3"
                >
                    <p class="text-sm text-muted-foreground">
                        Ödeme için aktif bir hesap ve gider kategorisi gerekir.
                    </p>
                    <Button as-child variant="outline"
                        ><Link :href="LoanController.index()"
                            >Kredilere dön</Link
                        ></Button
                    >
                </div>
                <Form
                    v-else
                    v-bind="
                        LoanPaymentController.store.form({
                            loan: loan.id,
                            installment: installment.id,
                        })
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="account_id">Ödeme hesabı</Label>
                            <select
                                id="account_id"
                                name="account_id"
                                required
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled selected>
                                    Hesap seçin
                                </option>
                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }}
                                </option>
                            </select>
                            <InputError :message="errors.account_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="category_id">Gider kategorisi</Label>
                            <select
                                id="category_id"
                                name="category_id"
                                required
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="" disabled selected>
                                    Kategori seçin
                                </option>
                                <option
                                    v-for="category in categories"
                                    :key="category.id"
                                    :value="category.id"
                                >
                                    {{ category.name }}
                                </option>
                            </select>
                            <InputError :message="errors.category_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="amount">Ödenen tutar (TRY)</Label>
                            <Input
                                id="amount"
                                name="amount"
                                :default-value="installment.amount"
                                inputmode="decimal"
                                required
                            />
                            <InputError :message="errors.amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="paid_at">Ödeme tarihi</Label>
                            <Input
                                id="paid_at"
                                name="paid_at"
                                type="date"
                                :default-value="today"
                                :max="today"
                                required
                            />
                            <InputError :message="errors.paid_at" />
                        </div>
                    </div>
                    <div
                        class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
                    >
                        <Button as-child variant="outline"
                            ><Link :href="LoanController.index()"
                                >Vazgeç</Link
                            ></Button
                        >
                        <Button type="submit" :disabled="processing">{{
                            processing ? 'Kaydediliyor…' : 'Ödemeyi kaydet'
                        }}</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
