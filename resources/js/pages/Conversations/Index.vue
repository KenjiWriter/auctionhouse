<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { AppPageProps } from '@/types';
import { route } from 'ziggy-js';

const page = usePage<AppPageProps>();
const currentUser = computed(() => page.props.auth.user);

const props = defineProps<{
    conversations: Array<{
        id: number;
        auction_id: number;
        buyer_id: number;
        seller_id: number;
        unread_count: number;
        updated_at: string;
        auction: { title: string; images: Array<{ path: string }> };
        last_message: { content: string; created_at: string; user_id: number };
        buyer: { name: string };
        seller: { name: string };
    }>;
}>();

const getOtherParticipant = (conversation: any) => {
    if (conversation.buyer_id === currentUser.value.id) {
        return conversation.seller.name;
    }
    return conversation.buyer.name;
};
</script>

<template>
    <Head title="Messages" />

    <AppLayout>
        <h1 class="text-2xl font-bold mb-6">Messages</h1>

        <div class="space-y-4">
            <Link 
                v-for="conversation in conversations" 
                :key="conversation.id" 
                :href="route('conversations.show', conversation.id)"
                class="block bg-card border rounded-lg p-4 shadow-sm hover:bg-muted/50 transition-colors"
                :class="{ 'border-primary ring-1 ring-primary/20': conversation.unread_count > 0 }"
            >
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-muted rounded-md overflow-hidden flex-shrink-0">
                         <img v-if="conversation.auction.images?.[0]" :src="`/storage/${conversation.auction.images[0].path}`" class="w-full h-full object-cover" />
                         <div v-else class="w-full h-full flex items-center justify-center text-xs text-muted-foreground">IMG</div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <h3 class="font-bold truncate">{{ conversation.auction.title }}</h3>
                            <span v-if="conversation.last_message" class="text-xs text-muted-foreground whitespace-nowrap ml-2">
                                {{ new Date(conversation.last_message.created_at).toLocaleDateString() }}
                            </span>
                        </div>
                        <p class="text-sm text-foreground/80 font-medium flex items-center gap-2">
                            {{ getOtherParticipant(conversation) }}
                            <span v-if="conversation.unread_count > 0" class="w-2 h-2 rounded-full bg-primary flex-shrink-0"></span>
                        </p>
                        <p v-if="conversation.last_message" class="text-sm text-muted-foreground truncate">
                            <span v-if="conversation.last_message.user_id === currentUser.id">You: </span>
                            {{ conversation.last_message.content }}
                        </p>
                        <p v-else class="text-sm text-muted-foreground italic">No messages yet</p>
                    </div>
                </div>
            </Link>

            <div v-if="conversations.length === 0" class="text-center py-12 text-muted-foreground">
                You have no active conversations.
            </div>
        </div>
    </AppLayout>
</template>
