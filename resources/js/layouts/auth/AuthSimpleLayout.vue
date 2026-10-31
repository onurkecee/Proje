<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, ShieldCheck, WalletCards } from '@lucide/vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="min-h-svh bg-[#f7f8f4] text-[#18261f]">
        <div class="mx-auto flex min-h-svh max-w-7xl flex-col px-5 sm:px-8">
            <header
                class="flex h-[76px] shrink-0 items-center justify-between border-b border-[#17291e]/10"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2.5"
                    aria-label="Finansım ana sayfa"
                >
                    <span
                        class="flex size-9 items-center justify-center rounded-xl bg-[#183e2b] text-[#e9f4d8]"
                    >
                        <WalletCards class="size-[18px]" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight"
                        >finansım<span class="text-[#8ea97a]">.</span></span
                    >
                </Link>
                <Link
                    :href="home()"
                    class="inline-flex items-center gap-2 text-sm font-medium text-[#68756b] transition hover:text-[#183e2b]"
                >
                    <ArrowLeft class="size-4" />
                    <span class="hidden sm:inline">Ana sayfaya dön</span>
                    <span class="sm:hidden">Ana sayfa</span>
                </Link>
            </header>

            <main
                class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[1fr_0.85fr] lg:gap-20 lg:py-14"
            >
                <section class="hidden lg:block">
                    <div
                        class="relative overflow-hidden rounded-[32px] bg-[#183e2b] p-10 text-white xl:p-14"
                    >
                        <div
                            class="absolute -top-24 -right-24 size-80 rounded-full border border-white/[0.08]"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="absolute -top-10 -right-10 size-52 rounded-full border border-white/[0.08]"
                            aria-hidden="true"
                        ></div>

                        <div class="relative">
                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-xs font-medium text-[#d3e6ba]"
                            >
                                <ShieldCheck class="size-3.5" />
                                Kişisel finansın, senin kontrolünde
                            </span>
                            <h2
                                class="mt-8 max-w-lg text-4xl leading-tight font-semibold tracking-[-0.04em] xl:text-5xl"
                            >
                                Paranı takip etmek artık daha kolay.
                            </h2>
                            <p
                                class="mt-5 max-w-md text-base leading-7 text-white/70"
                            >
                                Hesaplarını, harcamalarını ve ödeme günlerini
                                güvenle tek yerde düzenle.
                            </p>

                            <div
                                class="mt-10 space-y-4 rounded-2xl border border-white/10 bg-white/[0.06] p-5"
                            >
                                <p
                                    class="text-xs font-semibold tracking-[0.14em] text-white/55 uppercase"
                                >
                                    FİNANSIN İÇİN BİR ARADA
                                </p>
                                <div
                                    v-for="item in [
                                        'Gelir ve giderlerini kolayca kaydet',
                                        'Fatura ve taksit tarihlerini kaçırma',
                                        'Ay sonu özetini tek bakışta gör',
                                    ]"
                                    :key="item"
                                    class="flex items-center gap-3 text-sm text-white/90"
                                >
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-full bg-[#d3e6ba]/15 text-[#d3e6ba]"
                                    >
                                        <Check class="size-3.5" />
                                    </span>
                                    {{ item }}
                                </div>
                            </div>

                            <p class="mt-8 text-xs text-white/45">
                                Finansım · Sade, anlaşılır, senin için.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="mx-auto w-full max-w-md">
                    <div class="mb-7 space-y-2">
                        <p
                            class="text-xs font-semibold tracking-[0.15em] text-[#78916f] uppercase"
                        >
                            FİNANSIM HESABI
                        </p>
                        <h1
                            class="text-3xl font-semibold tracking-tight text-[#18261f]"
                        >
                            {{ title }}
                        </h1>
                        <p class="text-sm leading-6 text-[#68756b]">
                            {{ description }}
                        </p>
                    </div>

                    <div
                        class="rounded-3xl border border-[#203d2a]/[0.09] bg-white p-5 shadow-[0_20px_60px_-40px_rgba(27,54,34,0.28)] sm:p-8"
                    >
                        <slot />
                    </div>

                    <p class="mt-6 text-center text-xs text-[#8a958c]">
                        Bilgilerin güvenli şekilde korunur.
                    </p>
                </section>
            </main>
        </div>
    </div>
</template>
