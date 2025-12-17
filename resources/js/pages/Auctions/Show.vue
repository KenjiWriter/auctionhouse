<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();

const props = defineProps<{
    auction: {
        id: number;
        title: string;
        description: string;
        current_price: number;
        starting_price: number;
        category: { name: string };
        user: { name: string };
        ends_at: string;
        images: Array<{ path: string }>;
        bids: Array<{ id: number; amount: number; user: { name: string } }>;
    };
}>();

const mainImage = ref(props.auction.images[0]?.path || null);
const bidAmount = ref('');
const form = useForm({
    amount: '',
});

const submitBid = () => {
    form.amount = bidAmount.value;
    form.post(route('auctions.bid', props.auction.id), {
        onSuccess: () => bidAmount.value = '',
        preserveScroll: true,
    });
};

// Real-time updates
import { onMounted, onUnmounted } from 'vue';

onMounted(() => {
    // @ts-ignore
    window.Echo.channel(`auctions.${props.auction.id}`)
        .listen('BidPlaced', (e: any) => {
            props.auction.current_price = e.bid.amount;
            props.auction.bids.unshift({
                id: e.bid.id,
                amount: e.bid.amount,
                user: e.bid.user,
            });
        });
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`auctions.${props.auction.id}`);
});
</script>

<template>
    <Head :title="auction.title" />

    <AppLayout>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Images -->
            <div class="space-y-4">
                <div class="aspect-video bg-muted rounded-lg overflow-hidden flex items-center justify-center relative">
                     <img v-if="mainImage" :src="`/storage/${mainImage}`" class="w-full h-full object-cover" />
                     <span v-else class="text-4xl font-bold opacity-20 text-muted-foreground">NO IMAGE</span>
                </div>
                <!-- Thumbnails -->
                <div class="flex gap-2 overflow-x-auto">
                     <button
                        v-for="image in auction.images"
                        :key="image.path"
                        @click="mainImage = image.path"
                        class="w-20 h-20 rounded-md border-2 overflow-hidden flex-shrink-0"
                        :class="{'border-primary': mainImage === image.path, 'border-transparent': mainImage !== image.path}"
                     >
                        <img :src="`/storage/${image.path}`" class="w-full h-full object-cover" />
                     </button>
                </div>
            </div>

            <!-- Details -->
            <div class="space-y-6">
                <div>
                     <span class="text-sm font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                        {{ auction.category.name }}
                    </span>
                    <h1 class="mt-2 text-3xl font-bold text-foreground">{{ auction.title }}</h1>
                    <p class="text-muted-foreground">by {{ auction.user.name }}</p>
                </div>

                <div class="bg-card border rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Current Price</p>
                            <p class="text-3xl font-bold text-primary">${{ auction.current_price || auction.starting_price }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">Time Left</p>
                            <p class="text-xl font-bold">2h 15m 30s</p> <!-- Mock timer -->
                        </div>
                    </div>

                    <div class="space-y-4">
                         <!-- Bidding Form -->
                         <form @submit.prevent="submitBid" class="flex gap-2">
                             <input 
                                v-model="bidAmount" 
                                type="number" 
                                step="0.01" 
                                class="flex-1 rounded-md border-border bg-background" 
                                placeholder="Enter amount" 
                                required
                             />
                             <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 bg-primary text-primary-foreground rounded-md font-bold hover:bg-primary/90 disabled:opacity-50"
                            >
                                 Bid
                             </button>
                         </form>
                         <div v-if="form.errors.amount" class="text-red-500 text-sm">{{ form.errors.amount }}</div>
                    </div>
                </div>

                <div class="prose dark:prose-invert">
                    <h3 class="text-lg font-medium">Description</h3>
                    <p>{{ auction.description }}</p>
                </div>
            </div>
        </div>

        <!-- Bids History -->
        <div class="mt-12 max-w-2xl">
            <h3 class="text-xl font-bold mb-4">Bid History</h3>
            <div class="rounded-lg border bg-card overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-muted text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Bidder</th>
                            <th class="px-4 py-3 font-medium">Amount</th>
                            <th class="px-4 py-3 font-medium text-right">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="bid in auction.bids" :key="bid.id" class="hover:bg-muted/50">
                            <td class="px-4 py-3 font-medium">{{ bid.user.name }}</td>
                            <td class="px-4 py-3">${{ bid.amount }}</td>
                            <td class="px-4 py-3 text-right">ago</td>
                        </tr>
                         <tr v-if="auction.bids.length === 0">
                            <td colspan="3" class="px-4 py-6 text-center text-muted-foreground">No bids yet. be the first!</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
