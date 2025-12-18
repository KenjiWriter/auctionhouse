<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { User, Gavel, Package, Calendar, Edit, ChevronRight } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import CountdownTimer from '@/components/Auction/CountdownTimer.vue';

const { t } = useI18n();

const props = defineProps<{
    profileUser: {
        id: number;
        name: string;
        email: string;
        created_at: string;
    };
    isOwner: boolean;
    stats: {
        total_auctions: number;
        auctions_won: number;
        active_auctions: number;
        joined: string;
    };
    auctions: {
        data: Array<{
            id: number;
            title: string;
            current_price: number;
            starting_price: number;
            ends_at: string;
            status: string;
            images: Array<{ path: string }>;
            category: { name: string };
        }>;
        links: Array<any>;
    };
}>();

const tabs = computed(() => {
    const items = [
        { name: 'profile.tabs.auctions', href: route('profile', props.profileUser.id), current: true, icon: Package },
    ];

    if (props.isOwner) {
        items.push(
            { name: 'profile.tabs.wins', href: route('profile.wins'), current: false, icon: Gavel },
            { name: 'profile.tabs.bidding', href: route('profile.bidding'), current: false, icon: Gavel }
        );
    }
    
    return items;
});
</script>

<template>
    <Head :title="profileUser.name" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Profile Header -->
            <div class="bg-card border rounded-lg shadow-sm mb-6 overflow-hidden">
                <div class="h-32 bg-primary/10"></div>
                <div class="px-6 pb-6 relative">
                     <div class="flex flex-col sm:flex-row items-start sm:items-end -mt-12 mb-4 gap-4">
                        <div class="h-24 w-24 rounded-full bg-background border-4 border-background flex items-center justify-center shadow-md">
                            <span class="text-3xl font-bold text-primary">{{ profileUser.name.charAt(0) }}</span>
                        </div>
                        <div class="flex-1 mb-2">
                            <h1 class="text-2xl font-bold text-foreground">{{ profileUser.name }}</h1>
                            <div class="flex items-center text-sm text-muted-foreground gap-4 mt-1">
                                <span class="flex items-center gap-1">
                                    <Calendar class="h-4 w-4" />
                                    {{ t('profile.stats.joined') }}: {{ stats.joined }}
                                </span>
                                <span v-if="isOwner" class="text-xs bg-secondary text-secondary-foreground px-2 py-0.5 rounded-full">
                                    {{ profileUser.email }}
                                </span>
                            </div>
                        </div>
                        <div v-if="isOwner">
                            <Link :href="route('register.complete')" class="flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors text-sm font-medium">
                                <Edit class="h-4 w-4" />
                                {{ t('profile.edit') }}
                            </Link>
                        </div>
                     </div>

                     <!-- Stats Grid -->
                     <div class="grid grid-cols-2 md:grid-cols-4 gap-4 border-t pt-4">
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <div class="text-2xl font-bold text-primary">{{ stats.total_auctions }}</div>
                            <div class="text-xs text-muted-foreground uppercase tracking-wide font-medium">{{ t('profile.stats.auctions') }}</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <div class="text-2xl font-bold text-green-600">{{ stats.auctions_won }}</div>
                            <div class="text-xs text-muted-foreground uppercase tracking-wide font-medium">{{ t('profile.stats.wins') }}</div>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <div class="text-2xl font-bold text-blue-600">{{ stats.active_auctions }}</div>
                            <div class="text-xs text-muted-foreground uppercase tracking-wide font-medium">{{ t('profile.stats.active') }}</div>
                        </div>
                     </div>
                </div>
            </div>

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

            <!-- Tab Content (Auctions List) -->
            <div v-if="auctions.data.length > 0" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="auction in auctions.data" :key="auction.id" class="group bg-card border rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-all">
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
                                <span v-if="auction.status === 'active'" class="px-2 py-1 bg-green-500/90 text-white text-xs font-bold rounded-full backdrop-blur-sm">
                                    Active
                                </span>
                                <span v-else class="px-2 py-1 bg-gray-500/90 text-white text-xs font-bold rounded-full backdrop-blur-sm">
                                    {{ auction.status }}
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
                            
                            <div class="flex justify-between items-end mt-4">
                                <div>
                                    <p class="text-xs text-muted-foreground">Current Price</p>
                                    <p class="text-xl font-bold text-primary">${{ auction.current_price || auction.starting_price }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">Ends in</p>
                                    <CountdownTimer :date="auction.ends_at" class="text-sm font-medium" />
                                </div>
                            </div>
                        </div>
                     </Link>
                </div>
            </div>
            <div v-else class="text-center py-12 bg-muted/30 rounded-lg border border-dashed">
                <Package class="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <h3 class="text-lg font-medium text-foreground">{{ t('profile.empty.auctions') }}</h3>
                <Link v-if="isOwner" :href="route('auctions.create')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm font-medium">
                    <PlusCircle class="h-4 w-4" />
                    {{ t('nav.add_auction') }}
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="auctions.links.length > 3" class="mt-8 flex justify-center">
                 <div class="flex gap-1">
                    <Link
                        v-for="(link, key) in auctions.links"
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
