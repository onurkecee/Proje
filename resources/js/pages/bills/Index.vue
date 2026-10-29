<script setup lang="ts">
import { Head, Link, Form } from '@inertiajs/vue3';
import {
    CalendarClock,
    CircleCheck,
    Clock3,
    Plus,
    ReceiptText,
    TriangleAlert,
} from '@lucide/vue';
import BillController from '@/actions/App/Http/Controllers/BillController';
import BillPaymentController from '@/actions/App/Http/Controllers/BillPaymentController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatMoney } from '@/lib/money';

type BillType =
    | 'electricity'
    | 'water'
    | 'natural_gas'
    | 'phone'
    | 'internet'
    | 'other';

type Bill = {
    id: number;
    name: string;
    type: BillType;
    expectedAmountMinor: string;
    firstDueOn: string;
    repeatMonthly: boolean;
    endsOn: string | null;
    isActive: boolean;
    paidOccurrencesCount: number;
    canDelete: boolean;
};

type Occurrence = {
    id: number;
    billId: number;
    billName: string;
    billType: BillType;
    dueDate: string;
    amountMinor: string;
    overdue: boolean;
};

type Payment = {
    id: number;
    billName: string;
    billType: BillType;
    dueDate: string;
    paidAt: string;
    amountMinor: string;
    currency: string;
};

const props = defineProps<{
    bills: Bill[];
    upcoming: Occurrence[];
    recentPayments: Payment[];
    summary: {
        overdueCount: number;
        dueSoonCount: number;
        dueSoonMinor: string;
    };
}>();

const billTypeLabels: Record<BillType, string> = {
    electricity: 'Elektrik',
    water: 'Su',
    natural_gas: 'Doğal gaz',
    phone: 'Telefon',
    internet: 'İnternet',
    other: 'Diğer',
};

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat('tr-TR', { dateStyle: 'medium' }).format(
        new Date(`${value}T00:00:00`),
    );
}

function confirmDelete(event: Event): void {
    if (!window.confirm('Bu fatura tanımını silmek istiyor musun?')) {
        event.preventDefault();
    }
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Faturalar', href: '/bills' }],
    },
});
</script>

