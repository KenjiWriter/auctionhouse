<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import CountdownTimer from '@/components/Auction/CountdownTimer.vue';
import { route } from 'ziggy-js';

const { t } = useI18n();

defineProps<{
    auctions: Array<{
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
        <div class="space-y-6">
            <section>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-foreground">Auctions Starting Soon</h2>
                    <a href="#" class="text-sm text-primary hover:underline">View All</a>
                </div>

                <div v-if="auctions.length > 0" class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <div
                        v-for="auction in auctions"
                        :key="auction.id"
                        class="group relative overflow-hidden rounded-lg bg-card border shadow-sm transition-all hover:shadow-md"
                    >
                         <Link :href="route('auctions.show', auction.id)">
                             <div class="aspect-square w-full overflow-hidden bg-muted flex items-center justify-center">
                                <img
                                    v-if="auction.image"
                                    :src="auction.image"
                                    :alt="auction.title"
                                    class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <span v-else class="text-muted-foreground opacity-50 font-bold">NO IMAGE</span>
                            </div>
                            <div class="p-3">
                                <h3 class="truncate text-sm font-medium text-foreground group-hover:text-primary">
                                    {{ auction.title }}
                                </h3>
                                <div class="mt-1 flex items-center justify-between text-[10px] sm:text-xs">
                                    <span class="font-semibold text-primary">${{ auction.current_bid }}</span>
                                    <CountdownTimer :date="auction.ends_at" />
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>
                <div v-else class="text-center py-12 text-muted-foreground">
                    No active auctions at the moment.
                </div>
            </section>
        </div>
    </AppLayout>
</template>
