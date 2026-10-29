<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
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

type AccountOption = {
    id: number;
    name: string;
    currency: string;
};

type CategoryOption = {
    id: number;
    name: string;
    type: 'income' | 'expense';
};

type Transaction = {
    id: number;
    type: 'income' | 'expense' | 'transfer';
    accountId: number;
    destinationAccountId: number | null;
    categoryId: number | null;
    amount: string;
    description: string | null;
    occurredAt: string;
};

const props = defineProps<{
    accounts: AccountOption[];
    categories: CategoryOption[];
    transaction: Transaction | null;
    today: string;
}>();

const type = ref<Transaction['type']>(props.transaction?.type ?? 'expense');
const accountId = ref(
    String(props.transaction?.accountId ?? props.accounts[0]?.id ?? ''),
);
const destinationAccountId = ref(
    String(props.transaction?.destinationAccountId ?? ''),
);
const categoryId = ref(String(props.transaction?.categoryId ?? ''));

function confirmDelete(event: MouseEvent): void {
    if (!window.confirm('Bu hareketi kalıcı olarak silmek istiyor musun?')) {
        event.preventDefault();
    }
}

const visibleCategories = computed(() =>
    props.categories.filter((category) => category.type === type.value),
);

const visibleDestinations = computed(() =>
    props.accounts.filter(
        (account) =>
            String(account.id) !== accountId.value &&
            account.currency ===
                props.accounts.find(
                    (item) => String(item.id) === accountId.value,
                )?.currency,
    ),
);

watch(type, () => {
    if (
        !visibleCategories.value.some(
            (category) => String(category.id) === categoryId.value,
        )
    ) {
        categoryId.value = '';
    }
});

watch(accountId, () => {
    if (
        !visibleDestinations.value.some(
            (account) => String(account.id) === destinationAccountId.value,
        )
    ) {
        destinationAccountId.value = '';
    }
});

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Hareketler', href: '/transactions' },
            { title: 'Hareket formu', href: '/transactions/create' },
        ],
    },
});
</script>

<template>
    <Head :title="transaction ? 'Hareketi düzenle' : 'Yeni hareket'" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="TransactionController.index()"
                    ><ArrowLeft />Hareketlere dön</Link
                >
            </Button>
            <Heading
                :title="transaction ? 'Hareketi düzenle' : 'Yeni hareket ekle'"
                description="Tutarı kuruş hassasiyetinde kaydediyoruz. Transferler gelir veya gider olarak sayılmaz."
            />
        </header>

        <Card
            v-if="accounts.length === 0"
            class="border-amber-500/30 bg-amber-500/[0.04]"
        >
            <CardHeader>
                <CardTitle>Önce bir hesap ekle</CardTitle>
                <CardDescription
                    >Hareketleri kaydedebilmek için en az bir aktif hesabın
                    olmalı.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Button as-child>
                    <Link :href="AccountController.create()"
                        >Hesap oluştur</Link
                    >
                </Button>
            </CardContent>
        </Card>

        <Card
            v-else-if="type !== 'transfer' && visibleCategories.length === 0"
            class="border-amber-500/30 bg-amber-500/[0.04]"
        >
            <CardHeader>
                <CardTitle>Önce bir kategori ekle</CardTitle>
                <CardDescription
                    >Gelir veya gider hareketi için ilgili türde en az bir aktif
                    kategorin olmalı.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Button as-child>
                    <Link :href="CategoryController.create()"
                        >Kategori oluştur</Link
                    >
                </Button>
            </CardContent>
        </Card>

        <Form
            v-else
            v-bind="
                transaction
                    ? TransactionController.update.form(transaction.id)
                    : TransactionController.store.form()
            "
            class="space-y-6 rounded-2xl border bg-card p-5 shadow-sm sm:p-8"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="type">Hareket türü</Label>
                <select
                    id="type"
                    v-model="type"
                    name="type"
                    required
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="expense">Gider</option>
                    <option value="income">Gelir</option>
                    <option value="transfer">Hesaplar arası transfer</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="account_id">{{
                        type === 'income' ? 'Gelir hesabı' : 'Kaynak hesap'
                    }}</Label>
                    <select
                        id="account_id"
                        v-model="accountId"
                        name="account_id"
                        required
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Hesap seçin</option>
                        <option
                            v-for="account in accounts"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name }}
                        </option>
                    </select>
                    <InputError :message="errors.account_id" />
                </div>

                <div v-if="type === 'transfer'" class="grid gap-2">
                    <Label for="destination_account_id">Hedef hesap</Label>
                    <select
                        id="destination_account_id"
                        v-model="destinationAccountId"
                        name="destination_account_id"
                        required
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Hedef hesap seçin</option>
                        <option
                            v-for="account in visibleDestinations"
                            :key="account.id"
                            :value="String(account.id)"
                        >
                            {{ account.name }}
                        </option>
                    </select>
                    <p class="text-xs text-muted-foreground">
                        Yalnızca aynı para birimindeki farklı hesaplar
                        gösterilir.
                    </p>
                    <InputError :message="errors.destination_account_id" />
                </div>

                <div v-else class="grid gap-2">
                    <Label for="category_id">Kategori</Label>
                    <select
                        id="category_id"
                        v-model="categoryId"
                        name="category_id"
                        required
                        class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Kategori seçin</option>
                        <option
                            v-for="category in visibleCategories"
                            :key="category.id"
                            :value="String(category.id)"
                        >
                            {{ category.name }}
                        </option>
                    </select>
                    <InputError :message="errors.category_id" />
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="amount">Tutar (₺)</Label>
                    <Input
                        id="amount"
                        name="amount"
                        type="text"
                        inputmode="decimal"
                        :default-value="transaction?.amount ?? ''"
                        placeholder="Örn. 125,50"
                        autocomplete="off"
                        required
                    />
                    <InputError :message="errors.amount" />
                </div>
                <div class="grid gap-2">
                    <Label for="occurred_at">İşlem tarihi</Label>
                    <Input
                        id="occurred_at"
                        name="occurred_at"
                        type="date"
                        :default-value="transaction?.occurredAt ?? today"
                        required
                    />
                    <InputError :message="errors.occurred_at" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="description"
                    >Açıklama
                    <span class="font-normal text-muted-foreground"
                        >(isteğe bağlı)</span
                    ></Label
                >
                <Input
                    id="description"
                    name="description"
                    :default-value="transaction?.description ?? ''"
                    placeholder="Örn. Haftalık market alışverişi"
                    maxlength="500"
                />
                <InputError :message="errors.description" />
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
            >
                <Button as-child variant="outline">
                    <Link :href="TransactionController.index()">Vazgeç</Link>
                </Button>
                <Button :disabled="processing">
                    {{
                        processing
                            ? 'Kaydediliyor…'
                            : transaction
                              ? 'Değişiklikleri kaydet'
                              : 'Hareketi kaydet'
                    }}
                </Button>
            </div>
        </Form>

        <Card v-if="transaction" class="border-destructive/30">
            <CardHeader>
                <CardTitle class="text-base text-destructive"
                    >Hareketi sil</CardTitle
                >
                <CardDescription>
                    Silinen hareket hesap bakiyelerinden çıkarılır ve geri
                    alınamaz.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="TransactionController.destroy.form(transaction.id)"
                >
                    <Button
                        variant="destructive"
                        type="submit"
                        @click="confirmDelete($event)"
                    >
                        Hareketi sil
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
