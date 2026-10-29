<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
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

type AccountOption = { id: number; name: string; currency: string };
type StatementForm = {
    id: number;
    accountId: number;
    periodStart: string;
    periodEnd: string;
    dueOn: string;
    amount: string;
};

const props = defineProps<{
    statement: StatementForm | null;
    cards: AccountOption[];
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kart ekstreleri', href: '/credit-card-statements' },
            { title: 'Ekstre formu', href: '/credit-card-statements/create' },
        ],
    },
});
</script>

<template>
    <Head :title="statement ? 'Ekstreyi düzenle' : 'Yeni kart ekstresi'" />
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="CreditCardStatementController.index()"
                    ><ArrowLeft />Ekstre listesine dön</Link
                >
            </Button>
            <Heading
                :title="statement ? 'Ekstreyi düzenle' : 'Kart ekstresi ekle'"
                description="Kart ekstresi tutarını ve vadesini manuel kaydet; ödemeler transfer olarak işlenir."
            />
        </header>

        <Card>
            <CardHeader>
                <CardTitle>Ekstre bilgileri</CardTitle>
                <CardDescription
                    >Bu tutar gider raporuna eklenmez; kart alışverişleri zaten
                    gider olarak kaydedilir.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="cards.length === 0" class="space-y-3">
                    <p class="text-sm text-muted-foreground">
                        Ekstre eklemek için önce bir kredi kartı hesabı oluştur.
                    </p>
                    <Button as-child variant="outline">
                        <Link href="/accounts/create"
                            >Kredi kartı hesabı ekle</Link
                        >
                    </Button>
                </div>
                <Form
                    v-else
                    v-bind="
                        statement
                            ? CreditCardStatementController.update.form(
                                  statement.id,
                              )
                            : CreditCardStatementController.store.form()
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="account_id">Kredi kartı</Label>
                            <select
                                id="account_id"
                                name="account_id"
                                :value="statement?.accountId ?? ''"
                                required
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="" disabled>Kart seçin</option>
                                <option
                                    v-for="card in cards"
                                    :key="card.id"
                                    :value="card.id"
                                >
                                    {{ card.name }} · {{ card.currency }}
                                </option>
                            </select>
                            <InputError :message="errors.account_id" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="period_start">Dönem başlangıcı</Label>
                            <Input
                                id="period_start"
                                name="period_start"
                                type="date"
                                :default-value="statement?.periodStart ?? today"
                                required
                            />
                            <InputError :message="errors.period_start" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="period_end">Dönem bitişi</Label>
                            <Input
                                id="period_end"
                                name="period_end"
                                type="date"
                                :default-value="statement?.periodEnd ?? today"
                                required
                            />
                            <InputError :message="errors.period_end" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="due_on">Son ödeme tarihi</Label>
                            <Input
                                id="due_on"
                                name="due_on"
                                type="date"
                                :default-value="statement?.dueOn ?? today"
                                required
                            />
                            <InputError :message="errors.due_on" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="amount">Ekstre borcu (TRY)</Label>
                            <Input
                                id="amount"
                                name="amount"
                                inputmode="decimal"
                                placeholder="0,00"
                                :default-value="statement?.amount"
                                required
                            />
                            <InputError :message="errors.amount" />
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
                        <Button type="submit" :disabled="processing">
                            {{
                                processing
                                    ? 'Kaydediliyor…'
                                    : statement
                                      ? 'Değişiklikleri kaydet'
                                      : 'Ekstreyi kaydet'
                            }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
