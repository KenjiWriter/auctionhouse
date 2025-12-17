<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    email: '',
});

const status = ref<string | null>(null);

const submit = () => {
    form.post('/login/magic', {
        onSuccess: () => {
             status.value = 'We have emailed you a magic link!';
             form.reset();
        },
    });
};
</script>

<template>
    <Head :title="t('auth.login')" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-6 dark:bg-gray-900">
        <div class="w-full max-w-md overflow-hidden bg-white p-8 shadow-md rounded-lg dark:bg-gray-800">
            <h2 class="mb-6 text-2xl font-bold text-center text-gray-900 dark:text-white">
                {{ t('auth.login') }}
            </h2>

            <div v-if="status" class="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
                {{ status }}
            </div>

            <form @submit.prevent="submit">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.email') }}
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        :placeholder="t('auth.email_placeholder')"
                    />
                    <div v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                        {{ form.errors.email }}
                    </div>
                </div>

                <div class="flex items-center justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-white bg-primary rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
                    >
                        {{ t('auth.send_magic_link') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
