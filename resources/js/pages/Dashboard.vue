<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeft,
    ArrowLeftRight,
    ArrowRight,
    ArrowUpRight,
    Banknote,
    CalendarClock,
    CreditCard,
    Plus,
    ReceiptText,
    Tags,
    WalletCards,
} from '@lucide/vue';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import BillController from '@/actions/App/Http/Controllers/BillController';
import CreditCardStatementController from '@/actions/App/Http/Controllers/CreditCardStatementController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
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
import { dashboard } from '@/routes';

type Account = {
    id: number;
    name: string;
    type: 'bank' | 'cash' | 'credit_card';
    currency: string;
    balanceMinor: string;
    isActive: boolean;
};

type TrendMonth = {
    month: string;
    incomeMinor: string;
    expenseMinor: string;
};

type CategoryTotal = {
    id: number;
    name: string;
    amountMinor: string;
};

type RecentTransaction = {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    amountMinor: string;
    description: string | null;
    occurredAt: string;
    accountName: string;
    currency: string;
    categoryName: string | null;
    destinationAccountName: string | null;
    isLinkedPayment: boolean;
};

type UpcomingPayment = {
    id: string;
    name: string;
    type: 'bill' | 'credit_card' | 'loan';
    dueOn: string;
    amountMinor: string;
    currency: string;
    overdue: boolean;
    href: string;
};

const props = defineProps<{
    month: string;
    previousMonth: string;
    nextMonth: string;
    canGoNext: boolean;
    summary: {
        incomeMinor: string;
        expenseMinor: string;
        netMinor: string;
        previousIncomeMinor: string;
        previousExpenseMinor: string;
        netWorthMinor: string;
        accountCount: number;
        transactionCount: number;
    };
    trend: TrendMonth[];
    trendMaxMinor: string;
    categories: CategoryTotal[];
    categoryTotalMinor: string;
    accounts: Account[];
    recentTransactions: RecentTransaction[];
    upcomingPayments: UpcomingPayment[];
}>();

