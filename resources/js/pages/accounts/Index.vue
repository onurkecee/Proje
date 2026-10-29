<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, WalletCards } from '@lucide/vue';
import { computed } from 'vue';
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

type Account = {
    id: number;
    name: string;
    type: 'bank' | 'cash' | 'credit_card';
    currency: string;
    openingBalanceMinor: string;
    balanceMinor: string;
    isActive: boolean;
};

const props = defineProps<{
    accounts: Account[];
}>();

const totalBalance = computed(() =>
    props.accounts
        .reduce((sum, account) => sum + BigInt(account.balanceMinor), 0n)
        .toString(),
);

const accountTypeLabel = (type: Account['type']): string =>
    ({
        bank: 'Banka hesabı',
        cash: 'Nakit',
        credit_card: 'Kredi kartı',
    })[type];

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Hesaplar', href: '/accounts' }],
    },
});
</script>

<template>
    <Head title="Hesaplar" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Hesapların"
                description="Banka, nakit ve kredi kartı bakiyelerini net varlık görünümüyle takip et."
            />
            <Button as-child>
                <Link :href="AccountController.create()">
                    <Plus />
                    Yeni hesap
                </Link>
            </Button>
        </header>

        <section class="grid gap-4 sm:grid-cols-2">
            <Card class="overflow-hidden">
                <CardHeader class="pb-2">
                    <CardDescription>Net varlık</CardDescription>
                    <CardTitle class="text-3xl tracking-tight">
                        {{ formatMoney(totalBalance) }}
                    </CardTitle>
                </CardHeader>
                <CardContent class="text-sm text-muted-foreground">
                    Varlık hesapları eksi kredi kartı borçları
                </CardContent>
            </Card>
            <Card class="flex items-center gap-4 bg-primary/[0.04] p-6">
                <div
                    class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                >
                    <WalletCards class="size-6" />
                </div>
                <div>
                    <p class="font-medium">Bakiyeler otomatik güncellenir</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Kredi kartı borcu eksi bakiye olarak gösterilir. Ekstre
                        ödemeleri kart borcunu azaltan transferlerdir.
                    </p>
                </div>
            </Card>
        </section>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">Hesap listesi</h2>
                    <p class="text-sm text-muted-foreground">
                        Her hesabın mevcut bakiyesi ve durumu.
                    </p>
                </div>
            </div>

            <div
                v-if="accounts.length"
                class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <Card
                    v-for="account in accounts"
                    :key="account.id"
                    class="transition-shadow hover:shadow-md"
                >
                    <CardHeader
                        class="flex-row items-start justify-between space-y-0"
                    >
                        <div class="min-w-0">
                            <CardDescription>{{
                                accountTypeLabel(account.type)
                            }}</CardDescription>
                            <CardTitle class="mt-1 truncate text-lg">{{
                                account.name
                            }}</CardTitle>
                        </div>
                        <Badge
                            :variant="
                                account.isActive ? 'secondary' : 'outline'
                            "
                        >
                            {{ account.isActive ? 'Aktif' : 'Arşivlendi' }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <p
                                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                {{
                                    account.type === 'credit_card'
                                        ? 'Güncel kart borcu'
                                        : 'Güncel bakiye'
                                }}
                            </p>
                            <p
                                class="mt-1 text-2xl font-semibold tracking-tight"
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
                        <div
                            class="flex items-center justify-between border-t pt-3 text-sm"
                        >
                            <span class="text-muted-foreground"
                                >Açılış bakiyesi</span
                            >
                            <span class="font-medium">{{
                                formatMoney(
                                    account.openingBalanceMinor,
                                    account.currency,
                                )
                            }}</span>
                        </div>
                        <Button as-child variant="outline" class="w-full">
                            <Link :href="AccountController.edit(account.id)"
                                >Hesabı düzenle</Link
                            >
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <Card v-else class="border-dashed">
                <CardContent
                    class="flex flex-col items-center px-6 py-14 text-center"
                >
                    <div
                        class="mb-4 flex size-14 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <WalletCards class="size-7" />
                    </div>
                    <h3 class="font-semibold">Henüz hesabın yok</h3>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        Önce banka hesabını veya nakit cüzdanını ekle. Sonra
                        gelir ve giderlerini kaydedebilirsin.
                    </p>
                    <Button as-child class="mt-5">
                        <Link :href="AccountController.create()"
                            ><Plus />İlk hesabını ekle</Link
                        >
                    </Button>
                </CardContent>
            </Card>
        </section>
    </div>
</template>
