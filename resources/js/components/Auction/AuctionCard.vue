<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import CountdownTimer from '@/components/Auction/CountdownTimer.vue';

defineProps<{
    auction: {
        id: number;
        title: string;
        image: string | null;
        current_bid: number;
        ends_at: string;
    };
}>();
</script>

<template>
    <div class="group relative overflow-hidden rounded-lg bg-card border shadow-sm transition-all hover:shadow-md">
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
</template>
