<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Home, Search, PlusCircle, MessageSquare, User, Eye } from 'lucide-vue-next';

const { t } = useI18n();

const navigation = [
    { name: 'nav.search', href: route('auctions.index'), icon: Search },
    { name: 'nav.watched', href: route('auctions.watched'), icon: Eye },
    { name: 'nav.add', href: route('auctions.create'), icon: PlusCircle },
    { name: 'nav.chat', href: route('conversations.index'), icon: MessageSquare },
    { name: 'nav.account', href: route('dashboard'), icon: User },
];
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background px-4 pb-safe pt-2 lg:hidden">
        <ul class="flex justify-around">
            <li v-for="item in navigation" :key="item.name">
                <Link
                    :href="item.href"
                    class="flex flex-col items-center gap-1 p-2 text-xs text-muted-foreground hover:text-primary"
                    :class="{ 'text-primary': route().current(item.href) }"
                >
                    <component :is="item.icon" class="h-6 w-6" />
                    <span>{{ t(item.name) }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
