<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import BillController from '@/actions/App/Http/Controllers/BillController';
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

type BillType =
    | 'electricity'
    | 'water'
    | 'natural_gas'
    | 'phone'
    | 'internet'
    | 'other';

type BillFormData = {
    id: number;
    name: string;
    type: BillType;
    expectedAmount: string;
    firstDueOn: string;
    repeatMonthly: boolean;
    endsOn: string | null;
    isActive: boolean;
};

const props = defineProps<{
    bill: BillFormData | null;
    today: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Faturalar', href: '/bills' },
            { title: 'Fatura formu', href: '/bills/create' },
        ],
    },
});
</script>

<template>
    <Head :title="bill ? 'Faturayı düzenle' : 'Yeni fatura'" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="BillController.index()"
                    ><ArrowLeft />Faturalara dön</Link
                >
            </Button>
            <Heading
                :title="bill ? 'Faturayı düzenle' : 'Yeni fatura ekle'"
                description="Tutarlar tam sayı kuruş olarak saklanır; tekrarlayan faturaların vadeleri otomatik oluşturulur."
            />
        </header>

        <Card>
            <CardHeader>
                <CardTitle>Fatura bilgileri</CardTitle>
                <CardDescription>
                    Fatura tutarını TRY cinsinden gir. Ödeme sırasında gerçek
                    tutarı ayrıca belirleyebilirsin.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        bill
                            ? BillController.update.form(bill.id)
                            : BillController.store.form()
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Fatura adı</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="bill?.name"
                                placeholder="Örn. Ev interneti"
                                maxlength="120"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">Fatura türü</Label>
                            <select
                                id="type"
                                name="type"
                                :value="bill?.type ?? 'electricity'"
                                required
                                class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="electricity">Elektrik</option>
                                <option value="water">Su</option>
                                <option value="natural_gas">Doğal gaz</option>
                                <option value="phone">Telefon</option>
                                <option value="internet">İnternet</option>
                                <option value="other">Diğer</option>
                            </select>
                            <InputError :message="errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="expected_amount"
                                >Beklenen tutar (TRY)</Label
                            >
                            <Input
                                id="expected_amount"
                                name="expected_amount"
                                :default-value="bill?.expectedAmount"
                                inputmode="decimal"
                                placeholder="0,00"
                                required
                            />
                            <InputError :message="errors.expected_amount" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="first_due_on">İlk vade tarihi</Label>
                            <Input
                                id="first_due_on"
                                name="first_due_on"
                                type="date"
                                :default-value="bill?.firstDueOn ?? today"
                                required
                            />
                            <InputError :message="errors.first_due_on" />
                        </div>

                        <div v-if="bill" class="grid gap-2">
                            <Label class="sm:pt-7">Durum</Label>
                            <label
                                class="flex min-h-10 items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    :checked="bill.isActive"
                                    class="size-4 rounded border-input accent-primary"
                                />
                                Fatura takibi aktif
                            </label>
                            <InputError :message="errors.is_active" />
                        </div>
                    </div>

                    <div class="rounded-xl border p-4">
                        <label class="flex items-start gap-3">
                            <input
                                type="checkbox"
                                name="repeat_monthly"
                                value="1"
                                :checked="bill?.repeatMonthly ?? false"
                                class="mt-1 size-4 rounded border-input accent-primary"
                            />
                            <span>
                                <span class="block text-sm font-medium"
                                    >Her ay tekrarla</span
                                >
                                <span
                                    class="mt-1 block text-sm text-muted-foreground"
                                >
                                    İlk vade gününe göre her ay yeni bir ödeme
                                    dönemi oluştur.
                                </span>
                            </span>
                        </label>
                        <div class="mt-4 grid gap-2 sm:max-w-xs">
                            <Label for="ends_on"
                                >Tekrarlama bitiş tarihi (isteğe bağlı)</Label
                            >
                            <Input
                                id="ends_on"
                                name="ends_on"
                                type="date"
                                :default-value="bill?.endsOn ?? ''"
                            />
                            <InputError :message="errors.ends_on" />
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
                                processing
                                    ? 'Kaydediliyor…'
                                    : bill
                                      ? 'Değişiklikleri kaydet'
                                      : 'Faturayı kaydet'
                            }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
