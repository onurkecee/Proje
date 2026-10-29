<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeftRight,
    Banknote,
    LayoutGrid,
    ReceiptText,
    Tags,
    WalletCards,
} from '@lucide/vue';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import BillController from '@/actions/App/Http/Controllers/BillController';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import CreditCardStatementController from '@/actions/App/Http/Controllers/CreditCardStatementController';
import LoanController from '@/actions/App/Http/Controllers/LoanController';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Genel bakış',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Hesaplar',
        href: AccountController.index(),
        icon: WalletCards,
    },
    {
        title: 'Kategoriler',
        href: CategoryController.index(),
        icon: Tags,
    },
    {
        title: 'Hareketler',
        href: TransactionController.index(),
        icon: ArrowLeftRight,
    },
    {
        title: 'Faturalar',
        href: BillController.index(),
        icon: ReceiptText,
    },
    {
        title: 'Kart ekstreleri',
        href: CreditCardStatementController.index(),
        icon: WalletCards,
    },
    {
        title: 'Krediler',
        href: LoanController.index(),
        icon: Banknote,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>
        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
