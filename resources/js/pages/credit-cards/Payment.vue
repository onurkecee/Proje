<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowDownUp } from '@lucide/vue';
import CreditCardPaymentController from '@/actions/App/Http/Controllers/CreditCardPaymentController';
import CreditCardStatementController from '@/actions/App/Http/Controllers/CreditCardStatementController';
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

type AccountOption = { id: number; name: string; currency: string };
const props = defineProps<{
    statement: {
        id: number;
        cardName: string;
        currency: string;
        dueOn: string;
        amountMinor: string;
        paidAmountMinor: string;
        remainingMinor: string;
        amount: string;
    };
    accounts: AccountOption[];
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kart ekstreleri', href: '/credit-card-statements' },
            { title: 'Ekstre ödemesi', href: '/credit-card-statements' },
        ],
    },
});
</script>

<template>
    <Head :title="`${statement.cardName} ekstresi ödemesi`" />
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="CreditCardStatementController.index()"
                    ><ArrowLeft />Ekstre listesine dön</Link
                >
            </Button>
            <Heading
                title="Kart ekstresi ödemesi"
                description="Ödeme, seçilen hesaptan kredi kartı hesabına transfer olur; gider olarak ikinci kez sayılmaz."
            />
        </header>

        <Card class="border-primary/20 bg-primary/[0.03]">
            <CardHeader class="flex-row items-center gap-4 space-y-0">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <ArrowDownUp class="size-6" />
                </div>
                <div>
                    <CardDescription
                        >{{ statement.cardName }} · Son ödeme
                        {{ statement.dueOn }}</CardDescription
                    >
                    <CardTitle class="mt-1 text-2xl">
                        {{
                            formatMoney(
                                statement.remainingMinor,
                                statement.currency,
                            )
                        }}
                        kaldı
                    </CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ödenen
                        {{
                            formatMoney(
                                statement.paidAmountMinor,
                                statement.currency,
                            )
                        }}
                        /
                        {{
                            formatMoney(
                                statement.amountMinor,
                                statement.currency,
                            )
                        }}
                    </p>
                </div>
            </CardHeader>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Transfer bilgileri</CardTitle>
                <CardDescription
                    >Kısmi ödeme yapabilir, kalan ekstre borcunu daha sonra
                    tamamlayabilirsin.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="accounts.length === 0" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Ödeme için aynı para biriminde aktif banka veya nakit
                        hesabı gerekir.
                    </p>
                    <Button as-child variant="outline"
                        ><Link href="/accounts/create">Hesap ekle</Link></Button
                    >
                </div>
                <Form
                    v-else
                    v-bind="
                        CreditCardPaymentController.store.form(statement.id)
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
                                    {{ account.name }} · {{ account.currency }}
                                </option>
                            </select>
                            <InputError :message="errors.account_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="amount">Transfer tutarı</Label>
                            <Input
                                id="amount"
                                name="amount"
                                :default-value="statement.amount"
                                inputmode="decimal"
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Kalan tutardan fazla olamaz.
                            </p>
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
                            ><Link :href="CreditCardStatementController.index()"
                                >Vazgeç</Link
                            ></Button
                        >
                        <Button type="submit" :disabled="processing">{{
                            processing ? 'Kaydediliyor…' : 'Transferi kaydet'
                        }}</Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
