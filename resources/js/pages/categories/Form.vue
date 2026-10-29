<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
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

type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense';
    isActive: boolean;
    canDelete: boolean;
};

function confirmDelete(event: MouseEvent, name: string): void {
    if (
        !window.confirm(
            `“${name}” kategorisini kalıcı olarak silmek istiyor musun?`,
        )
    ) {
        event.preventDefault();
    }
}

defineProps<{
    category: Category | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Kategoriler', href: '/categories' },
            { title: 'Kategori formu', href: '/categories/create' },
        ],
    },
});
</script>

<template>
    <Head :title="category ? 'Kategoriyi düzenle' : 'Yeni kategori'" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header class="space-y-4">
            <Button as-child variant="ghost" size="sm" class="-ml-3 w-fit">
                <Link :href="CategoryController.index()"
                    ><ArrowLeft />Kategorilere dön</Link
                >
            </Button>
            <Heading
                :title="
                    category ? 'Kategoriyi düzenle' : 'Yeni kategori oluştur'
                "
                description="Gelir veya gider türünü seç. Kullanılmış kategoriler geçmiş hareketlerinde korunur."
            />
        </header>

        <Form
            v-bind="
                category
                    ? CategoryController.update.form(category.id)
                    : CategoryController.store.form()
            "
            class="space-y-6 rounded-2xl border bg-card p-5 shadow-sm sm:p-8"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Kategori adı</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="category?.name"
                    placeholder="Örn. Market"
                    required
                    maxlength="80"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="type">Kategori türü</Label>
                <select
                    id="type"
                    name="type"
                    :value="category?.type ?? 'expense'"
                    required
                    class="h-10 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="expense">Gider</option>
                    <option value="income">Gelir</option>
                </select>
                <InputError :message="errors.type" />
            </div>

            <div
                v-if="category"
                class="flex items-start gap-3 rounded-lg border p-4"
            >
                <input
                    id="is_active"
                    type="checkbox"
                    name="is_active"
                    value="1"
                    :checked="category.isActive"
                    class="mt-1 size-4 rounded border-input accent-primary"
                />
                <div>
                    <Label for="is_active">Kategori aktif</Label>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pasif kategoriler yeni hareket girişinde listelenmez.
                    </p>
                </div>
                <InputError :message="errors.is_active" />
            </div>

            <div
                class="flex flex-col-reverse gap-3 border-t pt-5 sm:flex-row sm:justify-end"
            >
                <Button as-child variant="outline">
                    <Link :href="CategoryController.index()">Vazgeç</Link>
                </Button>
                <Button :disabled="processing">
                    {{
                        processing
                            ? 'Kaydediliyor…'
                            : category
                              ? 'Değişiklikleri kaydet'
                              : 'Kategoriyi oluştur'
                    }}
                </Button>
            </div>
        </Form>

        <Card v-if="category?.canDelete" class="border-destructive/30">
            <CardHeader>
                <CardTitle class="text-base text-destructive"
                    >Kategoriyi sil</CardTitle
                >
                <CardDescription
                    >Bu kategori hiçbir harekette kullanılmıyor. Silinen
                    kategori geri alınamaz.</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form v-bind="CategoryController.destroy.form(category.id)">
                    <Button
                        variant="destructive"
                        type="submit"
                        @click="confirmDelete($event, category.name)"
                    >
                        Kategoriyi sil
                    </Button>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
