<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const auctions = [
    {
        id: 1,
        title: 'Vintage Camera',
        image: 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        current_bid: 120,
        ends_in: '2h 15m',
    },
    {
        id: 2,
        title: 'Mechanical Keyboard',
        image: 'https://images.unsplash.com/photo-1595225476474-87563907a212?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        current_bid: 85,
        ends_in: '45m',
    },
    {
        id: 3,
        title: 'Gaming Headset',
        image: 'https://images.unsplash.com/photo-1583394838336-acd977736f90?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        current_bid: 45,
        ends_in: '5h 30m',
    },
    {
        id: 4,
        title: 'Smart Watch',
        image: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60',
        current_bid: 150,
        ends_in: '1d 2h',
    },
];
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

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    <div
                        v-for="auction in auctions"
                        :key="auction.id"
                        class="group relative overflow-hidden rounded-lg bg-card border shadow-sm transition-all hover:shadow-md"
                    >
                         <div class="aspect-square w-full overflow-hidden bg-muted">
                            <img
                                :src="auction.image"
                                :alt="auction.title"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            />
                        </div>
                        <div class="p-3">
                            <h3 class="truncate text-sm font-medium text-foreground group-hover:text-primary">
                                {{ auction.title }}
                            </h3>
                            <div class="mt-1 flex items-center justify-between text-xs">
                                <span class="font-semibold text-primary">${{ auction.current_bid }}</span>
                                <span class="text-muted-foreground">{{ auction.ends_in }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
