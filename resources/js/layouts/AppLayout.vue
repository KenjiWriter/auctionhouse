<script setup lang="ts">
import Sidebar from '@/components/Navigation/Sidebar.vue';
import BottomNav from '@/components/Navigation/BottomNav.vue';

interface Props {
    breadcrumbs?: any[];
}

defineProps<Props>();
</script>

<template>
    <div class="flex h-screen w-full bg-background text-foreground">
        <!-- Desktop Sidebar -->
        <Sidebar />

        <div class="flex flex-1 flex-col overflow-hidden">
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 pb-20 lg:p-6 lg:pb-6">
                <slot />
            </main>

            <!-- Mobile Bottom Nav -->
            <BottomNav />
        </div>
    </div>
</template>
