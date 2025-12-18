<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';
import { Home, PlusCircle, User, Bell, Search, Gavel, Package, MessageSquare, Eye, ChevronDown } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { AppPageProps } from '@/types';

const { t, locale } = useI18n();
const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth?.user);
const unreadCount = computed(() => page.props.auth?.unread_messages_count || 0);

const navigation = computed(() => [
    { name: 'nav.home', href: route('home'), icon: Home, badge: 0 },
    { name: 'nav.search', href: route('auctions.index'), icon: Search, badge: 0 },
    { name: 'nav.watched', href: route('auctions.watched'), icon: Eye, badge: 0 },
    { name: 'nav.add_auction', href: route('auctions.create'), icon: PlusCircle, badge: 0 },
    { name: 'nav.chat', href: route('conversations.index'), icon: MessageSquare, badge: unreadCount.value },
]);

// Account submenu items
const accountMenu = computed(() => [
    { name: 'nav.profile', href: route('profile.mine'), icon: User },
    { name: 'nav.my_auctions', href: route('auctions.mine'), icon: Package },
    { name: 'nav.my_wins', href: route('auctions.wins'), icon: Gavel },
    { name: 'nav.bidding', href: route('profile.bidding'), icon: Gavel },
]);

const showAccountMenu = ref(false);

const switchLanguage = (lang: string) => {
    locale.value = lang;
    localStorage.setItem('locale', lang);
};
</script>

<template>
    <aside class="hidden w-64 flex-col border-r bg-sidebar text-sidebar-foreground lg:flex">
        <div class="flex h-16 items-center border-b px-6">
            <Link :href="route('home')" class="flex items-center gap-2 font-bold text-lg">
                <Gavel class="h-6 w-6 text-primary" />
                <span>AuctionHouse</span>
            </Link>
        </div>

        <nav class="flex-1 overflow-y-auto px-4 py-4">
            <ul class="space-y-1">
                <li v-for="item in navigation" :key="item.name">
                    <Link
                        :href="item.href"
                        class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground': route().current(item.href) }"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        <span>{{ t(item.name) }}</span>
                        <span v-if="item.badge > 0" class="ml-auto bg-primary text-primary-foreground text-[10px] px-1.5 rounded-full py-0.5 font-bold">
                            {{ item.badge }}
                        </span>
                    </Link>
                </li>

                <!-- Account Dropdown -->
                <li v-if="user" class="relative">
                    <button 
                        @click="showAccountMenu = !showAccountMenu"
                        class="w-full group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground': showAccountMenu || route().current('profile*') || route().current('auctions.mine') || route().current('auctions.wins') }"
                    >
                        <User class="h-4 w-4" />
                        <span>{{ t('nav.account') }}</span>
                        <ChevronDown class="h-3 w-3 ml-auto transition-transform" :class="{ 'rotate-180': showAccountMenu }" />
                    </button>
                    
                    <div v-show="showAccountMenu" class="mt-1 ml-4 space-y-1 border-l pl-2">
                        <Link
                            v-for="item in accountMenu"
                            :key="item.name"
                            :href="item.href"
                            class="group flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground"
                            :class="{ 'text-primary font-semibold': route().current(item.href) }"
                        >
                            <span>{{ t(item.name) }}</span>
                        </Link>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="border-t p-4">
             <!-- Language Switcher -->
            <div class="mb-4 flex gap-2">
                <button 
                    @click="switchLanguage('pl')" 
                    class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600"
                    :class="{ 'bg-primary text-primary-foreground': locale === 'pl' }"
                >
                    PL
                </button>
                <button 
                    @click="switchLanguage('en')" 
                    class="px-2 py-1 text-xs rounded border border-gray-300 dark:border-gray-600"
                    :class="{ 'bg-primary text-primary-foreground': locale === 'en' }"
                >
                    EN
                </button>
            </div>

            <div v-if="user" class="flex items-center gap-3">
                <div class="h-8 w-8 rounded-full overflow-hidden shrink-0">
                    <img 
                        v-if="user.avatar_url && (user.avatar_url.startsWith('http') || user.avatar_url.startsWith('/'))" 
                        :src="user.avatar_url" 
                        class="h-full w-full object-cover"
                    />
                    <div 
                        v-else-if="user.avatar_url && user.avatar_url.startsWith('bg-')" 
                        class="h-full w-full" 
                        :class="user.avatar_url"
                    ></div>
                    <div 
                        v-else 
                        class="h-full w-full bg-primary/20 flex items-center justify-center text-primary font-bold"
                    >
                        {{ user.name.charAt(0) }}
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-medium">{{ user.name }}</span>
                    <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                </div>
            </div>
             <div v-else class="flex flex-col gap-2">
                <Link :href="route('login')" class="w-full text-center rounded-md bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90">
                    {{ t('auth.login') }}
                </Link>
            </div>
        </div>
    </aside>
</template>
