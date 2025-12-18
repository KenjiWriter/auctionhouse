<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { User, Mail, Phone, ShoppingCart, CircleAlert } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

const { t } = useI18n();

const props = defineProps<{
    winner: {
        id: number;
        name: string;
        email: string;
        phone?: string;
    };
    finalPrice: number;
    auctionId: number;
    sellerNotifiedAt: string | null;
}>();

const emit = defineEmits(['contact']);
</script>

<template>
    <div class="bg-card border border-green-500/30 rounded-lg shadow-sm overflow-hidden mb-6">
        <div class="bg-green-500/10 px-4 py-3 border-b border-green-500/20 flex items-center gap-2">
            <ShoppingCart class="h-5 w-5 text-green-600" />
            <h3 class="font-bold text-green-800">{{ t('auction.winnerInfo') }}</h3>
            
            <div v-if="!sellerNotifiedAt" class="ml-auto flex items-center gap-1.5 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full animate-pulse">
                <CircleAlert class="h-3 w-3" />
                {{ t('auction.endedActionRequired') }}
            </div>
        </div>
        
        <div class="p-4 sm:p-6 grid gap-6 md:grid-cols-2">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
                        {{ winner.name.charAt(0) }}
                    </div>
                    <div>
                        <p class="font-bold text-lg text-foreground">{{ winner.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ t('auction.winner') }}</p>
                    </div>
                </div>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2 text-muted-foreground">
                        <Mail class="h-4 w-4" />
                        <span>{{ winner.email }}</span>
                    </div>
                     <div v-if="winner.phone" class="flex items-center gap-2 text-muted-foreground">
                        <Phone class="h-4 w-4" />
                        <span>{{ winner.phone }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-center items-end border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-6">
                <div class="text-right mb-4">
                    <p class="text-sm text-muted-foreground mb-1">{{ t('auction.finalPrice') }}</p>
                    <p class="text-3xl font-bold text-green-600">${{ finalPrice }}</p>
                </div>

                <button 
                    @click="$emit('contact')"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 font-medium transition-colors shadow-sm"
                >
                    <Mail class="h-4 w-4" />
                    {{ t('auction.contactWinner') }}
                </button>
            </div>
        </div>
    </div>
</template>
