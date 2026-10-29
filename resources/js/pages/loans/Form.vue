<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
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

type LoanFormData = {
    id: number;
    name: string;
    lender: string | null;
    totalRepayment: string;
    installmentAmount: string;
    installmentCount: number;
    firstDueOn: string;
    isActive: boolean;
};

const props = defineProps<{ loan: LoanFormData | null; today: string }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Krediler', href: '/loans' },
            { title: 'Kredi formu', href: '/loans/create' },
        ],
    },
});
</script>

<template>
    <Head :title="loan ? 'Kredi planını düzenle' : 'Yeni kredi'" />
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="LoanController.index()"
                    ><ArrowLeft />Kredilere dön</Link
                >
            </Button>
            <Heading
                :title="
                    loan ? 'Kredi planını düzenle' : 'Kredi taksit planı ekle'
                "
                description="Faiz hesaplaması yapılmaz; sözleşmedeki toplam geri ödeme ve taksitleri manuel takip ederiz."
            />
        </header>

        <Card>
            <CardHeader>
                <CardTitle>Sözleşme bilgileri</CardTitle>
                <CardDescription
                    >Son taksit, toplam geri ödeme tutarını kuruşu kuruşuna
                    tamamlayacak şekilde ayarlanır.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="
                        loan
                            ? LoanController.update.form(loan.id)
                            : LoanController.store.form()
                    "
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Kredi adı</Label>
                            <Input
                                id="name"
                                name="name"
                                :default-value="loan?.name"
                                placeholder="Örn. Taşıt kredisi"
                                maxlength="120"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="lender"
                                >Banka / kredi veren (isteğe bağlı)</Label
                            >
                            <Input
                                id="lender"
                                name="lender"
                                :default-value="loan?.lender ?? ''"
                                maxlength="120"
                            />
                            <InputError :message="errors.lender" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="total_repayment"
                                >Toplam geri ödeme (TRY)</Label
                            >
                            <Input
                                id="total_repayment"
                                name="total_repayment"
                                :default-value="loan?.totalRepayment"
                                inputmode="decimal"
                                placeholder="0,00"
                                required
                            />
                            <InputError :message="errors.total_repayment" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="installment_amount"
                                >Aylık taksit (TRY)</Label
                            >
                            <Input
                                id="installment_amount"
                                name="installment_amount"
                                :default-value="loan?.installmentAmount"
                                inputmode="decimal"
                                placeholder="0,00"
                                required
                            />
                            <InputError :message="errors.installment_amount" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="installment_count">Taksit sayısı</Label>
                            <Input
                                id="installment_count"
                                name="installment_count"
                                type="number"
                                min="1"
                                max="600"
                                :default-value="loan?.installmentCount ?? 12"
                                required
                            />
                            <InputError :message="errors.installment_count" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="first_due_on">İlk taksit tarihi</Label>
                            <Input
                                id="first_due_on"
                                name="first_due_on"
                                type="date"
                                :default-value="loan?.firstDueOn ?? today"
                                required
                            />
                            <InputError :message="errors.first_due_on" />
                        </div>
                    </div>
                    <div v-if="loan" class="rounded-lg border p-4">
                        <label class="flex items-center gap-3 text-sm">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                :checked="loan.isActive"
                                class="size-4 rounded border-input accent-primary"
                            />
                            Kredi planı aktif
                        </label>
                        <InputError :message="errors.is_active" />
                    </div>
                    <div
                        class="rounded-xl bg-muted/50 p-4 text-sm text-muted-foreground"
                    >
                        Taksit tutarları toplamdan fazla olursa kayıt
                        reddedilir. Son taksit, kalan kuruş farkına göre
                        ayarlanır.
                    </div>
                    <div
                        class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
                    >
                        <Button as-child variant="outline"
                            ><Link :href="LoanController.index()"
                                >Vazgeç</Link
                            ></Button
                        >
                        <Button type="submit" :disabled="processing">
                            {{
                                processing
                                    ? 'Kaydediliyor…'
                                    : loan
                                      ? 'Planı güncelle'
                                      : 'Krediyi ve taksitleri oluştur'
                            }}
                        </Button>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
