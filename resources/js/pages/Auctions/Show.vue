<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { route } from 'ziggy-js';
import { useCountdown } from '@/composables/useCountdown';
import { Mail } from 'lucide-vue-next';
import WinnerPanel from '@/components/Auction/WinnerPanel.vue';
import PostAuctionPanel from '@/components/Auction/PostAuctionPanel.vue';

const { t } = useI18n();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const props = defineProps<{
    auction: {
        id: number;
        title: string;
        description: string;
        is_watched: boolean;
        current_price: number;
        starting_price: number;
        category: { name: string };
        user: { id: number; name: string };
        user_id: number;
        starts_at: string | null;
        ends_at: string;
        status: string;
        winner?: { id: number; name: string; email: string; phone?: string };
        winner_id?: number;
        seller_notified_at?: string;
        post_status?: string;
        seller_contacted_at?: string | null;
        buyer_confirmed_at?: string | null;
        seller_confirmed_at?: string | null;
        disputed_at?: string | null;
        dispute_reason?: string | null;
        images: Array<{ path: string }>;
        bids: Array<{ id: number; amount: number; user: { id: number; name: string }; user_id: number; created_at: string }>;
    };
    userAutoBid?: {
        id: number;
        max_amount: number;
        is_active: boolean;
    } | null;
}>();

const mainImage = ref(props.auction.images[0]?.path || null);
const isOutbid = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const isWatched = ref(props.auction.is_watched || false);

const currentPrice = ref(Number(props.auction.current_price || props.auction.starting_price));
const bids = ref([...props.auction.bids]);

const form = useForm({
    amount: '' as number | string
});

const isOwner = computed(() => currentUser.value?.id === props.auction.user_id);

const { formattedTime: startsIn, isExpired: isStarted } = useCountdown(props.auction.starts_at);
const { formattedTime: endsIn, isExpired: isEnded } = useCountdown(props.auction.ends_at);

const autoBidForm = useForm({
    max_amount: props.userAutoBid?.max_amount || '',
    is_active: props.userAutoBid?.is_active ?? false
});

const submitAutoBid = () => {
    autoBidForm.post(route('auctions.autobid', props.auction.id), {
        preserveScroll: true,
        onSuccess: () => {
            toastMessage.value = t('auction.auto_bid_updated');
            showToast.value = true;
        }
    });
};

const contactSeller = () => {
    router.post(route('conversations.store'), {
        auction_id: props.auction.id,
        receiver_id: props.auction.user_id
    });
};

const contactWinner = () => {
    if (!props.auction.winner_id) return;

    router.post(route('conversations.store'), {
        auction_id: props.auction.id,
        receiver_id: props.auction.winner_id
    }, {
        onSuccess: () => {
             // Also mark as notified if not already
             if (!props.auction.seller_notified_at) {
                 router.post(route('auctions.notified', props.auction.id), {}, { preserveScroll: true });
             }
        }
    });

    // Optimistically update if needed, but router reload should handle
};

const userState = computed(() => {
    if (!currentUser.value) return 'guest';
    if (isOwner.value) return 'owner';
    
    const displayBids = bids.value || [];
    if (displayBids.length === 0) return 'not_participating';

    const highestBidderId = displayBids[0]?.user_id;
    if (highestBidderId === currentUser.value.id) return 'leading';

    const hasBid = displayBids.some(b => b.user_id === currentUser.value.id);
    if (hasBid) return 'losing';

    return 'not_participating';
});

const minBidAmount = computed(() => {
    const current = Number(currentPrice.value);
    // If no bids, min bid is starting price. If bids, must be higher (e.g. +1 or +0.01)
    // For now, let's say must be > current price + 1 if there are bids, or just > current if no bids?
    // Logic: if no bids, can bid starting price.
    if (!bids.value || bids.value.length === 0) {
        return Number(props.auction.starting_price);
    }
    return current + 1.00; // Increment step
});

const submitBid = () => {
    form.post(route('auctions.bid', props.auction.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            toastMessage.value = t('auction.bid_success');
            showToast.value = true;
            // Update local bids and price if needed, but Inertia reload should handle it
        },
    });
};

