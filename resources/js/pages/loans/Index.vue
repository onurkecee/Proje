<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    Banknote,
    CalendarClock,
    CircleCheck,
    Plus,
    TriangleAlert,
} from '@lucide/vue';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import LoanPaymentController from '@/actions/App/Http/Controllers/LoanPaymentController';
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

type Installment = {
    id: number;
    loanId: number;
    loanName: string;
    installmentNumber: number;
    dueOn: string;
    amountMinor: string;
    paidAmountMinor: string;
    remainingMinor: string;
    paid: boolean;
    overdue: boolean;
};

type Loan = {
    id: number;
    name: string;
    lender: string | null;
    totalRepaymentMinor: string;
    installmentAmountMinor: string;
    installmentCount: number;
    firstDueOn: string;
    isActive: boolean;
    paidInstallments: number;
    paidMinor: string;
    remainingMinor: string;
    nextInstallment: {
        id: number;
        number: number;
        dueOn: string;
        amountMinor: string;
        remainingMinor: string;
    } | null;
    canEdit: boolean;
};

const props = defineProps<{ loans: Loan[]; installments: Installment[] }>();
const openInstallments = props.installments.filter((item) => !item.paid);
const overdueCount = openInstallments.filter((item) => item.overdue).length;
const dueSoonMinor = openInstallments
    .filter((item) => {
        const days = Math.floor(
            (new Date(`${item.dueOn}T00:00:00`).getTime() -
                new Date().setHours(0, 0, 0, 0)) /
                86_400_000,
        );
        return days >= 0 && days <= 7;
    })
    .reduce((sum, item) => sum + BigInt(item.remainingMinor), 0n)
    .toString();

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat('tr-TR', { dateStyle: 'medium' }).format(
        new Date(`${value}T00:00:00`),
    );
}

function confirmDelete(event: Event): void {
    if (
        !window.confirm(
            'Bu kredi planını ve ödenmemiş taksitlerini silmek istiyor musun?',
        )
    ) {
        event.preventDefault();
    }
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Krediler', href: '/loans' }],
    },
});
</script>

