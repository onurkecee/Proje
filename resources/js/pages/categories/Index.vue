<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Tags } from '@lucide/vue';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
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

type Category = {
    id: number;
    name: string;
    type: 'income' | 'expense';
    isActive: boolean;
};

defineProps<{
    categories: Category[];
}>();

const types = ['expense', 'income'] as const;

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kategoriler', href: '/categories' }],
    },
});
</script>

<template>
    <Head title="Kategoriler" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 p-4 sm:p-6 lg:p-8">
        <header
            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
        >
            <Heading
                title="Kategorilerin"
                description="Gelir ve giderlerini sana uygun kategorilerle düzenle."
            />
            <Button as-child>
                <Link :href="CategoryController.create()"
                    ><Plus />Yeni kategori</Link
                >
            </Button>
        </header>

        <div v-if="categories.length" class="grid gap-6 lg:grid-cols-2">
            <Card v-for="type in types" :key="type">
                <CardHeader>
                    <CardTitle>{{
                        type === 'expense'
                            ? 'Gider kategorileri'
                            : 'Gelir kategorileri'
                    }}</CardTitle>
                    <CardDescription>
                        {{
                            type === 'expense'
                                ? 'Harcama türlerini sınıflandır.'
                                : 'Gelir kaynaklarını düzenle.'
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-for="category in categories.filter(
                            (item) => item.type === type,
                        )"
                        :key="category.id"
                        class="flex items-center justify-between gap-3 border-t py-3 first:border-t-0 first:pt-0 last:pb-0"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    type === 'expense'
                                        ? 'bg-rose-500/10 text-rose-600'
                                        : 'bg-emerald-500/10 text-emerald-600'
                                "
                            >
                                <Tags class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-medium">
                                    {{ category.name }}
                                </p>
                                <Badge variant="outline" class="mt-1">{{
                                    category.isActive ? 'Aktif' : 'Arşivlendi'
                                }}</Badge>
                            </div>
                        </div>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="CategoryController.edit(category.id)"
                                >Düzenle</Link
                            >
                        </Button>
                    </div>
                    <p
                        v-if="
                            !categories.some(
                                (category) => category.type === type,
                            )
                        "
                        class="border-t pt-4 text-sm text-muted-foreground"
                    >
                        Bu türde henüz kategori eklenmedi.
                    </p>
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
                    <Tags class="size-7" />
                </div>
                <h2 class="font-semibold">Kategorilerini oluştur</h2>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    Örneğin market, ulaşım, fatura veya maaş kategorileri
                    ekleyerek hareketlerini kolayca bul.
                </p>
                <Button as-child class="mt-5">
                    <Link :href="CategoryController.create()"
                        ><Plus />İlk kategorini ekle</Link
                    >
                </Button>
            </CardContent>
        </Card>
    </div>
</template>