<template>
    <Head title="Faturalar" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Faturaların"
                description="Vade tarihlerini takip et; ödediğinde giderin ve hesap bakiyen otomatik güncellensin."
            />
            <Button as-child>
                <Link :href="BillController.create()"><Plus />Yeni fatura</Link>
            </Button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Gecikmiş ödeme</CardDescription>
                    <CardTitle
                        class="flex items-center gap-2 text-3xl tracking-tight"
                    >
                        <TriangleAlert class="size-6 text-rose-600" />
                        {{ summary.overdueCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground"
                    >Ödenmemiş ve vadesi geçmiş dönem</CardContent
                >
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Önümüzdeki 7 gün</CardDescription>
                    <CardTitle
                        class="flex items-center gap-2 text-3xl tracking-tight"
                    >
                        <CalendarClock class="size-6 text-amber-600" />
                        {{ summary.dueSoonCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Tahmini toplam {{ formatMoney(summary.dueSoonMinor) }}
                </CardContent>
            </Card>
            <Card class="flex items-center gap-4 bg-primary/[0.04] p-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <CircleCheck class="size-6" />
                </div>
                <div>
                    <p class="font-medium">Ödeme kaydı tek adım</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ödeme seçtiğin TRY hesabına gider ve gider hareketi
                        olarak kaydedilir.
                    </p>
                </div>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Yaklaşan vadeler</CardTitle>
                <CardDescription
                    >Ödenmemiş faturalar, vade tarihine göre
                    sıralı.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="upcoming.length" class="divide-y">
                    <article
                        v-for="occurrence in upcoming"
                        :key="occurrence.id"
                        class="flex flex-col gap-3 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                            >
                                <Clock3 class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-medium">
                                        {{ occurrence.billName }}
                                    </p>
                                    <Badge
                                        :variant="
                                            occurrence.overdue
                                                ? 'destructive'
                                                : 'outline'
                                        "
                                    >
                                        {{
                                            occurrence.overdue
                                                ? 'Gecikmiş'
                                                : billTypeLabels[
                                                      occurrence.billType
                                                  ]
                                        }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Vade: {{ dateLabel(occurrence.dueDate) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 sm:justify-end"
                        >
                            <p class="shrink-0 font-semibold tabular-nums">
                                {{ formatMoney(occurrence.amountMinor) }}
                            </p>
                            <Button as-child size="sm">
                                <Link
                                    :href="
                                        BillPaymentController.create({
                                            bill: occurrence.billId,
                                            occurrence: occurrence.id,
                                        })
                                    "
                                >
                                    Öde
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center px-6 py-10 text-center"
                >
                    <CalendarClock class="mb-3 size-8 text-muted-foreground" />
                    <h3 class="font-semibold">Yaklaşan vade yok</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Fatura eklediğinde ödeme tarihleri burada görünür.
                    </p>
                </div>
            </CardContent>
        </Card>

        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Fatura tanımları</h2>
                <p class="text-sm text-muted-foreground">
                    Tek seferlik veya aylık tekrarlayan faturaların.
                </p>
            </div>
            <div v-if="bills.length" class="grid gap-4 md:grid-cols-2">
                <Card v-for="bill in bills" :key="bill.id">
                    <CardHeader
                        class="flex-row items-start justify-between space-y-0"
                    >
                        <div class="min-w-0">
                            <CardDescription>{{
                                billTypeLabels[bill.type]
                            }}</CardDescription>
                            <CardTitle class="mt-1 truncate text-lg">{{
                                bill.name
                            }}</CardTitle>
                        </div>
                        <Badge
                            :variant="bill.isActive ? 'secondary' : 'outline'"
                        >
                            {{ bill.isActive ? 'Aktif' : 'Arşivlendi' }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-end justify-between gap-3">
                            <div>
                                <p
                                    class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                                >
                                    Beklenen tutar
                                </p>
                                <p
                                    class="mt-1 text-2xl font-semibold tracking-tight"
                                >
                                    {{ formatMoney(bill.expectedAmountMinor) }}
                                </p>
                            </div>
                            <div
                                class="text-right text-sm text-muted-foreground"
                            >
                                <p>
                                    {{
                                        bill.repeatMonthly
                                            ? 'Her ay'
                                            : 'Tek seferlik'
                                    }}
                                </p>
                                <p>İlk vade {{ dateLabel(bill.firstDueOn) }}</p>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between border-t pt-3 text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Ödenen dönem</span
                            >
                            <span class="font-medium">{{
                                bill.paidOccurrencesCount
                            }}</span>
                        </div>
                        <div class="flex gap-2">
                            <Button as-child variant="outline" class="flex-1">
                                <Link :href="BillController.edit(bill.id)"
                                    >Düzenle</Link
                                >
                            </Button>
                            <Form
                                v-if="bill.canDelete"
                                v-bind="BillController.destroy.form(bill.id)"
                                @submit="confirmDelete"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    class="text-destructive"
                                >
                                    Sil
                                </Button>
                            </Form>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="border-dashed">
                <CardContent
                    class="flex flex-col items-center px-6 py-12 text-center"
                >
                    <ReceiptText class="mb-3 size-8 text-muted-foreground" />
                    <h3 class="font-semibold">Henüz fatura tanımlamadın</h3>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Elektrik, su, doğal gaz, telefon veya internet
                        faturalarını ekleyerek vadelerini takip et.
                    </p>
                    <Button as-child class="mt-5">
                        <Link :href="BillController.create()"
                            ><Plus />İlk faturayı ekle</Link
                        >
                    </Button>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Son ödemeler</CardTitle>
                <CardDescription
                    >Fatura ödemesi olarak kaydedilen son 10 gider
                    hareketi.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="recentPayments.length" class="divide-y">
                    <div
                        v-for="payment in recentPayments"
                        :key="payment.id"
                        class="flex items-center justify-between gap-4 py-3 first:pt-0"
                    >
                        <div>
                            <p class="font-medium">{{ payment.billName }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ dateLabel(payment.paidAt) }} · Vade
                                {{ dateLabel(payment.dueDate) }}
                            </p>
                        </div>
                        <p class="shrink-0 font-semibold tabular-nums">
                            {{
                                formatMoney(
                                    payment.amountMinor,
                                    payment.currency,
                                )
                            }}
                        </p>
                    </div>
                </div>
                <p v-else class="py-4 text-sm text-muted-foreground">
                    Henüz fatura ödemesi kaydedilmedi.
                </p>
            </CardContent>
        </Card>
    </div>
</template>