const monthLabel = (month: string): string =>
    new Intl.DateTimeFormat('tr-TR', {
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${month}-01T00:00:00`));

const dateLabel = (date: string): string =>
    new Intl.DateTimeFormat('tr-TR', { dateStyle: 'medium' }).format(
        new Date(`${date}T00:00:00`),
    );

const shortMonth = (month: string): string =>
    new Intl.DateTimeFormat('tr-TR', { month: 'short' }).format(
        new Date(`${month}-01T00:00:00`),
    );

function barWidth(amountMinor: string, maximumMinor: string): string {
    const amount = BigInt(amountMinor);
    const maximum = BigInt(maximumMinor);

    if (amount <= 0n || maximum <= 0n) {
        return '0%';
    }

    const percent = (amount * 100n) / maximum;

    return `${percent < 4n ? 4n : percent}%`;
}

function categoryWidth(amountMinor: string): string {
    return barWidth(amountMinor, props.categoryTotalMinor);
}

function difference(current: string, previous: string): string {
    const amount = BigInt(current) - BigInt(previous);

    return `${amount > 0n ? '+' : amount < 0n ? '−' : ''}${formatMoney(
        (amount < 0n ? -amount : amount).toString(),
    )}`;
}

function paymentIcon(type: UpcomingPayment['type']) {
    if (type === 'bill') return ReceiptText;
    if (type === 'credit_card') return CreditCard;
    return Banknote;
}

const accountTypeLabel: Record<Account['type'], string> = {
    bank: 'Banka hesabı',
    cash: 'Nakit',
    credit_card: 'Kredi kartı',
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Genel bakış', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Genel bakış" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Finans özeti"
                description="Gelirini, harcamalarını, hesaplarını ve yaklaşan ödemelerini tek yerde gör."
            />
            <div
                class="flex items-center justify-between gap-3 rounded-xl border bg-card p-2 sm:justify-start"
            >
                <Link
                    :href="dashboard({ query: { month: previousMonth } })"
                    class="inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    :aria-label="`Önceki ay: ${monthLabel(previousMonth)}`"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <span
                    class="min-w-32 text-center text-sm font-semibold capitalize"
                >
                    {{ monthLabel(month) }}
                </span>
                <Link
                    v-if="canGoNext"
                    :href="dashboard({ query: { month: nextMonth } })"
                    class="inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    :aria-label="`Sonraki ay: ${monthLabel(nextMonth)}`"
                >
                    <ArrowRight class="size-4" />
                </Link>
                <span
                    v-else
                    class="inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground/40"
                    aria-hidden="true"
                >
                    <ArrowRight class="size-4" />
                </span>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Bu ay gelir</CardDescription>
                    <CardTitle class="text-2xl tracking-tight text-emerald-600">
                        {{ formatMoney(summary.incomeMinor) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Önceki aya göre
                    {{
                        difference(
                            summary.incomeMinor,
                            summary.previousIncomeMinor,
                        )
                    }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Bu ay gider</CardDescription>
                    <CardTitle class="text-2xl tracking-tight text-rose-600">
                        {{ formatMoney(summary.expenseMinor) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Önceki aya göre
                    {{
                        difference(
                            summary.expenseMinor,
                            summary.previousExpenseMinor,
                        )
                    }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Aylık net fark</CardDescription>
                    <CardTitle
                        class="text-2xl tracking-tight"
                        :class="
                            BigInt(summary.netMinor) >= 0n
                                ? 'text-emerald-600'
                                : 'text-rose-600'
                        "
                    >
                        {{ formatMoney(summary.netMinor) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    Gelir eksi gider · {{ summary.transactionCount }} kayıt
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardDescription>Hesap net bakiyesi</CardDescription>
                    <CardTitle class="text-2xl tracking-tight">
                        {{ formatMoney(summary.netWorthMinor) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-xs text-muted-foreground">
                    {{ summary.accountCount }} hesap · kart borçları dahil
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
            <Card>
                <CardHeader>
                    <CardTitle>Gelir ve gider eğilimi</CardTitle>
                    <CardDescription
                        >Seçilen ayı da içeren son altı ay · transferler
                        hariç</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div class="space-y-5">
                        <div
                            v-for="item in trend"
                            :key="item.month"
                            class="grid grid-cols-[2.5rem_1fr] items-center gap-3"
                        >
                            <span
                                class="text-xs font-medium text-muted-foreground"
                            >
                                {{ shortMonth(item.month) }}
                            </span>
                            <div class="space-y-1.5">
                                <div
                                    class="flex h-3 items-center rounded-full bg-muted"
                                >
                                    <div
                                        class="h-3 rounded-full bg-emerald-500 transition-all"
                                        :style="{
                                            width: barWidth(
                                                item.incomeMinor,
                                                trendMaxMinor,
                                            ),
                                        }"
                                    />
                                </div>
                                <div
                                    class="flex h-3 items-center rounded-full bg-muted"
                                >
                                    <div
                                        class="h-3 rounded-full bg-rose-500 transition-all"
                                        :style="{
                                            width: barWidth(
                                                item.expenseMinor,
                                                trendMaxMinor,
                                            ),
                                        }"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="mt-5 flex flex-wrap gap-4 border-t pt-4 text-xs text-muted-foreground"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full bg-emerald-500"
                            />Gelir
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="size-2.5 rounded-full bg-rose-500"
                            />Gider
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Gider kategorileri</CardTitle>
                    <CardDescription
                        >{{ monthLabel(month) }} döneminde en çok harcama
                        yapılan alanlar</CardDescription
                    >
                </CardHeader>
                <CardContent>
                    <div v-if="categories.length" class="space-y-4">
                        <div v-for="category in categories" :key="category.id">
                            <div
                                class="mb-1.5 flex items-center justify-between gap-3 text-sm"
                            >
                                <span class="truncate font-medium">{{
                                    category.name
                                }}</span>
                                <span
                                    class="shrink-0 text-muted-foreground tabular-nums"
                                >
                                    {{ formatMoney(category.amountMinor) }}
                                </span>
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-primary transition-all"
                                    :style="{
                                        width: categoryWidth(
                                            category.amountMinor,
                                        ),
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="flex flex-col items-center py-8 text-center"
                    >
                        <Tags class="mb-2 size-7 text-muted-foreground" />
                        <p class="font-medium">Bu ay gider kaydı yok</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Giderlerin kategorilere göre burada özetlenir.
                        </p>
                    </div>
                </CardContent>
            </Card>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1.2fr]">
            <Card>
                <CardHeader class="flex-row items-start justify-between gap-3">
                    <div>
                        <CardTitle>Hesapların</CardTitle>
                        <CardDescription
                            >Banka, nakit ve kart hesaplarının güncel
                            durumu</CardDescription
                        >
                    </div>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="AccountController.index()"
                            >Tümü <ArrowRight
                        /></Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div v-if="accounts.length" class="divide-y">
                        <div
                            v-for="account in accounts.slice(0, 5)"
                            :key="account.id"
                            class="flex items-center justify-between gap-4 py-3 first:pt-0"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                                >
                                    <CreditCard
                                        v-if="account.type === 'credit_card'"
                                        class="size-4"
                                    />
                                    <WalletCards v-else class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{ account.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ accountTypeLabel[account.type] }}
                                        <span v-if="!account.isActive">
                                            · Arşivlendi</span
                                        >
                                    </p>
                                </div>
                            </div>
                            <p
                                class="shrink-0 text-sm font-semibold tabular-nums"
                                :class="
                                    account.type === 'credit_card' &&
                                    BigInt(account.balanceMinor) < 0n
                                        ? 'text-rose-600'
                                        : ''
                                "
                            >
                                {{
                                    account.type === 'credit_card' &&
                                    BigInt(account.balanceMinor) < 0n
                                        ? formatMoney(
                                              (-BigInt(
                                                  account.balanceMinor,
                                              )).toString(),
                                              account.currency,
                                          )
                                        : formatMoney(
                                              account.balanceMinor,
                                              account.currency,
                                          )
                                }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="py-7 text-center">
                        <p class="text-sm text-muted-foreground">
                            Dashboard özetini görmek için hesap ekle.
                        </p>
                        <Button as-child class="mt-3" size="sm">
                            <Link :href="AccountController.create()"
                                ><Plus />Hesap ekle</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex-row items-start justify-between gap-3">
                    <div>
                        <CardTitle>Son hareketler</CardTitle>
                        <CardDescription
                            >Gelir, gider ve transfer
                            kayıtların</CardDescription
                        >
                    </div>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="TransactionController.index()"
                            >Tümü <ArrowRight
                        /></Link>
                    </Button>
                </CardHeader>
                <CardContent>
                    <div v-if="recentTransactions.length" class="divide-y">
                        <div
                            v-for="transaction in recentTransactions"
                            :key="transaction.id"
                            class="flex items-center justify-between gap-3 py-3 first:pt-0"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex size-9 shrink-0 items-center justify-center rounded-xl"
                                    :class="{
                                        'bg-emerald-500/10 text-emerald-600':
                                            transaction.type === 'income',
                                        'bg-rose-500/10 text-rose-600':
                                            transaction.type === 'expense',
                                        'bg-sky-500/10 text-sky-600':
                                            transaction.type === 'transfer',
                                    }"
                                >
                                    <ArrowDownLeft
                                        v-if="transaction.type === 'income'"
                                        class="size-4"
                                    />
                                    <ArrowUpRight
                                        v-else-if="
                                            transaction.type === 'expense'
                                        "
                                        class="size-4"
                                    />
                                    <ArrowLeftRight v-else class="size-4" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">
                                        {{
                                            transaction.description ||
                                            transaction.categoryName ||
                                            (transaction.type === 'income'
                                                ? 'Gelir'
                                                : transaction.type === 'expense'
                                                  ? 'Gider'
                                                  : 'Transfer')
                                        }}
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ transaction.accountName }}
                                        <template
                                            v-if="
                                                transaction.destinationAccountName
                                            "
                                        >
                                            →
                                            {{
                                                transaction.destinationAccountName
                                            }}
                                        </template>
                                        <template
                                            v-else-if="transaction.categoryName"
                                        >
                                            · {{ transaction.categoryName }}
                                        </template>
                                        ·
                                        {{ dateLabel(transaction.occurredAt) }}
                                    </p>
                                </div>
                            </div>
                            <p
                                class="shrink-0 text-sm font-semibold tabular-nums"
                                :class="{
                                    'text-emerald-600':
                                        transaction.type === 'income',
                                    'text-rose-600':
                                        transaction.type === 'expense',
                                }"
                            >
                                {{
                                    transaction.type === 'income'
                                        ? '+'
                                        : transaction.type === 'expense'
                                          ? '−'
                                          : ''
                                }}
                                {{
                                    formatMoney(
                                        transaction.amountMinor,
                                        transaction.currency,
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div v-else class="py-7 text-center">
                        <p class="text-sm text-muted-foreground">
                            Henüz hareket yok.
                        </p>
                        <Button as-child class="mt-3" size="sm">
                            <Link :href="TransactionController.create()"
                                ><Plus />Hareket ekle</Link
                            >
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </section>

        <Card>
            <CardHeader class="flex-row items-start justify-between gap-3">
                <div>
                    <CardTitle>Yaklaşan ödemeler</CardTitle>
                    <CardDescription
                        >Vadesi geçmiş ve önümüzdeki 30 gündeki
                        borçlar</CardDescription
                    >
                </div>
                <div class="flex gap-1">
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="BillController.index()">Faturalar</Link>
                    </Button>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="CreditCardStatementController.index()"
                            >Kartlar</Link
                        >
                    </Button>
                    <Button as-child variant="ghost" size="sm">
                        <Link :href="LoanController.index()">Krediler</Link>
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <div
                    v-if="upcomingPayments.length"
                    class="grid gap-3 md:grid-cols-2 xl:grid-cols-4"
                >
                    <Link
                        v-for="payment in upcomingPayments"
                        :key="payment.id"
                        :href="payment.href"
                        class="rounded-xl border p-4 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                            >
                                <component
                                    :is="paymentIcon(payment.type)"
                                    class="size-4"
                                />
                            </div>
                            <Badge
                                :variant="
                                    payment.overdue ? 'destructive' : 'outline'
                                "
                            >
                                {{
                                    payment.overdue
                                        ? 'Gecikmiş'
                                        : dateLabel(payment.dueOn)
                                }}
                            </Badge>
                        </div>
                        <p class="mt-3 truncate text-sm font-medium">
                            {{ payment.name }}
                        </p>
                        <p class="mt-1 text-lg font-semibold tabular-nums">
                            {{
                                formatMoney(
                                    payment.amountMinor,
                                    payment.currency,
                                )
                            }}
                        </p>
                    </Link>
                </div>
                <div v-else class="flex flex-col items-center py-8 text-center">
                    <CalendarClock class="mb-2 size-7 text-muted-foreground" />
                    <p class="font-medium">Yaklaşan ödeme yok</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Fatura, kredi kartı ekstresi veya kredi taksiti
                        eklediğinde burada görünür.
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card
            v-if="summary.accountCount === 0"
            class="border-dashed bg-muted/20"
        >
            <CardContent
                class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p class="font-medium">Başlamaya hazır mısın?</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Önce bir hesap ve kategori ekle; ardından gelir ve
                        giderlerini kaydet.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child variant="outline" size="sm"
                        ><Link :href="AccountController.create()"
                            >Hesap ekle</Link
                        ></Button
                    >
                    <Button as-child variant="outline" size="sm"
                        ><Link :href="CategoryController.create()"
                            >Kategori ekle</Link
                        ></Button
                    >
                    <Button as-child size="sm"
                        ><Link :href="TransactionController.create()"
                            >Hareket ekle</Link
                        ></Button
                    >
                </div>
            </CardContent>
        </Card>
    </div>
</template>
