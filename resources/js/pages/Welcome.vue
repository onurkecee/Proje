<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUpRight,
    Banknote,
    CalendarClock,
    CreditCard,
    ReceiptText,
    WalletCards,
} from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

const features = [
    {
        icon: WalletCards,
        title: 'Hesapların tek yerde',
        description:
            'Banka, nakit ve kredi kartı bakiyelerini birlikte takip et.',
    },
    {
        icon: ReceiptText,
        title: 'Günlük harcamalar',
        description: 'Market, ulaşım ve diğer giderlerini kategorilere ayır.',
    },
    {
        icon: CalendarClock,
        title: 'Ödeme tarihleri',
        description: 'Faturaları, kart ekstrelerini ve taksitleri önceden gör.',
    },
    {
        icon: Banknote,
        title: 'Aylık finans özeti',
        description: 'Gelir ve giderlerinin ay ay nasıl değiştiğini incele.',
    },
];
</script>

<template>
    <Head title="Finansım — Paranın kontrolü sende">
        <meta
            name="description"
            content="Gelirlerini, giderlerini, faturalarını ve hesaplarını tek bir yerde takip et. Finansım ile aylık bütçene net bir bakış kazan."
        />
    </Head>

    <main class="min-h-screen overflow-hidden bg-[#f7f8f4] text-[#18261f]">
        <div class="mx-auto max-w-7xl px-4 sm:px-8">
            <header
                class="flex h-[84px] items-center justify-between border-b border-[#17291e]/10"
            >
                <Link
                    href="/"
                    class="flex shrink-0 items-center gap-2 sm:gap-3"
                    aria-label="Finansım ana sayfa"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-2xl bg-[#183e2b] text-[#e9f4d8]"
                    >
                        <WalletCards class="size-5" />
                    </span>
                    <span class="text-xl font-semibold tracking-tight"
                        >finansım<span class="text-[#8ea97a]">.</span></span
                    >
                </Link>

                <nav
                    class="flex shrink-0 items-center gap-1 sm:gap-7"
                    aria-label="Ana menü"
                >
                    <a
                        href="#ozellikler"
                        class="hidden text-sm font-medium text-[#617066] transition hover:text-[#183e2b] sm:inline"
                    >
                        Özellikler
                    </a>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-flex h-10 items-center gap-2 rounded-full bg-[#183e2b] px-5 text-sm font-semibold text-white transition hover:bg-[#24583d]"
                    >
                        Uygulamaya git
                        <ArrowRight class="size-4" />
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-full px-1.5 py-2 text-sm font-semibold whitespace-nowrap text-[#46584b] transition hover:text-[#183e2b] sm:px-3"
                        >
                            Giriş yap
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full bg-[#183e2b] px-3 text-sm font-semibold whitespace-nowrap text-white transition hover:bg-[#24583d] sm:gap-2 sm:px-5"
                        >
                            Ücretsiz başla
                            <ArrowRight class="size-4" />
                        </Link>
                    </template>
                </nav>
            </header>

            <section
                class="grid items-center gap-14 pt-14 pb-20 sm:pt-20 sm:pb-24 lg:grid-cols-[1fr_0.95fr] lg:gap-16 lg:pt-24 lg:pb-28"
            >
                <div class="relative z-10">
                    <div
                        class="mb-7 inline-flex items-center gap-2 rounded-full border border-[#dbe5d6] bg-white/70 px-3.5 py-2 text-xs font-semibold tracking-wide text-[#416149]"
                    >
                        <span class="size-2 rounded-full bg-[#8da96c]"></span>
                        KİŞİSEL FİNANSINI SADECE TAKİP ET
                    </div>
                    <h1
                        class="max-w-2xl text-[2.9rem] leading-[1.04] font-semibold tracking-[-0.055em] sm:text-6xl lg:text-[4.35rem]"
                    >
                        Paranın nereye gittiği
                        <span
                            class="font-serif font-medium text-[#719065] italic"
                            >artık</span
                        >
                        sır değil.
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-base leading-7 text-[#647168] sm:text-lg sm:leading-8"
                    >
                        Gelirini, günlük harcamalarını ve yaklaşan ödemelerini
                        tek bir yerde gör. Ay sonunda sürprizlerle değil, net
                        bir tabloyla karşılaş.
                    </p>

                    <div class="mt-9 flex flex-wrap items-center gap-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="dashboard()"
                            class="group inline-flex h-13 items-center gap-3 rounded-full bg-[#183e2b] px-7 text-sm font-semibold text-white shadow-lg shadow-[#183e2b]/15 transition hover:-translate-y-0.5 hover:bg-[#24583d]"
                        >
                            Finans özetine git
                            <ArrowRight
                                class="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </Link>
                        <template v-else>
                            <Link
                                :href="register()"
                                class="group inline-flex h-13 items-center gap-3 rounded-full bg-[#183e2b] px-7 text-sm font-semibold text-white shadow-lg shadow-[#183e2b]/15 transition hover:-translate-y-0.5 hover:bg-[#24583d]"
                            >
                                Hesabını oluştur
                                <ArrowRight
                                    class="size-4 transition-transform group-hover:translate-x-1"
                                />
                            </Link>
                            <Link
                                :href="login()"
                                class="inline-flex h-12 items-center px-3 text-sm font-semibold text-[#46584b] transition hover:text-[#183e2b]"
                            >
                                Zaten hesabım var
                            </Link>
                        </template>
                    </div>

                    <div
                        class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-3 text-sm text-[#68756b]"
                    >
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="size-1.5 rounded-full bg-[#87a779]"
                            ></span>
                            Banka bağlantısı gerekmez
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="size-1.5 rounded-full bg-[#87a779]"
                            ></span>
                            Tutarlar kuruş hassasiyetinde
                        </span>
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-[560px]">
                    <div
                        class="absolute -top-14 -right-10 size-48 rounded-full bg-[#dce9cf] blur-3xl"
                        aria-hidden="true"
                    ></div>
                    <div
                        class="absolute -bottom-12 -left-12 size-48 rounded-full bg-[#eddfbd] opacity-70 blur-3xl"
                        aria-hidden="true"
                    ></div>

                    <div
                        class="relative rounded-[30px] border border-white/80 bg-white/75 p-3 shadow-[0_30px_90px_-40px_rgba(27,54,34,0.36)] backdrop-blur-sm sm:p-4"
                    >
                        <div
                            class="overflow-hidden rounded-[22px] bg-[#f8faf6] ring-1 ring-[#203d2a]/[0.07]"
                        >
                            <div
                                class="flex items-center justify-between border-b border-[#203d2a]/[0.07] px-5 py-4 sm:px-6"
                            >
                                <div class="flex items-center gap-2.5">
                                    <span
                                        class="flex size-8 items-center justify-center rounded-xl bg-[#183e2b] text-[#e9f4d8]"
                                    >
                                        <WalletCards class="size-4" />
                                    </span>
                                    <span
                                        class="text-sm font-semibold tracking-tight"
                                        >Finans özeti</span
                                    >
                                </div>
                                <span
                                    class="rounded-full bg-white px-3 py-1.5 text-xs font-medium text-[#647168] ring-1 ring-[#203d2a]/[0.08]"
                                    >Örnek görünüm</span
                                >
                            </div>

                            <div class="space-y-4 p-4 sm:space-y-5 sm:p-6">
                                <div
                                    class="relative overflow-hidden rounded-2xl bg-[#183e2b] p-5 text-white sm:p-6"
                                >
                                    <div
                                        class="absolute -top-16 -right-8 size-48 rounded-full border border-white/10"
                                        aria-hidden="true"
                                    ></div>
                                    <div
                                        class="absolute -top-10 -right-2 size-36 rounded-full border border-white/10"
                                        aria-hidden="true"
                                    ></div>
                                    <div
                                        class="relative flex items-center justify-between"
                                    >
                                        <span class="text-sm text-white/70"
                                            >Toplam hesap bakiyesi</span
                                        >
                                        <ArrowUpRight
                                            class="size-4 text-[#c5dda9]"
                                        />
                                    </div>
                                    <div
                                        class="relative mt-3 text-3xl font-semibold tracking-tight sm:text-4xl"
                                    >
                                        ₺ 48.250,00
                                    </div>
                                    <div
                                        class="relative mt-5 flex items-center gap-2 text-xs text-white/65"
                                    >
                                        <span
                                            class="rounded-full bg-white/10 px-2.5 py-1 text-[#d3e6ba]"
                                            >3 hesap</span
                                        >
                                        <span>•</span>
                                        <span>Örnek veridir</span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div
                                        class="rounded-2xl border border-[#203d2a]/[0.07] bg-white p-4"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-xs font-medium text-[#718076]"
                                        >
                                            <span
                                                class="flex size-7 items-center justify-center rounded-lg bg-[#edf5e9] text-[#53794a]"
                                            >
                                                <ArrowUpRight class="size-4" />
                                            </span>
                                            Bu ay gelir
                                        </div>
                                        <p
                                            class="mt-3 text-lg font-semibold tracking-tight"
                                        >
                                            ₺ 32.400
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-[#849087]"
                                        >
                                            Örnek veri
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-2xl border border-[#203d2a]/[0.07] bg-white p-4"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-xs font-medium text-[#718076]"
                                        >
                                            <span
                                                class="flex size-7 items-center justify-center rounded-lg bg-[#fbf0e9] text-[#b87952]"
                                            >
                                                <CreditCard class="size-4" />
                                            </span>
                                            Bu ay gider
                                        </div>
                                        <p
                                            class="mt-3 text-lg font-semibold tracking-tight"
                                        >
                                            ₺ 18.750
                                        </p>
                                        <p
                                            class="mt-1 text-[11px] text-[#849087]"
                                        >
                                            Örnek veri
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="rounded-2xl border border-[#203d2a]/[0.07] bg-white p-4 sm:p-5"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold tracking-tight"
                                            >
                                                Aylık hareket
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-[#879188]"
                                            >
                                                Gelir ve gider karşılaştırması
                                            </p>
                                        </div>
                                        <span
                                            class="text-xs font-medium text-[#879188]"
                                            >6 ay</span
                                        >
                                    </div>
                                    <div
                                        class="mt-5 flex h-24 items-end gap-2 sm:gap-3"
                                        aria-label="Örnek aylık gelir gider grafiği"
                                    >
                                        <div
                                            v-for="(height, index) in [
                                                46, 67, 55, 82, 64, 92,
                                            ]"
                                            :key="index"
                                            class="flex h-full flex-1 items-end justify-center gap-1"
                                        >
                                            <span
                                                class="w-2 rounded-t-md bg-[#a9c897] sm:w-2.5"
                                                :style="{
                                                    height: `${height}%`,
                                                }"
                                            ></span>
                                            <span
                                                class="w-2 rounded-t-md bg-[#e7c7a6] sm:w-2.5"
                                                :style="{
                                                    height: `${Math.max(20, height - 23 + (index % 2) * 11)}%`,
                                                }"
                                            ></span>
                                        </div>
                                    </div>
                                    <div
                                        class="mt-3 flex items-center justify-center gap-5 text-[11px] text-[#7d897f]"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <i
                                                class="size-2 rounded-sm bg-[#a9c897]"
                                            ></i>
                                            Gelir
                                        </span>
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <i
                                                class="size-2 rounded-sm bg-[#e7c7a6]"
                                            ></i>
                                            Gider
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center justify-between rounded-2xl border border-[#203d2a]/[0.07] bg-white px-4 py-3.5"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="flex size-9 items-center justify-center rounded-xl bg-[#f6efe2] text-[#947742]"
                                        >
                                            <CalendarClock class="size-4" />
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-semibold text-[#34443a]"
                                            >
                                                Elektrik faturası
                                            </p>
                                            <p
                                                class="mt-0.5 text-[11px] text-[#879188]"
                                            >
                                                Yaklaşan ödeme
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs font-semibold">
                                            ₺ 840
                                        </p>
                                        <p
                                            class="mt-0.5 text-[11px] text-[#879188]"
                                        >
                                            Örnek tutar
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="absolute -bottom-5 -left-4 hidden items-center gap-3 rounded-2xl border border-white bg-white px-4 py-3 shadow-xl shadow-[#183e2b]/10 sm:flex"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-xl bg-[#edf5e9] text-[#53794a]"
                        >
                            <ReceiptText class="size-4" />
                        </span>
                        <span>
                            <span
                                class="block text-xs font-semibold text-[#34443a]"
                                >Ödemelerin gözünün önünde</span
                            >
                            <span
                                class="mt-0.5 block text-[11px] text-[#879188]"
                                >Fatura ve taksit takibi</span
                            >
                        </span>
                    </div>
                </div>
            </section>

            <section
                id="ozellikler"
                class="border-t border-[#17291e]/10 py-14 sm:py-16"
            >
                <div
                    class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.16em] text-[#78916f] uppercase"
                        >
                            DAHA RAHAT BİR FİNANS RUTİNİ
                        </p>
                        <h2
                            class="mt-3 text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            İhtiyacın olan her şey, sade bir yerde.
                        </h2>
                    </div>
                    <p class="max-w-md text-sm leading-6 text-[#68756b]">
                        Karmaşık tablolar olmadan; kayıtlarını, bakiyelerini ve
                        ödeme günlerini anlaşılır biçimde yönet.
                    </p>
                </div>

                <div
                    class="grid overflow-hidden rounded-3xl border border-[#203d2a]/[0.09] bg-white/70 sm:grid-cols-2 lg:grid-cols-4"
                >
                    <article
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="group border-[#203d2a]/[0.09] p-5 transition hover:bg-white sm:p-6 lg:border-r lg:last:border-r-0"
                        :class="{
                            'border-b sm:border-r': index < 2,
                            'sm:border-b-0': index >= 2,
                        }"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-2xl bg-[#edf3e9] text-[#416149] transition group-hover:bg-[#183e2b] group-hover:text-white"
                        >
                            <component :is="feature.icon" class="size-5" />
                        </span>
                        <h3 class="mt-5 text-sm font-semibold">
                            {{ feature.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-[#738076]">
                            {{ feature.description }}
                        </p>
                    </article>
                </div>
            </section>

            <footer
                class="flex flex-col gap-4 border-t border-[#17291e]/10 py-6 text-xs text-[#829087] sm:flex-row sm:items-center sm:justify-between"
            >
                <span
                    >© 2026 Finansım. Kişisel finansın, senin kontrolünde.</span
                >
                <span class="inline-flex items-center gap-2">
                    <CreditCard class="size-3.5" />
                    Manuel kayıt, net görünüm.
                </span>
            </footer>
        </div>
    </main>
</template>
