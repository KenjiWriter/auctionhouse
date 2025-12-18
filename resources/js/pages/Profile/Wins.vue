<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { User, Gavel, Package, Search } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { AppPageProps } from '@/types';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth?.user);

const props = defineProps<{
    wins: {
        data: Array<{
            id: number;
            title: string;
            current_price: number;
            ends_at: string;
            status: string;
            images: Array<{ path: string }>;
            category: { name: string };
            user: { id: number; name: string };
        }>;
        links: Array<any>;
    };
}>();

const tabs = [
    { name: 'profile.tabs.auctions', href: route('profile.mine'), current: false, icon: Package },
    { name: 'profile.tabs.wins', href: route('profile.wins'), current: true, icon: Gavel },
    { name: 'profile.tabs.bidding', href: route('profile.bidding'), current: false, icon: Gavel },
];
</script>

<template>
    <Head :title="t('nav.my_wins')" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <Gavel class="h-6 w-6 text-primary" />
                {{ t('nav.my_wins') }}
            </h1>

            <!-- Tabs -->
            <div class="border-b border-border mb-6">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                    <Link 
                        v-for="tab in tabs" 
                        :key="tab.name"
                        :href="tab.href"
                        class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm gap-2"
                        :class="[
                            tab.current
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground hover:border-gray-300'
                        ]"
                    >
                        <component :is="tab.icon" class="h-4 w-4" />
                        <span>{{ t(tab.name) }}</span>
                    </Link>
                </nav>
            </div>

            <!-- Wins List -->
            <div v-if="wins.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="auction in wins.data" :key="auction.id" class="group bg-card border rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-all border-green-500/20">
                     <Link :href="route('auctions.show', auction.id)" class="block">
                        <div class="h-48 bg-muted flex items-center justify-center text-muted-foreground overflow-hidden relative">
                            <img 
                                v-if="auction.images?.[0]" 
                                :src="`/storage/${auction.images[0].path}`" 
                                :alt="auction.title"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                            <span v-else class="text-4xl font-bold opacity-20">IMAGE</span>
                            
                            <div class="absolute top-2 right-2">
                                <span class="px-2 py-1 bg-green-600 text-white text-xs font-bold rounded-full shadow-sm">
                                    {{ t('auction.status.won') }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-semibold px-2 py-0.5 bg-secondary text-secondary-foreground rounded-full">
                                    {{ auction.category.name }}
                                </span>
                            </div>
                            <h3 class="text-lg font-bold text-foreground mb-2 line-clamp-1">{{ auction.title }}</h3>
                            <p class="text-xs text-muted-foreground mb-4">by {{ auction.user.name }}</p>
                            
                            <div class="flex justify-between items-end border-t pt-3">
                                <div>
                                    <p class="text-xs text-muted-foreground">{{ t('auction.finalPrice') }}</p>
                                    <p class="text-xl font-bold text-green-600">${{ auction.current_price }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-medium text-primary hover:underline">View Details</span>
                                </div>
                            </div>
                        </div>
                     </Link>
                </div>
            </div>
            <div v-else class="text-center py-12 bg-muted/30 rounded-lg border border-dashed">
                <Gavel class="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <h3 class="text-lg font-medium text-foreground">{{ t('profile.empty.wins') }}</h3>
                <Link :href="route('auctions.index')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm font-medium">
                    <Search class="h-4 w-4" />
                    {{ t('nav.search') }}
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="wins.links.length > 3" class="mt-8 flex justify-center">
                 <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in wins.links"
                        :key="key"
                        :href="link.url || '#'"
                        class="px-3 py-1 rounded border text-sm"
                        :class="{ 'bg-primary text-primary-foreground': link.active, 'opacity-50 pointer-events-none': !link.url }"
                        v-html="link.label"
                    />
                 </div>
            </div>
        </div>
    </AppLayout>
</template>
