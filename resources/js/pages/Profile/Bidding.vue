<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { Gavel, Package, Search, TrendingUp, AlertCircle } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import { AppPageProps } from '@/types';
import CountdownTimer from '@/components/Auction/CountdownTimer.vue';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const user = computed(() => page.props.auth?.user);

const props = defineProps<{
    bidding: Array<{
        auction: {
            id: number;
            title: string;
            current_price: number;
            ends_at: string;
            status: string;
            images: Array<{ path: string }>;
            category: { name: string };
            user: { id: number; name: string };
        };
        is_leading: boolean;
        user_bid_amount: number;
        current_price: number;
    }>;
}>();

const tabs = [
    { name: 'profile.tabs.auctions', href: route('profile.mine'), current: false, icon: Package },
    { name: 'profile.tabs.wins', href: route('profile.wins'), current: false, icon: Gavel },
    { name: 'profile.tabs.bidding', href: route('profile.bidding'), current: true, icon: Gavel },
];
</script>

<template>
    <Head :title="t('nav.bidding')" />

    <AppLayout>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <Gavel class="h-6 w-6 text-primary" />
                {{ t('nav.bidding') }}
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

            <!-- Bidding List -->
            <div v-if="bidding.length > 0" class="space-y-4">
                <div v-for="item in bidding" :key="item.auction.id" class="group bg-card border rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col md:flex-row overflow-hidden">
                    <!-- Image -->
                    <div class="w-full md:w-48 h-32 md:h-auto bg-muted flex-shrink-0 relative overflow-hidden">
                         <img 
                            v-if="item.auction.images?.[0]" 
                            :src="`/storage/${item.auction.images[0].path}`" 
                            :alt="item.auction.title"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <span v-else class="flex items-center justify-center h-full text-2xl font-bold opacity-20">IMAGE</span>
                        
                         <div class="absolute top-2 left-2">
                            <span 
                                class="px-2 py-1 text-white text-xs font-bold rounded-full shadow-sm flex items-center gap-1"
                                :class="item.is_leading ? 'bg-green-600' : 'bg-red-500'"
                            >
                                <TrendingUp v-if="item.is_leading" class="h-3 w-3" />
                                <AlertCircle v-else class="h-3 w-3" />
                                {{ item.is_leading ? t('auction.status.leading') : t('auction.status.losing') }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 flex-1 flex flex-col md:flex-row gap-4">
                        <div class="flex-1">
                             <div class="flex items-start justify-between mb-1">
                                <span class="text-xs font-semibold px-2 py-0.5 bg-secondary text-secondary-foreground rounded-full">
                                    {{ item.auction.category.name }}
                                </span>
                            </div>
                            <Link :href="route('auctions.show', item.auction.id)" class="text-lg font-bold text-foreground hover:underline line-clamp-1 mb-1">
                                {{ item.auction.title }}
                            </Link>
                            <p class="text-xs text-muted-foreground">by {{ item.auction.user.name }}</p>

                            <div class="mt-4 flex gap-6 text-sm">
                                <div>
                                    <p class="text-xs text-muted-foreground">Your Max Bid</p>
                                    <p class="font-semibold text-muted-foreground">${{ item.user_bid_amount }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-muted-foreground">Ends in</p>
                                    <CountdownTimer :date="item.auction.ends_at" class="font-medium" />
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end justify-center border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-4 min-w-[120px]">
                            <p class="text-xs text-muted-foreground">Current Price</p>
                            <p class="text-2xl font-bold text-primary mb-3">${{ item.current_price }}</p>
                            
                            <Link 
                                :href="route('auctions.show', item.auction.id)" 
                                class="w-full text-center px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm font-medium transition-colors"
                                :class="{ 'bg-red-500 hover:bg-red-600': !item.is_leading }"
                            >
                                {{ item.is_leading ? 'View Auction' : 'Bid Again' }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-12 bg-muted/30 rounded-lg border border-dashed">
                <Gavel class="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <h3 class="text-lg font-medium text-foreground">{{ t('profile.empty.bidding') }}</h3>
                <Link :href="route('auctions.index')" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 text-sm font-medium">
                    <Search class="h-4 w-4" />
                    {{ t('nav.search') }}
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
