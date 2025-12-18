<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuctionCard from '@/components/Auction/AuctionCard.vue';

const { t } = useI18n();

defineProps<{
    auctions: Array<{
        id: number;
        title: string;
        image: string | null;
        current_bid: number;
        ends_at: string;
    }>;
    recommendations: Array<{
        id: number;
        title: string;
        image: string | null;
        current_bid: number;
        ends_at: string;
    }>;
}>();
</script>

<template>
    <Head :title="t('nav.home')" />

    <AppLayout>
        <div class="space-y-12">
            <!-- For You Section -->
             <section v-if="recommendations && recommendations.length > 0">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-foreground">{{ t('home.forYou.title') }}</h2>
                        <p class="text-sm text-muted-foreground">{{ t('home.forYou.subtitle') }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <AuctionCard 
                        v-for="auction in recommendations" 
                        :key="auction.id" 
                        :auction="auction" 
                    />
                </div>
            </section>

            <!-- Active Auctions Section -->
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-foreground">Auctions Starting Soon</h2>
                    <Link :href="route('auctions.index')" class="text-sm text-primary hover:underline">View All</Link>
                </div>

                <div v-if="auctions.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <AuctionCard 
                        v-for="auction in auctions" 
                        :key="auction.id" 
                        :auction="auction" 
                    />
                </div>
                <div v-else class="text-center py-12 text-muted-foreground">
                    No active auctions at the moment.
                </div>
            </section>
        </div>
    </AppLayout>
</template>