const toggleWatch = () => {
    if (!currentUser.value) return; 
    
    isWatched.value = !isWatched.value;

    router.post(route('auctions.watch', props.auction.id), {}, {
        preserveScroll: true,
        onError: () => {
            isWatched.value = !isWatched.value;
        }
    });
};

onMounted(() => {
    // @ts-ignore
    console.log(`Subscribing to auctions.${props.auction.id}`);
    window.Echo.channel(`auctions.${props.auction.id}`)
        .listen('.bid.placed', (e: { bid: any }) => {
            console.log('BidPlaced event received:', e);
            bids.value.unshift(e.bid);
            currentPrice.value = Number(e.bid.amount);
            
            // Check if we were outbid (just visual, logic is computed)
            if (currentUser.value && e.bid.user_id !== currentUser.value.id) {
                // Determine if we participated
                const myLastBid = bids.value.find(b => b.user_id === currentUser.value.id);
                // If we have a bid and it's not the top one (checked above), we might be outbid.
                // However, logic is simpler: if new bid is not ours, and we are 'losing', show toast?
                // The 'userState' computed property will update automatically.
                if (myLastBid) {
                     isOutbid.value = true;
                     toastMessage.value = t('auction.outbid');
                     showToast.value = true;
                }
            }
        });

    if (currentUser.value) {
        // @ts-ignore
        window.Echo.private(`App.Models.User.${currentUser.value.id}`)
            .listen('.user.outbid', (e: any) => {
                console.log('Outbid event received:', e);
                if (e.auctionId === props.auction.id) {
                    isOutbid.value = true;
                    toastMessage.value = t('auction.outbid');
                    showToast.value = true;
                }
            });
    }
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`auctions.${props.auction.id}`);
});
</script>

