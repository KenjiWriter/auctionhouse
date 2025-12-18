import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

export function useCountdown(targetDate: string | Date | null) {
    const { t } = useI18n();
    const timeLeft = ref<{ days: number; hours: number; minutes: number; seconds: number } | null>(null);
    const isExpired = ref(false);
    let timer: number | null = null;

    const calculateTimeLeft = () => {
        if (!targetDate) return;

        const target = new Date(targetDate).getTime();
        const now = new Date().getTime();
        const difference = target - now;

        if (difference <= 0) {
            isExpired.value = true;
            timeLeft.value = { days: 0, hours: 0, minutes: 0, seconds: 0 };
            if (timer) clearInterval(timer);
            return;
        }

        timeLeft.value = {
            days: Math.floor(difference / (1000 * 60 * 60 * 24)),
            hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((difference / 1000 / 60) % 60),
            seconds: Math.floor((difference / 1000) % 60),
        };
    };

    const formattedTime = computed(() => {
        if (!timeLeft.value) return '';
        if (isExpired.value) return t('auction.ended');

        const parts = [];
        if (timeLeft.value.days > 0) parts.push(`${timeLeft.value.days}d`);
        if (timeLeft.value.hours > 0 || timeLeft.value.days > 0) parts.push(`${timeLeft.value.hours}h`);
        parts.push(`${timeLeft.value.minutes}m`);
        parts.push(`${timeLeft.value.seconds}s`);

        return parts.join(' ');
    });

    onMounted(() => {
        calculateTimeLeft();
        timer = window.setInterval(calculateTimeLeft, 1000);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return {
        timeLeft,
        formattedTime,
        isExpired
    };
}
