<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import { route } from 'ziggy-js';

const { t } = useI18n();

const props = defineProps<{
    auctions: {
        data: Array<{
            id: number;
            title: string;
            current_price: number;
            starting_price: number;
            category: { name: string };
            user: { name: string };
            ends_at: string;
            status: string;
        }>;
    };
    filters?: {
        search: string;
        category: string;
    };
    categories?: Array<{ id: number; name: string }>;
    title?: string;
}>();

const search = ref(props.filters?.search || '');
const category = ref(props.filters?.category || '');

watch([search, category], debounce(() => {
    router.get(route('auctions.index'), { search: search.value, category: category.value }, { preserveState: true, replace: true });
}, 300));
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

        <div v-if="filters" class="mb-6 flex gap-4">
            <input 
                v-model="search" 
                type="text" 
                placeholder="Search auctions..." 
                class="flex-1 rounded-md border-border bg-background"
            />
            <select v-model="category" class="rounded-md border-border bg-background">
                <option value="">All Categories</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="auction in auctions.data" :key="auction.id" class="bg-card border rounded-lg shadow-sm overflow-hidden">
                <div class="h-48 bg-muted flex items-center justify-center text-muted-foreground">
                    <!-- Image placeholder -->
                    <span class="text-4xl font-bold opacity-20">IMAGE</span>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold px-2 py-1 bg-secondary text-secondary-foreground rounded-full">
                            {{ auction.category.name }}
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
                        <div class="text-right">
                             <p class="text-xs text-muted-foreground">Ends</p>
                             <p class="text-sm font-medium">{{ new Date(auction.ends_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