<template>
    <Head :title="auction.title" />

    <AppLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-8">
                <!-- Left Column: Images -->
                <div class="space-y-4">
                     <div class="aspect-square bg-muted rounded-lg overflow-hidden relative shadow-sm">
                         <img v-if="mainImage" :src="`/storage/${mainImage}`" class="w-full h-full object-cover" />
                         <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground">No Image</div>
                     </div>
                     <div class="grid grid-cols-4 gap-2">
                        <button
                            v-for="(img, index) in auction.images"
                            :key="index"
                            @click="mainImage = img.path"
                            class="aspect-square rounded-md overflow-hidden border-2 transition-all"
                            :class="mainImage === img.path ? 'border-primary' : 'border-transparent hover:border-muted-foreground/50'"
                        >
                            <img :src="`/storage/${img.path}`" class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    
                    <!-- Winner Panel for Seller -->
                    <WinnerPanel 
                        v-if="isOwner && auction.status === 'ended' && auction.winner"
                        :winner="auction.winner"
                        :final-price="Number(auction.current_price)"
                        :auction-id="auction.id"
                        :seller-notified-at="auction.seller_notified_at ?? null"
                        @contact="contactWinner"
                    />

                    <div class="flex justify-between items-start">
                        <div class="flex gap-2">
                             <span class="text-sm font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                                {{ auction.category.name }}
                            </span>
                             <!-- Watch Button -->
                            <button 
                                v-if="currentUser"
                                @click="toggleWatch"
                                class="p-1 rounded-full hover:bg-muted transition-colors"
                                :title="isWatched ? t('auction.status.removed_from_watchlist') : t('auction.status.added_to_watchlist')"
                            >
                                <svg v-if="isWatched" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400 fill-current" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                 <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </button>
                        </div>
                        
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
                            <p v-if="auction.status === 'upcoming' && !isStarted" class="text-sm text-muted-foreground">Starts In</p>
                            <p v-else class="text-sm text-muted-foreground">Ends In</p>
                            <p v-if="auction.status === 'upcoming' && !isStarted" class="text-xl font-bold">{{ startsIn }}</p> 
                            <p v-else class="text-xl font-bold text-red-600 dark:text-red-400">{{ endsIn }}</p> 
                        </div>
                    </div>

                    <div class="space-y-4">
                         <!-- Bidding Form -->
                         <div v-if="userState === 'owner'" class="p-3 bg-yellow-50 text-yellow-800 rounded text-center text-sm">
                             <p class="mb-2">{{ t('validation.owner_cannot_bid') }}</p>
                             <Link 
                                v-if="['ended', 'ended_without_sale'].includes(auction.status)"
                                :href="route('auctions.relist', auction.id)"
                                class="inline-block px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                             >
                                 {{ t('auction.relist') }}
                             </Link>
                         </div>
                         <div v-else-if="auction.status !== 'active'" class="p-3 bg-gray-100 text-gray-600 rounded text-center text-sm">
                             {{ auction.status === 'upcoming' ? t('validation.auction_not_active') : t('validation.auction_ended') }}
                         </div>
                         <form v-else @submit.prevent="submitBid" class="flex gap-2">
                             <input 
                                v-model="form.amount" 
                                type="number" 
                                step="0.01" 
                                :min="minBidAmount"
                                class="flex-1 rounded-md border-border bg-background" 
                                :placeholder="t('validation.bid_too_low_start', { min: minBidAmount.toFixed(2) })"
                                required
                                :disabled="form.processing"
                             />
                             <button 
                                type="submit" 
                                :disabled="form.processing || !form.amount"
                                class="px-6 py-2 bg-primary text-primary-foreground rounded-md font-bold hover:bg-primary/90 disabled:opacity-50 flex items-center gap-2"
                            >
                                 <span v-if="form.processing" class="animate-spin">⏳</span>
                                 Bid
                            </button>
                         </form>
                          <div v-if="form.errors.amount" class="text-red-500 text-sm">{{ form.errors.amount }}</div>

                         <!-- Auto Bid Section -->
                         <div v-if="userState !== 'owner' && userState !== 'guest' && auction.status === 'active'" class="mt-6 pt-6 border-t">
                            <h4 class="text-sm font-bold mb-3 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                                {{ t('auction.autoBid') }}
                            </h4>
                            <form @submit.prevent="submitAutoBid" class="space-y-3">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">$</span>
                                        <input 
                                            v-model="autoBidForm.max_amount" 
                                            type="number" 
                                            step="0.01" 
                                            class="w-full pl-7 rounded-md border-border bg-background text-sm" 
                                            :placeholder="t('auction.autoBidMax')"
                                            required
                                        />
                                    </div>
                                    <button 
                                        type="submit" 
                                        :disabled="autoBidForm.processing"
                                        class="px-4 py-2 border border-primary text-primary rounded-md text-sm font-medium hover:bg-primary/5 transition-colors"
                                    >
                                        {{ props.userAutoBid ? t('common.update') : t('common.set') }}
                                    </button>
                                </div>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <div class="relative inline-flex items-center">
                                        <input 
                                            type="checkbox" 
                                            v-model="autoBidForm.is_active" 
                                            class="sr-only peer"
                                            @change="submitAutoBid"
                                        />
                                        <div class="w-9 h-5 bg-muted rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"></div>
                                    </div>
                                    <span class="text-xs text-muted-foreground group-hover:text-foreground transition-colors">
                                        {{ autoBidForm.is_active ? t('auction.autoBidEnabled') : t('auction.autoBidDisabled') }}
                                    </span>
                                </label>
                            </form>
                         </div>
                    </div>
                </div>

                <div class="prose dark:prose-invert">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-medium m-0">Description</h3>
                        <button 
                            v-if="!isOwner && currentUser" 
                            @click="contactSeller"
                            class="flex items-center gap-2 text-sm text-primary hover:underline font-medium"
                        >
                            <Mail class="w-4 h-4" />
                            {{ t('chat.writeToSeller') }}
                        </button>
                    </div>
                    <p>{{ auction.description }}</p>
                </div>

                <!-- Post-Auction Lifecycle Panel -->
                <PostAuctionPanel 
                    v-if="auction.status === 'ended' && auction.winner_id"
                    :auction="auction"
                    :winner="auction.winner"
                    :is-seller="isOwner"
                    :is-winner="currentUser?.id === auction.winner_id"
                />
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
