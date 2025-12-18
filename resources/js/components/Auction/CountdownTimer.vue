<script setup lang="ts">
import { useCountdown } from '@/composables/useCountdown';

const props = defineProps<{
    date: string | Date | null;
    label?: string;
}>();

const { formattedTime, isExpired } = useCountdown(props.date);
</script>

<template>
    <div class="flex items-center gap-1">
        <span v-if="label" class="mr-1 opacity-70">{{ label }}:</span>
        <span :class="isExpired ? 'text-muted-foreground' : 'text-red-500 font-mono font-bold'">
            {{ formattedTime }}
        </span>
    </div>
</template>
