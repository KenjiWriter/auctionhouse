<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { route } from 'ziggy-js';

const { t } = useI18n();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const props = defineProps<{
    auction: {
        id: number;
        title: string;
        description: string;
        current_price: number;
        starting_price: number;
        category: { name: string };
        user: { id: number; name: string };
        user_id: number;
        ends_at: string;
        status: string;
        images: Array<{ path: string }>;
        bids: Array<{ id: number; amount: number; user: { id: number; name: string }; user_id: number; created_at: string }>;
    };
}>();

const mainImage = ref(props.auction.images[0]?.path || null);
const bidAmount = ref<number | ''>('');
const isOutbid = ref(false);
const showToast = ref(false);
const toastMessage = ref('');

const currentPrice = ref(Number(props.auction.current_price || props.auction.starting_price));
const bids = ref([...props.auction.bids]);

// Compute User State
const userState = computed(() => {
    if (!currentUser.value) return 'guest';
    if (props.auction.user_id === currentUser.value.id) return 'owner';

    const myLastBid = bids.value.find(b => b.user.id === currentUser.value.id);
    if (!myLastBid) return 'not_participating';

    const highestBid = bids.value[0];
    if (highestBid && highestBid.user.id === currentUser.value.id) return 'leading';

    return 'losing';
});

const canBid = computed(() => {
    if (props.auction.status !== 'active') return false;
    if (userState.value === 'owner') return false;
    const min = currentPrice.value;
    // Simple check: client side validation help, backend is source of truth
    return true; 
});

const minBidAmount = computed(() => {
    return currentPrice.value + 0.01; // minimal increment example
});

const form = useForm({
    amount: '',
});

const submitBid = () => {
    if (!bidAmount.value) return;
    
    form.amount = bidAmount.value.toString();
    form.post(route('auctions.bid', props.auction.id), {
        onSuccess: () => {
             bidAmount.value = '';
             displayToast(t('auction.bid_success'), 'success');
        },
        onError: () => {
             // Inertia handles errors automatically in form.errors
        },
        preserveScroll: true,
    });
};

const displayToast = (msg: string, type: 'success' | 'error' | 'info' = 'info') => {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => showToast.value = false, 3000);
};

// Real-time updates
onMounted(() => {
    // Listen for Public Updates
    // @ts-ignore
    window.Echo.channel(`auctions.${props.auction.id}`)
        .listen('BidPlaced', (e: any) => {
            currentPrice.value = Number(e.bid.amount);
            bids.value.unshift({
                id: e.bid.id,
                amount: e.bid.amount,
                user: e.bid.user,
                user_id: e.bid.user_id,
                created_at: e.bid.created_at
            });
            
            // Pulse animation trigger could go here
        });

    // Listen for Private Outbid events
    if (currentUser.value) {
        // @ts-ignore
        window.Echo.private(`App.Models.User.${currentUser.value.id}`)
            .listen('Outbid', (e: any) => {
                if (e.auctionId === props.auction.id) {
                    isOutbid.value = true;
                    displayToast(t('auction.outbid'), 'error');
                    if (navigator.vibrate) navigator.vibrate([200, 100, 200]);
                    setTimeout(() => isOutbid.value = false, 2000);
                }
            });
    }
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`auctions.${props.auction.id}`);
    if (currentUser.value) {
        // @ts-ignore
        window.Echo.leave(`App.Models.User.${currentUser.value.id}`);
    }
});
</script>

<template>
    <Head :title="auction.title" />

    <AppLayout>
        <!-- Toast Notification -->
        <div v-if="showToast" class="fixed top-20 right-4 z-50 px-4 py-2 rounded shadow-lg text-white transition-opacity duration-300"
             :class="toastMessage === t('auction.outbid') ? 'bg-red-600' : 'bg-green-600'">
            {{ toastMessage }}
        </div>

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
                    <div class="flex justify-between items-start">
                         <span class="text-sm font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                            {{ auction.category.name }}
                        </span>
                        <!-- Status Badge -->
                        <div v-if="userState !== 'guest' && userState !== 'owner'" class="px-3 py-1 rounded-full text-sm font-bold"
                             :class="{
                                'bg-green-100 text-green-800': userState === 'leading',
                                'bg-red-100 text-red-800': userState === 'losing',
                                'bg-gray-100 text-gray-800': userState === 'not_participating'
                             }">
                             {{ t(`auction.status.${userState}`) }}
                        </div>
                    </div>
                    <h1 class="mt-2 text-3xl font-bold text-foreground">{{ auction.title }}</h1>
                    <p class="text-muted-foreground">by {{ auction.user.name }}</p>
                </div>

                <div class="bg-card border rounded-lg p-6 shadow-sm transition-colors duration-500"
                     :class="{'bg-red-50 border-red-200 dark:bg-red-900/20': isOutbid}">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <p class="text-sm text-muted-foreground">Current Price</p>
                            <p class="text-3xl font-bold text-primary transition-all duration-300" :class="{'scale-110 text-red-600': isOutbid}">
                                ${{ currentPrice.toFixed(2) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-muted-foreground">Ends In</p>
                            <p class="text-xl font-bold">{{ new Date(auction.ends_at).toLocaleDateString() }}</p> 
                        </div>
                    </div>

                    <div class="space-y-4">
                         <!-- Bidding Form -->
                         <div v-if="userState === 'owner'" class="p-3 bg-yellow-50 text-yellow-800 rounded text-center text-sm">
                             {{ t('validation.owner_cannot_bid') }}
                         </div>
                         <div v-else-if="auction.status !== 'active'" class="p-3 bg-gray-100 text-gray-600 rounded text-center text-sm">
                             {{ auction.status === 'upcoming' ? t('validation.auction_not_active') : t('validation.auction_ended') }}
                         </div>
                         <form v-else @submit.prevent="submitBid" class="flex gap-2">
                             <input 
                                v-model="bidAmount" 
                                type="number" 
                                step="0.01" 
                                :min="minBidAmount"
                                class="flex-1 rounded-md border-border bg-background" 
                                :placeholder="t('auction.bid_too_low_start', { min: minBidAmount.toFixed(2) })"
                                required
                                :disabled="form.processing"
                             />
                             <button 
                                type="submit" 
                                :disabled="form.processing || !bidAmount || bidAmount <= currentPrice"
                                class="px-6 py-2 bg-primary text-primary-foreground rounded-md font-bold hover:bg-primary/90 disabled:opacity-50 flex items-center gap-2"
                            >
                                 <span v-if="form.processing" class="animate-spin">⏳</span>
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
                    <transition-group name="list" tag="tbody" class="divide-y relative">
                        <tr v-for="bid in bids" :key="bid.id" class="hover:bg-muted/50 transition-all duration-500">
                            <td class="px-4 py-3 font-medium">
                                {{ bid.user.name }}
                                <span v-if="currentUser && bid.user.id === currentUser.id" class="ml-1 text-xs text-primary font-bold">(You)</span>
                            </td>
                            <td class="px-4 py-3 font-bold">${{ Number(bid.amount).toFixed(2) }}</td>
                            <td class="px-4 py-3 text-right text-muted-foreground">{{ new Date(bid.created_at).toLocaleTimeString() }}</td>
                        </tr>
                         <tr v-if="bids.length === 0" key="empty">
                            <td colspan="3" class="px-4 py-6 text-center text-muted-foreground">No bids yet. be the first!</td>
                        </tr>
                    </transition-group>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
  background-color: rgba(var(--primary), 0.1);
}
</style>
