import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
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
        is_watched: boolean;
        curent_price: number;
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
const bidAmount = ref('' as number | string);
const isOutbid = ref(false);
const showToast = ref(false);
const toastMessage = ref('');
const isWatched = ref(props.auction.is_watched || false);

const currentPrice = ref(Number(props.auction.current_price || props.auction.starting_price));
const bids = ref([...props.auction.bids]);

const toggleWatch = () => {
    if (!currentUser.value) return; // Or redirect to login
    
    // Optimistic UI update
    isWatched.value = !isWatched.value;

    router.post(route('auctions.watch', props.auction.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
             displayToast(isWatched.value ? t('auction.status.added_to_watchlist') : t('auction.status.removed_from_watchlist'), 'success');
        },
        onError: () => {
            // Revert on error
            isWatched.value = !isWatched.value;
        }
    });
};

// ... (existing computed properties)

// ...

// Template changes
// Inside <div class="flex justify-between items-start">
// BEFORE <span class="text-sm ...">
/*
                        <div class="flex gap-2">
                             <span class="text-sm font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                                {{ auction.category.name }}
                            </span>
                             <!-- Watch Button -->
                            <button 
                                v-if="currentUser"
                                @click="toggleWatch"
                                class="p-1 rounded-full hover:bg-muted transition-colors"
                                :title="isWatched ? 'Remove from Watchlist' : 'Add to Watchlist'"
                            >
                                <svg v-if="isWatched" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-400 fill-current" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                 <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </button>
                        </div>
*/
                        <!-- Status Badge -->
                        <div v-if="userState !== 'guest' && userState !== 'owner'" class="px-3 py-1 rounded-full text-sm font-bold"
                             :class="{
                                'bg-green-100 text-green-800': userState === 'leading',
                                'bg-red-100 text-red-800': userState === 'losing',
                                'bg-gray-100 text-gray-800': userState === 'not_participating'
                <div>
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
                                :title="isWatched ? 'Remove from Watchlist' : 'Add to Watchlist'"
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
                            <p class="text-sm text-muted-foreground">Ends In</p>
                            <p class="text-xl font-bold">{{ new Date(auction.ends_at).toLocaleDateString() }}</p> 
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
