<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowDownUp, CreditCard, Plus, TriangleAlert } from '@lucide/vue';
import CreditCardPaymentController from '@/actions/App/Http/Controllers/CreditCardPaymentController';
import CreditCardStatementController from '@/actions/App/Http/Controllers/CreditCardStatementController';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
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

type AccountOption = { id: number; name: string; currency: string };
type Statement = {
    id: number;
    accountId: number;
    cardName: string;
    currency: string;
    periodStart: string;
    periodEnd: string;
    dueOn: string;
    amountMinor: string;
    paidAmountMinor: string;
    remainingMinor: string;
    paid: boolean;
    overdue: boolean;
    canEdit: boolean;
};

const props = defineProps<{
    statements: Statement[];
    cards: AccountOption[];
    summary: {
        openCount: number;
        overdueCount: number;
        dueSoonMinor: string;
    };
}>();

function dateLabel(value: string): string {
    return new Intl.DateTimeFormat('tr-TR', { dateStyle: 'medium' }).format(
        new Date(`${value}T00:00:00`),
    );
}

function confirmDelete(event: Event): void {
    if (!window.confirm('Bu kart ekstresini silmek istiyor musun?')) {
        event.preventDefault();
    }
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kart ekstreleri', href: '/credit-card-statements' },
        ],
    },
});
</script>

<template>
    <Head title="Kart ekstreleri" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Kredi kartı ekstreleri"
                description="Ekstre borcunu, son ödeme tarihini ve parça parça yaptığın transferleri takip et."
            />
            <Button as-child>
                <Link :href="CreditCardStatementController.create()"
                    ><Plus />Ekstre ekle</Link
                >
            </Button>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Ödenmemiş ekstre</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <CreditCard class="size-6 text-primary" />
                        {{ summary.openCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground"
                    >Kısmi ödemeler dahil</CardContent
                >
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Gecikmiş ekstre</CardDescription>
                    <CardTitle class="flex items-center gap-2 text-3xl">
                        <TriangleAlert class="size-6 text-rose-600" />
                        {{ summary.overdueCount }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground"
                    >Ödeme bekleyen son ödeme tarihleri geçmiş</CardContent
                >
            </Card>
            <Card class="flex items-center gap-4 bg-primary/[0.04] p-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <ArrowDownUp class="size-6" />
                </div>
                <div>
                    <p class="font-medium">Çift gider kaydı yok</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Ekstre ödemesi banka hesabından karta transferdir; kart
                        alışverişi gider olarak kalır.
                    </p>
                </div>
            </Card>
        </section>

        <Card>
            <CardHeader>
                <CardTitle>Ekstre listesi</CardTitle>
                <CardDescription>Ödeme ve kalan borç durumu.</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="statements.length" class="divide-y">
                    <article
                        v-for="statement in statements"
                        :key="statement.id"
                        class="flex flex-col gap-4 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate font-medium">
                                    {{ statement.cardName }}
                                </p>
                                <Badge
                                    :variant="
                                        statement.paid
                                            ? 'secondary'
                                            : statement.overdue
                                              ? 'destructive'
                                              : 'outline'
                                    "
                                >
                                    {{
                                        statement.paid
                                            ? 'Ödendi'
                                            : statement.overdue
                                              ? 'Gecikmiş'
                                              : 'Bekliyor'
                                    }}
                                </Badge>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Dönem {{ dateLabel(statement.periodStart) }} –
                                {{ dateLabel(statement.periodEnd) }} · Son ödeme
                                {{ dateLabel(statement.dueOn) }}
                            </p>
                            <p
                                v-if="BigInt(statement.paidAmountMinor) > 0n"
                                class="mt-1 text-xs text-muted-foreground"
                            >
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
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 sm:justify-end"
                        >
                            <div class="text-right">
                                <p class="font-semibold tabular-nums">
                                    {{
                                        formatMoney(
                                            statement.remainingMinor,
                                            statement.currency,
                                        )
                                    }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Kalan ekstre borcu
                                </p>
                            </div>
                            <Button v-if="!statement.paid" as-child size="sm">
                                <Link
                                    :href="
                                        CreditCardPaymentController.create(
                                            statement.id,
                                        )
                                    "
                                >
                                    Ödeme ekle
                                </Link>
                            </Button>
                            <Button
                                v-if="statement.canEdit"
                                as-child
                                variant="outline"
                                size="sm"
                            >
                                <Link
                                    :href="
                                        CreditCardStatementController.edit(
                                            statement.id,
                                        )
                                    "
                                    >Düzenle</Link
                                >
                            </Button>
                            <Form
                                v-if="statement.canEdit"
                                v-bind="
                                    CreditCardStatementController.destroy.form(
                                        statement.id,
                                    )
                                "
                                @submit="confirmDelete"
                            >
                                <Button
                                    type="submit"
                                    variant="ghost"
                                    size="sm"
                                    class="text-destructive"
                                >
                                    Sil
                                </Button>
                            </Form>
                        </div>
                    </article>
                </div>
                <div
                    v-else
                    class="flex flex-col items-center px-6 py-12 text-center"
                >
                    <CreditCard class="mb-3 size-8 text-muted-foreground" />
                    <h3 class="font-semibold">Henüz ekstre eklenmedi</h3>
                    <p class="mt-1 max-w-md text-sm text-muted-foreground">
                        Kart ekstre tutarı ve vadesini manuel kaydet. Ekstreyi
                        ödeme ekranından banka hesabından karta aktarabilirsin.
                    </p>
                    <Button v-if="cards.length" as-child class="mt-5">
                        <Link :href="CreditCardStatementController.create()"
                            ><Plus />İlk ekstreyi ekle</Link
                        >
                    </Button>
                    <Button v-else as-child variant="outline" class="mt-5">
                        <Link :href="AccountController.create()"
                            >Önce kredi kartı hesabı ekle</Link
                        >
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
