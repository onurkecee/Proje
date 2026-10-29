<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowLeftRight,
    ArrowUpRight,
    Plus,
    ReceiptText,
} from '@lucide/vue';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
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

type Transaction = {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    amountMinor: string;
    description: string | null;
    occurredAt: string;
    account: { id: number; name: string; currency: string };
    category: { id: number; name: string } | null;
    destinationAccount: { id: number; name: string } | null;
    isBillPayment: boolean;
    isCreditCardPayment: boolean;
    isLoanPayment: boolean;
};

type Pagination = {
    data: Transaction[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

defineProps<{
    transactions: Pagination;
}>();

const labels = {
    income: 'Gelir',
    expense: 'Gider',
    transfer: 'Transfer',
} as const;

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Hareketler', href: '/transactions' }],
    },
});
</script>

<template>
    <Head title="Hareketler" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Hareketlerin"
                description="Gelir, gider ve hesaplar arası transfer geçmişin."
            />
            <Button as-child>
                <Link :href="TransactionController.create()"
                    ><Plus />Hareket ekle</Link
                >
            </Button>
        </header>

        <Card>
            <CardHeader>
                <CardTitle>Son hareketler</CardTitle>
                <CardDescription>
                    {{ transactions.total }} kayıt · Tarihe göre en yeniden
                    eskiye
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="transactions.data.length" class="divide-y">
                    <article
                        v-for="transaction in transactions.data"
                        :key="transaction.id"
                        class="flex flex-col gap-3 py-4 first:pt-0 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl"
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
                                    class="size-5"
                                />
                                <ArrowUpRight
                                    v-else-if="transaction.type === 'expense'"
                                    class="size-5"
                                />
                                <ArrowLeftRight v-else class="size-5" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-medium">
                                        {{
                                            transaction.description ||
                                            transaction.category?.name ||
                                            labels[transaction.type]
                                        }}
                                    </p>
                                    <Badge variant="outline">{{
                                        labels[transaction.type]
                                    }}</Badge>
                                    <Badge
                                        v-if="transaction.isBillPayment"
                                        variant="secondary"
                                    >
                                        Fatura ödemesi
                                    </Badge>
                                    <Badge
                                        v-else-if="
                                            transaction.isCreditCardPayment
                                        "
                                        variant="secondary"
                                    >
                                        Kart ekstresi transferi
                                    </Badge>
                                    <Badge
                                        v-else-if="transaction.isLoanPayment"
                                        variant="secondary"
                                    >
                                        Kredi taksiti
                                    </Badge>
                                </div>
                                <p
                                    class="mt-1 truncate text-sm text-muted-foreground"
                                >
                                    {{ transaction.account.name }}
                                    <template
                                        v-if="transaction.destinationAccount"
                                    >
                                        →
                                        {{
                                            transaction.destinationAccount.name
                                        }}
                                    </template>
                                    <template v-else-if="transaction.category">
                                        · {{ transaction.category.name }}
                                    </template>
                                    ·
                                    {{
                                        new Intl.DateTimeFormat('tr-TR', {
                                            dateStyle: 'medium',
                                        }).format(
                                            new Date(
                                                `${transaction.occurredAt}T00:00:00`,
                                            ),
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 sm:justify-end"
                        >
                            <p
                                class="shrink-0 text-base font-semibold tabular-nums"
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
                                        transaction.account.currency,
                                    )
                                }}
                            </p>
                            <Button
                                v-if="
                                    !transaction.isBillPayment &&
                                    !transaction.isCreditCardPayment &&
                                    !transaction.isLoanPayment
                                "
                                as-child
                                variant="ghost"
                                size="sm"
                            >
                                <Link
                                    :href="
                                        TransactionController.edit(
                                            transaction.id,
                                        )
                                    "
                                    >Düzenle</Link
                                >
                            </Button>
                        </div>
                    </article>

                    <div
                        class="flex items-center justify-between border-t pt-4 text-sm"
                    >
                        <Link
                            v-if="transactions.prev_page_url"
                            :href="transactions.prev_page_url"
                            class="font-medium text-primary hover:underline"
                        >
                            Önceki
                        </Link>
                        <span v-else class="text-muted-foreground">Önceki</span>
                        <span class="text-muted-foreground">
                            Sayfa {{ transactions.current_page }} /
                            {{ transactions.last_page }}
                        </span>
                        <Link
                            v-if="transactions.next_page_url"
                            :href="transactions.next_page_url"
                            class="font-medium text-primary hover:underline"
                        >
                            Sonraki
                        </Link>
                        <span v-else class="text-muted-foreground"
                            >Sonraki</span
                        >
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center px-6 py-14 text-center"
                >
                    <div
                        class="mb-4 flex size-14 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <ReceiptText class="size-7" />
                    </div>
                    <h2 class="font-semibold">Henüz hareket eklenmedi</h2>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        İlk gelir veya giderini kaydettiğinde hareketlerin
                        burada listelenir.
                    </p>
                    <Button as-child class="mt-5">
                        <Link :href="TransactionController.create()"
                            ><Plus />İlk hareketini ekle</Link
                        >
                    </Button>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
