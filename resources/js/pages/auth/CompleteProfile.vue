<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
    phone: '',
    address: '',
    city: '',
    postal_code: '',
    country: '',
});

const submit = () => {
    form.post('/register/complete');
};
</script>

<template>
    <Head :title="t('auth.complete_profile')" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-100 p-6 dark:bg-gray-900">
        <div class="w-full max-w-lg overflow-hidden bg-white p-8 shadow-md rounded-lg dark:bg-gray-800">
            <h2 class="mb-6 text-2xl font-bold text-center text-gray-900 dark:text-white">
                {{ t('auth.complete_profile') }}
            </h2>

            <form @submit.prevent="submit" class="grid grid-cols-1 gap-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.name') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                </div>

                 <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.password') }}
                    </label>
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                     <div v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</div>
                </div>
                
                 <!-- Password Confirmation -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.password_confirmation') }}
                    </label>
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.phone') }}
                    </label>
                    <input
                        v-model="form.phone"
                        type="tel"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                     <div v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</div>
                </div>

                <!-- Address -->
                 <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.address') }}
                    </label>
                    <input
                        v-model="form.address"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                     <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
                </div>

                <!-- City & Postal -->
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('auth.city') }}
                        </label>
                        <input
                            v-model="form.city"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                         <div v-if="form.errors.city" class="mt-1 text-sm text-red-600">{{ form.errors.city }}</div>
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ t('auth.postal_code') }}
                        </label>
                        <input
                            v-model="form.postal_code"
                            type="text"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                         <div v-if="form.errors.postal_code" class="mt-1 text-sm text-red-600">{{ form.errors.postal_code }}</div>
                    </div>
                </div>
                
                 <!-- Country -->
                 <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ t('auth.country') }}
                    </label>
                    <input
                        v-model="form.country"
                        type="text"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    />
                     <div v-if="form.errors.country" class="mt-1 text-sm text-red-600">{{ form.errors.country }}</div>
                </div>

                <div class="flex items-center justify-end mt-4">
                     <button
                        type="submit"
                        :disabled="form.processing"
                         class="px-4 py-2 text-white bg-primary rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
                    >
                        {{ t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