<template>
    <Head title="Kredi ve taksitler" />
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Kredi ve taksitler"
                description="Sözleşmedeki toplam geri ödeme tutarını ve aylık vadeleri takip et."
            />
            <Button as-child
                ><Link :href="LoanController.create()"
                    ><Plus />Kredi ekle</Link
                ></Button
            >
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Gecikmiş taksit</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <TriangleAlert class="size-6 text-rose-600" />{{
                            overdueCount
                        }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground"
                    >Ödenmemiş, vadesi geçmiş taksit</CardContent
                >
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Önümüzdeki 7 gün</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <CalendarClock class="size-6 text-amber-600" />
                        {{ formatMoney(dueSoonMinor) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground"
                    >Yaklaşan taksitlerin kalan tutarı</CardContent
                >
            </Card>
            <Card class="flex items-center gap-4 bg-primary/[0.04] p-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <CircleCheck class="size-6" />
                </div>
                <div>
                    <p class="font-medium">Kuruş hassasiyetinde</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ödemeler gider hareketidir; son taksit toplam sözleşme
                        tutarına göre dengelenir.
                    </p>
                </div>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Taksit takvimi</CardTitle>
                <CardDescription
                    >Kısmi ödemeler dahil kalan taksit
                    tutarları.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <div v-if="installments.length" class="divide-y">
                    <article
                        v-for="installment in installments"
                        :key="installment.id"
                        class="flex flex-col gap-3 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">
                                    {{ installment.loanName }} ·
                                    {{ installment.installmentNumber }}. taksit
                                </p>
                                <Badge
                                    :variant="
                                        installment.paid
                                            ? 'secondary'
                                            : installment.overdue
                                              ? 'destructive'
                                              : 'outline'
                                    "
                                >
                                    {{
                                        installment.paid
                                            ? 'Ödendi'
                                            : installment.overdue
                                              ? 'Gecikmiş'
                                              : BigInt(
                                                      installment.paidAmountMinor,
                                                  ) > 0n
                                                ? 'Kısmi ödendi'
                                                : 'Bekliyor'
                                    }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Vade {{ dateLabel(installment.dueOn) }}
                            </p>
                            <p
                                v-if="BigInt(installment.paidAmountMinor) > 0n"
                                class="text-xs text-muted-foreground"
                            >
                                Ödenen
                                {{ formatMoney(installment.paidAmountMinor) }} /
                                {{ formatMoney(installment.amountMinor) }}
                            </p>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 sm:justify-end"
                        >
                            <div class="text-right">
                                <p class="font-semibold tabular-nums">
                                    {{
                                        formatMoney(installment.remainingMinor)
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Kalan tutar
                                </p>
                            </div>
                            <Button v-if="!installment.paid" as-child size="sm">
                                <Link
                                    :href="
                                        LoanPaymentController.create({
                                            loan: installment.loanId,
                                            installment: installment.id,
                                        })
                                    "
                                >
                                    Ödeme ekle
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
                    <h3 class="font-semibold">Henüz taksit planı yok</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Kredi eklediğinde taksit takvimi burada görünür.
                    </p>
                </div>
            </CardContent>
        </Card>

        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-semibold">Kredi planların</h2>
                <p class="text-sm text-muted-foreground">
                    Toplam geri ödeme ve kalan sözleşme yükümlülüğü.
                </p>
            </div>
            <div v-if="loans.length" class="grid gap-4 md:grid-cols-2">
                <Card v-for="loan in loans" :key="loan.id">
                    <CardHeader
                        class="flex-row items-start justify-between space-y-0"
                    >
                        <div class="min-w-0">
                            <CardDescription>{{
                                loan.lender || 'Kredi veren belirtilmedi'
                            }}</CardDescription>
                            <CardTitle class="mt-1 truncate text-lg">{{
                                loan.name
                            }}</CardTitle>
                        </div>
                        <Badge
                            :variant="loan.isActive ? 'secondary' : 'outline'"
                            >{{ loan.isActive ? 'Aktif' : 'Arşivlendi' }}</Badge
                        >
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-muted-foreground">
                                    Toplam geri ödeme
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ formatMoney(loan.totalRepaymentMinor) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground">
                                    Kalan plan tutarı
                                </p>
                                <p class="mt-1 font-semibold">
                                    {{ formatMoney(loan.remainingMinor) }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between border-t pt-3 text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Taksit durumu</span
                            >
                            <span class="font-medium"
                                >{{ loan.paidInstallments }} /
                                {{ loan.installmentCount }} ödendi</span
                            >
                        </div>
                        <p
                            v-if="loan.nextInstallment"
                            class="text-sm text-muted-foreground"
                        >
                            Sıradaki:
                            {{ dateLabel(loan.nextInstallment.dueOn) }} ·
                            {{
                                formatMoney(loan.nextInstallment.remainingMinor)
                            }}
                        </p>
                        <div v-if="loan.canEdit" class="flex gap-2">
                            <Button as-child variant="outline" class="flex-1">
                                <Link :href="LoanController.edit(loan.id)"
                                    >Planı düzenle</Link
                                >
                            </Button>
                            <Form
                                v-bind="LoanController.destroy.form(loan.id)"
                                @submit="confirmDelete"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    class="text-destructive"
                                    >Sil</Button
                                >
                            </Form>
                        </div>
                    </CardContent>
                </Card>
            </div>
            <Card v-else class="border-dashed">
                <CardContent
                    class="flex flex-col items-center px-6 py-10 text-center"
                >
                    <Banknote class="mb-3 size-8 text-muted-foreground" />
                    <h3 class="font-semibold">Henüz kredi eklenmedi</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Toplam geri ödeme tutarı ve aylık taksit planını
                        tanımla.
                    </p>
                    <Button as-child class="mt-5"
                        ><Link :href="LoanController.create()"
                            ><Plus />İlk krediyi ekle</Link
                        ></Button
                    >
                </CardContent>
            </Card>
        </section>
    </div>
</template>
