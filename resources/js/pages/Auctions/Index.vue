<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';
import { AppPageProps } from '@/types';
import CountdownTimer from '@/components/Auction/CountdownTimer.vue';

const { t } = useI18n();
const page = usePage<AppPageProps>();
const currentUser = computed(() => page.props.auth?.user);

const props = defineProps<{
    auctions: {
        data: Array<{
            id: number;
            title: string;
            current_price: number;
            starting_price: number;
            category: { name: string };
            user: { id: number; name: string };
            ends_at: string;
            starts_at: string | null;
            status: string;
            status: string;
            is_watched?: boolean;
            winner?: { id: number; name: string };
            seller_notified_at?: string;
            images: Array<{ path: string }>;
        }>;
    };
    filters?: {
        search: string;
        category: string;
        category_id?: string; // Sometimes filter might be category_id
        min_price?: number;
        max_price?: number;
        buy_now?: string; // boolean as string 'true'/'false'
        status?: string;
    };
    categories?: Array<{ id: number; name: string }>;
    title?: string;
}>();

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || '');
const minPrice = ref(props.filters?.min_price || '');
const maxPrice = ref(props.filters?.max_price || '');
const buyNow = ref(props.filters?.buy_now === 'true');
const status = ref(props.filters?.status || '');
const showFilters = ref(false);

watch([search, category, minPrice, maxPrice, buyNow, status], debounce(() => {
    router.get(route('auctions.index'), { 
        search: search.value, 
        category: category.value,
        min_price: minPrice.value,
        max_price: maxPrice.value,
        buy_now: buyNow.value ? 'true' : null,
        status: status.value
    }, { preserveState: true, replace: true });
}, 500));
</script>

<template>
    <Head title="Auctions" />

    <AppLayout>
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-foreground">{{ title || 'Auctions' }}</h1>
            <Link :href="route('auctions.create')" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                Create Auction
            </Link>
        </div>

        <div v-if="filters" class="mb-6 space-y-4">
            <div class="flex gap-4">
                <input 
                    v-model="search" 
                    type="text" 
                    placeholder="Search auctions..." 
                    class="flex-1 rounded-md border-border bg-background"
                />
                <select v-model="category" class="rounded-md border-border bg-background w-1/3 md:w-auto">
                    <option value="">All Categories</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
                <button @click="showFilters = !showFilters" class="px-3 py-2 border rounded-md hover:bg-muted" :class="{'bg-muted': showFilters}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                </button>
            </div>

            <!-- Advanced Filters -->
             <div v-if="showFilters" class="p-4 bg-muted/50 rounded-md grid grid-cols-1 md:grid-cols-4 gap-4 transition-all duration-300">
                <div>
                     <label class="block text-xs font-medium mb-1">Price Range</label>
                     <div class="flex gap-2">
                         <input v-model="minPrice" type="number" placeholder="Min" class="w-full rounded-md border-border bg-background text-sm" />
                         <input v-model="maxPrice" type="number" placeholder="Max" class="w-full rounded-md border-border bg-background text-sm" />
                     </div>
                </div>
                <div>
                     <label class="block text-xs font-medium mb-1">Status</label>
                     <select v-model="status" class="w-full rounded-md border-border bg-background text-sm">
                         <option value="">Active & Upcoming</option>
                         <option value="active">Active Only</option>
                         <option value="upcoming">Upcoming Only</option>
                         <option value="ended">Ended</option>
                     </select>
                </div>
                 <div class="flex items-center">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="buyNow" type="checkbox" class="rounded border-border text-primary focus:ring-primary" />
                        <span class="text-sm">Buy Now Available</span>
                    </label>
                </div>
             </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="auction in auctions.data" :key="auction.id" class="bg-card border rounded-lg shadow-sm overflow-hidden">
                <div class="h-48 bg-muted flex items-center justify-center text-muted-foreground overflow-hidden">
                    <img 
                        v-if="auction.images?.[0]" 
                        :src="`/storage/${auction.images[0].path}`" 
                        :alt="auction.title"
                        class="h-full w-full object-cover transition-transform duration-300 hover:scale-105"
                    />
                    <span v-else class="text-4xl font-bold opacity-20">IMAGE</span>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                            {{ auction.category.name }}
                        </span>
                        <div v-if="auction.is_watched" class="ml-2 text-yellow-500" title="Watched">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 fill-current" viewBox="0 0 24 24" stroke="none">
                                <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </div>
                        
                        <!-- Seller Notification Badge -->
                        <span 
                            v-if="currentUser?.id === auction.user.id && auction.status === 'ended' && auction.winner && !auction.seller_notified_at"
                            class="ml-2 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full animate-pulse flex items-center gap-1"
                        >
                            ⚠️ {{ t('auction.endedActionRequired') }}
                        </span>
                        <span v-if="auction.status === 'upcoming'" class="text-xs font-semibold px-2 py-1 bg-blue-100 text-blue-800 rounded-full ml-2">
                            Upcoming
                        </span>
                         <span v-else-if="auction.status === 'ended'" class="text-xs font-semibold px-2 py-1 bg-green-100 text-green-800 rounded-full ml-2">
                            Sold
                        </span>
                        <span v-else-if="auction.status === 'ended_without_sale'" class="text-xs font-semibold px-2 py-1 bg-gray-100 text-gray-800 rounded-full ml-2">
                            Ended (No Sale)
                        </span>
                         <span class="text-xs text-muted-foreground ml-auto">
                             by {{ auction.user.name }}
                         </span>
                    </div>
                    <Link :href="route('auctions.show', auction.id)" class="text-lg font-bold text-foreground hover:underline">
                        {{ auction.title }}
                    </Link>
                    <div class="mt-4 flex justify-between items-end">
                        <div>
                            <p class="text-xs text-muted-foreground">Current Bid</p>
                            <p class="text-xl font-bold text-primary">${{ auction.current_price || auction.starting_price }}</p>
                        </div>
                        <div class="text-right flex flex-col items-end gap-2">
                             <div>
                                 <p v-if="auction.status === 'upcoming' && auction.starts_at" class="text-xs text-muted-foreground">Starts</p>
                                 <p v-else class="text-xs text-muted-foreground">Ends</p>
                                 
                                 <CountdownTimer 
                                    :date="auction.status === 'upcoming' ? auction.starts_at : auction.ends_at" 
                                    class="text-sm font-medium"
                                 />
                             </div>
                             <Link 
                                v-if="currentUser && currentUser.id === auction.user.id && ['ended', 'ended_without_sale'].includes(auction.status)"
                                :href="route('auctions.relist', auction.id)"
                                class="text-xs px-2 py-1 bg-primary text-primary-foreground rounded hover:bg-primary/90"
                             >
                                Relist
                             </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
