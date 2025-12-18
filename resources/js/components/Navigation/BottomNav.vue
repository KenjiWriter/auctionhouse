<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { Home, Search, PlusCircle, MessageSquare, User, Eye } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { AppPageProps } from '@/types';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const unreadCount = computed(() => page.props.auth?.unread_messages_count || 0);

const navigation = computed(() => [
    { name: 'nav.search', href: route('auctions.index'), icon: Search, badge: 0 },
    { name: 'nav.watched', href: route('auctions.watched'), icon: Eye, badge: 0 },
    { name: 'nav.add', href: route('auctions.create'), icon: PlusCircle, badge: 0 },
    { name: 'nav.chat', href: route('conversations.index'), icon: MessageSquare, badge: unreadCount.value },
    { name: 'nav.account', href: route('profile'), icon: User, badge: 0 },
]);
</script>

<template>
    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background px-4 pb-safe pt-2 lg:hidden">
        <ul class="flex justify-around">
            <li v-for="item in navigation" :key="item.name">
                <Link
                    :href="item.href"
                    class="flex flex-col items-center gap-1 p-2 text-xs text-muted-foreground hover:text-primary relative"
                    :class="{ 'text-primary': route().current(item.href) }"
                >
                    <div class="relative">
                        <component :is="item.icon" class="h-6 w-6" />
                        <span v-if="item.badge > 0" class="absolute -top-1 -right-1 bg-primary text-primary-foreground text-[10px] min-w-[16px] h-[16px] flex items-center justify-center rounded-full font-bold border-2 border-background px-0.5">
                            {{ item.badge }}
                        </span>
                    </div>
                    <span>{{ t(item.name) }}</span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
