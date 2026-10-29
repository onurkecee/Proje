<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ReceiptText } from '@lucide/vue';
import BillController from '@/actions/App/Http/Controllers/BillController';
import BillPaymentController from '@/actions/App/Http/Controllers/BillPaymentController';
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

type Option = {
    id: number;
    name: string;
    currency?: string;
};

const props = defineProps<{
    bill: { id: number; name: string; type: string };
    occurrence: {
        id: number;
        dueDate: string;
        amount: string;
        amountMinor: string;
    };
    accounts: Option[];
    categories: Option[];
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Faturalar', href: '/bills' },
            { title: 'Fatura ödemesi', href: '/bills' },
        ],
    },
});
</script>

<template>
    <Head :title="`${bill.name} ödemesi`" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="BillController.index()"
                    ><ArrowLeft />Faturalara dön</Link
                >
            </Button>
            <Heading
                title="Fatura ödemesini kaydet"
                description="Ödeme kaydedildiğinde seçilen hesaptan düşülür ve gider raporuna eklenir."
            />
        </header>

        <Card class="border-primary/20 bg-primary/[0.03]">
            <CardHeader class="flex-row items-center gap-4 space-y-0">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <ReceiptText class="size-6" />
                </div>
                <div class="min-w-0">
                    <CardDescription
                        >{{ bill.name }} · Vade
                        {{ occurrence.dueDate }}</CardDescription
                    >
                    <CardTitle class="mt-1 text-2xl">{{
                        formatMoney(occurrence.amountMinor)
                    }}</CardTitle>
                </div>
            </CardHeader>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Ödeme bilgileri</CardTitle>
                <CardDescription
                    >Beklenen tutardan farklı bir ödeme yaptıysan gerçek tutarı
                    yaz.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div
                    v-if="accounts.length === 0 || categories.length === 0"
                    class="space-y-3"
                >
                    <p class="text-sm text-muted-foreground">
                        Ödeme için en az bir aktif TRY hesabı ve bir aktif gider
                        kategorisi gerekir.
                    </p>
                    <Button as-child variant="outline">
                        <Link :href="BillController.index()"
                            >Faturalara dön</Link
                        >
                    </Button>
                </div>
                <Form
                    v-else
                    v-bind="
                        BillPaymentController.store.form({
                            bill: bill.id,
                            occurrence: occurrence.id,
                        })
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="account_id"
                                >Ödemenin yapılacağı hesap</Label
                            >
                            <select
                                id="account_id"
                                name="account_id"
                                required
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="" disabled selected>
                                    Hesap seçin
                                </option>
                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }} · {{ account.currency }}
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
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                            <Label for="amount"
                                >Gerçek ödenen tutar (TRY)</Label
                            >
                            <Input
                                id="amount"
                                name="amount"
                                :default-value="occurrence.amount"
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
                        <Button as-child variant="outline">
                            <Link :href="BillController.index()">Vazgeç</Link>
                        </Button>
                        <Button type="submit" :disabled="processing">
                            {{
                                processing ? 'Kaydediliyor…' : 'Ödemeyi kaydet'
                            }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
