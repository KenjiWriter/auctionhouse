<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Bell } from 'lucide-vue-next';
import { route } from 'ziggy-js';

const { t } = useI18n();

const props = defineProps<{
    notifications: {
        data: any[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}>();

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
    const link = getNotificationLink(notification);
    if (link) {
        router.visit(link);
    }
};

const markAllAsRead = () => {
    router.post(route('notifications.read-all'), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('notifications.title')" />

    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <Bell class="h-6 w-6 text-primary" />
                    {{ t('notifications.title') }}
                </h1>
                <button
                    @click="markAllAsRead"
                    class="text-sm text-primary hover:underline"
                >
                    {{ t('notifications.markAllRead') }}
                </button>
            </div>

            <div v-if="notifications.data.length === 0" class="text-center py-16 bg-muted/30 rounded-lg">
                <Bell class="h-12 w-12 text-muted-foreground mx-auto mb-3 opacity-50" />
                <h3 class="text-lg font-medium text-foreground">{{ t('notifications.empty') }}</h3>
            </div>

            <div v-else class="space-y-2">
                <button
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    @click="handleNotificationClick(notification)"
                    class="w-full px-4 py-3 bg-card border rounded-lg hover:shadow-md transition-all text-left"
                    :class="{ 'border-l-4 border-l-primary': !notification.last_seen_at }"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="font-medium text-sm">{{ getNotificationTitle(notification) }}</div>
                            <div class="text-xs text-muted-foreground mt-1">{{ getNotificationBody(notification) }}</div>
                            <div class="text-xs text-muted-foreground mt-2">
                                {{ new Date(notification.created_at).toLocaleString() }}
                            </div>
                        </div>
                        <div v-if="notification.unread_count > 1" class="ml-4">
                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-primary rounded-full">
                                {{ notification.unread_count }}
                            </span>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Pagination -->
            <div v-if="notifications.links.length > 3" class="mt-6 flex justify-center gap-2">
                <Link
                    v-for="(link, index) in notifications.links"
                    :key="index"
                    :href="link.url || '#'"
                    :class="[
                        'px-3 py-2 rounded-md text-sm',
                        link.active ? 'bg-primary text-primary-foreground' : 'bg-card border hover:bg-muted',
                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
