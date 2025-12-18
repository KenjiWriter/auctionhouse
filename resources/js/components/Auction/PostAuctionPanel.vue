<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { CheckCircle, Clock, AlertTriangle, Ban } from 'lucide-vue-next';
import { ref } from 'vue';

const { t } = useI18n();

interface AuctionProps {
    id: number;
    title: string;
    status: string;
    post_status?: string;
    winner_id?: number | null;
    user_id: number;
    seller_contacted_at?: string | null;
    buyer_confirmed_at?: string | null;
    seller_confirmed_at?: string | null;
    disputed_at?: string | null;
    dispute_reason?: string | null;
}

interface WinnerProps {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    auction: AuctionProps;
    winner?: WinnerProps;
    isSeller: boolean;
    isWinner: boolean;
}>();

const disputeReason = ref('');
const showDisputeModal = ref(false);

const getStatusIcon = () => {
    switch (props.auction.post_status) {
        case 'completed':
            return CheckCircle;
        case 'disputed':
            return AlertTriangle;
        case 'cancelled':
            return Ban;
        default:
            return Clock;
    }
};

const getStatusColor = () => {
    switch (props.auction.post_status) {
        case 'completed':
            return 'text-green-600';
        case 'disputed':
            return 'text-red-600';
        case 'cancelled':
            return 'text-gray-600';
        case 'awaiting_payment':
            return 'text-yellow-600';
        default:
            return 'text-blue-600';
    }
};

const contactWinner = () => {
    router.post(route('auctions.contactWinner', props.auction.id));
};

const markPaymentReceived = () => {
    router.post(route('auctions.paymentReceived', props.auction.id));
};

const markCompleted = () => {
    router.post(route('auctions.markCompleted', props.auction.id));
};

const openDisputeModal = () => {
    showDisputeModal.value = true;
};

const submitDispute = () => {
    router.post(route('auctions.openDispute', props.auction.id), {
        reason: disputeReason.value,
    }, {
        onSuccess: () => {
            showDisputeModal.value = false;
            disputeReason.value = '';
        }
    });
};
</script>

<template>
    <div class="bg-card border rounded-lg p-6 mt-6">
        <div class="flex items-center gap-3 mb-4">
            <component :is="getStatusIcon()" :class="['h-6 w-6', getStatusColor()]" />
            <div>
                <h3 class="text-lg font-semibold">
                    {{ t(`auction.postStatus.${auction.post_status}`) }}
                </h3>
                <p v-if="isSeller && winner" class="text-sm text-muted-foreground">
                    Winner: {{ winner.name }}
                </p>
                <p v-if="isWinner" class="text-sm text-muted-foreground">
                    {{ t('auction.youWon') }}!
                </p>
            </div>
        </div>

        <!-- Timeline -->
        <div class="mb-6 space-y-2">
            <div class="flex items-center gap-2">
                <div :class="[
                    'h-2 w-2 rounded-full',
                    auction.seller_contacted_at ? 'bg-green-500' : 'bg-gray-300'
                ]"></div>
                <span class="text-sm">{{ t('auction.postStatus.awaitingContact') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <div :class="[
                    'h-2 w-2 rounded-full',
                    auction.post_status === 'awaiting_payment' || auction.post_status === 'completed' ? 'bg-green-500' : 'bg-gray-300'
                ]"></div>
                <span class="text-sm">{{ t('auction.postStatus.awaitingPayment') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <div :class="[
                    'h-2 w-2 rounded-full',
                    auction.post_status === 'completed' ? 'bg-green-500' : 'bg-gray-300'
                ]"></div>
                <span class="text-sm">{{ t('auction.postStatus.completed') }}</span>
            </div>
        </div>

        <!-- Dispute Info -->
        <div v-if="auction.disputed_at" class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-md">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">
                {{ t('auction.postStatus.disputed') }}
            </p>
            <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                {{ auction.dispute_reason }}
            </p>
        </div>

        <!-- Actions for Seller -->
        <div v-if="isSeller && auction.post_status !== 'completed' && auction.post_status !== 'disputed'" class="flex flex-wrap gap-2">
            <button
                v-if="auction.post_status === 'awaiting_contact'"
                @click="contactWinner"
                class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
            >
                {{ t('auction.actions.contactWinner') }}
            </button>

            <button
                v-if="auction.post_status === 'awaiting_payment'"
                @click="markPaymentReceived"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            >
                {{ t('auction.actions.markPaymentReceived') }}
            </button>

            <button
                v-if="auction.post_status !== 'completed'"
                @click="markCompleted"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
                {{ t('auction.actions.markCompleted') }}
            </button>

            <button
                @click="openDisputeModal"
                class="px-4 py-2 border border-red-600 text-red-600 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20"
            >
                {{ t('auction.actions.openDispute') }}
            </button>
        </div>

        <!-- Actions for Winner -->
        <div v-if="isWinner && auction.post_status !== 'completed' && auction.post_status !== 'disputed'" class="flex gap-2">
            <button
                @click="markCompleted"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            >
                {{ t('auction.actions.markCompleted') }}
            </button>

            <button
                @click="openDisputeModal"
                class="px-4 py-2 border border-red-600 text-red-600 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20"
            >
                {{ t('auction.actions.openDispute') }}
            </button>
        </div>

        <!-- Dispute Modal -->
        <div v-if="showDisputeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-card p-6 rounded-lg max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">{{ t('auction.actions.openDispute') }}</h3>
                <textarea
                    v-model="disputeReason"
                    class="w-full border rounded-md p-2 mb-4"
                    rows="4"
                    placeholder="Describe the issue..."
                ></textarea>
                <div class="flex gap-2 justify-end">
                    <button
                        @click="showDisputeModal = false"
                        class="px-4 py-2 border rounded-md hover:bg-muted"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitDispute"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                    >
                        Submit Dispute
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
