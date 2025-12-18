<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Bell } from 'lucide-vue-next';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { route } from 'ziggy-js';
import axios from 'axios';

const { t } = useI18n();

const unreadCount = ref(0);
const notifications = ref<any[]>([]);
const isOpen = ref(false);

const fetchUnreadCount = async () => {
    try {
        const { data } = await axios.get(route('notifications.unread-count'));
        unreadCount.value = data.count;
    } catch (e) {
        console.error('Error fetching unread count:', e);
    }
};

const fetchLatestNotifications = async () => {
    try {
        const { data } = await axios.get(route('notifications.latest'));
        notifications.value = data;
    } catch (e) {
        console.error('Error fetching notifications:', e);
    }
};

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        await fetchLatestNotifications();
    }
};

const markAllAsRead = async () => {
    try {
        await router.post(route('notifications.read-all'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                unreadCount.value = 0;
                fetchLatestNotifications();
            }
        });
    } catch (e) {
        console.error('Error marking all as read:', e);
    }
};

const getNotificationTitle = (notification: any) => {
    return t(`notifications.types.${notification.type}.title`);
};

const getNotificationBody = (notification: any) => {
    const key = `notifications.types.${notification.type}.body`;
    return t(key, notification.data || {});
};

const getNotificationLink = (notification: any) => {
    if (notification.auction_id) {
        return route('auctions.show', notification.auction_id);
    }
    return null;
};

const handleNotificationClick = (notification: any) => {
    isOpen.value = false;
    const link = getNotificationLink(notification);
    if (link) {
        router.visit(link);
    }
};

onMounted(() => {
    fetchUnreadCount();
    
    // Listen for real-time notifications via Echo
    if ((window as any).Echo) {
        const userId = (window as any).Laravel?.user?.id;
        if (userId) {
            (window as any).Echo.private(`App.Models.User.${userId}`)
                .listen('NotificationCreated', (e: any) => {
                    console.log('New notification:', e);
                    fetchUnreadCount();
                    if (isOpen.value) {
                        fetchLatestNotifications();
                    }
                });
        }
    }
});
</script>

<template>
    <div class="relative">
        <button 
            @click="toggleDropdown"
            class="relative p-2 text-muted-foreground hover:text-foreground transition-colors"
            type="button"
        >
            <Bell class="h-6 w-6" />
            <span 
                v-if="unreadCount > 0" 
                class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown with fixed positioning -->
        <div 
            v-if="isOpen"
            class="fixed bottom-20 left-4 w-80 bg-card border rounded-lg shadow-lg z-[9999]"
        >
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="font-semibold">{{ t('notifications.title') }}</h3>
                <button 
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-xs text-primary hover:underline"
                >
                    {{ t('notifications.markAllRead') }}
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto">
                <div 
                    v-if="notifications.length === 0"
                    class="p-8 text-center text-muted-foreground"
                >
                    {{ t('notifications.empty') }}
                </div>
                
                <div v-else>
                    <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        @click="handleNotificationClick(notification)"
                        class="w-full px-4 py-3 hover:bg-muted/50 transition-colors border-b last:border-b-0 text-left"
                        :class="{ 'bg-muted/20': !notification.last_seen_at }"
                    >
                        <div class="font-medium text-sm">{{ getNotificationTitle(notification) }}</div>
                        <div class="text-xs text-muted-foreground mt-1">{{ getNotificationBody(notification) }}</div>
                        <div v-if="notification.unread_count > 1" class="text-xs text-primary mt-1">
                            +{{ notification.unread_count - 1 }} more
                        </div>
                    </button>
                </div>
            </div>

            <div class="p-3 border-t text-center">
                <Link 
                    :href="route('notifications.index')"
                    class="text-sm text-primary hover:underline"
                    @click="isOpen = false"
                >
                    {{ t('notifications.viewAll') }}
                </Link>
            </div>
        </div>
    </div>
</template>
