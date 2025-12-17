<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { AppPageProps } from '@/types';

const page = usePage<AppPageProps>();
const currentUser = computed(() => page.props.auth.user);

const props = defineProps<{
    conversation: {
        id: number;
        auction_id: number;
        buyer_id: number;
        seller_id: number;
        auction: { id: number; title: string; current_price: number; images: Array<{ path: string }> };
        buyer: { id: number; name: string };
        seller: { id: number; name: string };
        messages: Array<{ id: number; content: string; user_id: number; created_at: string; user: { name: string } }>;
    };
}>();

const messages = ref([...props.conversation.messages]);
const messagesContainer = ref<HTMLElement | null>(null);

const otherParticipant = computed(() => {
    if (props.conversation.buyer_id === currentUser.value.id) {
        return props.conversation.seller;
    }
    return props.conversation.buyer;
});

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

const form = useForm({
    content: '',
});

const sendMessage = () => {
    if (!form.content.trim()) return;

    form.post(route('conversations.message', props.conversation.id), {
        onSuccess: () => {
            form.reset();
            // Optimistic update handled by Echo, or simpler: 
            // Inertia reload gives us the message, but real-time is smoother
        },
        preserveScroll: true,
    });
};

onMounted(() => {
    scrollToBottom();

    // @ts-ignore
    window.Echo.private(`conversations.${props.conversation.id}`)
        .listen('MessageSent', (e: any) => {
            messages.value.push({
                id: e.message.id,
                content: e.message.content,
                user_id: e.message.user_id,
                created_at: e.message.created_at,
                user: e.message.user // Ensure this is loaded in Event
            });
            scrollToBottom();
        });
});

onUnmounted(() => {
    // @ts-ignore
    window.Echo.leave(`conversations.${props.conversation.id}`);
});
</script>

<template>
    <Head :title="`Chat with ${otherParticipant.name}`" />

    <AppLayout>
        <div class="h-[calc(100vh-12rem)] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4 border-b pb-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('conversations.index')" class="text-muted-foreground hover:text-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold">{{ otherParticipant.name }}</h1>
                        <Link :href="route('auctions.show', conversation.auction.id)" class="text-sm text-primary hover:underline">
                            re: {{ conversation.auction.title }}
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div ref="messagesContainer" class="flex-1 overflow-y-auto space-y-4 p-4 bg-muted/20 rounded-lg">
                <div 
                    v-for="message in messages" 
                    :key="message.id" 
                    class="flex"
                    :class="message.user_id === currentUser.id ? 'justify-end' : 'justify-start'"
                >
                    <div 
                        class="max-w-[75%] rounded-lg p-3"
                        :class="message.user_id === currentUser.id ? 'bg-primary text-primary-foreground rounded-br-none' : 'bg-card border rounded-bl-none'"
                    >
                        <p class="text-sm whitespace-pre-wrap">{{ message.content }}</p>
                        <p class="text-[10px] opacity-70 mt-1 text-right">
                            {{ new Date(message.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                        </p>
                    </div>
                </div>
                 <div v-if="messages.length === 0" class="text-center text-muted-foreground py-10">
                    No messages yet. Say hello!
                </div>
            </div>

            <!-- Input Area -->
            <div class="mt-4 pt-4 border-t">
                <form @submit.prevent="sendMessage" class="flex gap-2">
                    <input 
                        v-model="form.content" 
                        type="text" 
                        placeholder="Type a message..." 
                        class="flex-1 rounded-md border-border bg-background"
                        :disabled="form.processing"
                    />
                    <button 
                        type="submit" 
                        :disabled="form.processing || !form.content.trim()"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:opacity-50"
                    >
                        Send
                    </button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
