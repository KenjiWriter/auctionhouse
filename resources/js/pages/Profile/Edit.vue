<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AvatarPicker from '@/components/Profile/AvatarPicker.vue';
import { Loader2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { route } from 'ziggy-js';

const { t } = useI18n();

interface UserProps {
    id: number;
    name: string;
    phone: string;
    address: string;
    city: string;
    postal_code: string;
    country: string;
    avatar_path?: string;
    avatar_preset?: string;
}

const props = defineProps<{
    user: UserProps;
}>();

const form = useForm({
    name: props.user.name || '',
    phone: props.user.phone || '',
    address: props.user.address || '',
    city: props.user.city || '',
    postal_code: props.user.postal_code || '',
    country: props.user.country || '',
    avatar: null as File | null,
    avatar_preset: props.user.avatar_preset || (props.user.avatar_path ? null : 'default'),
});

// Better preset initialization logic
if (props.user.avatar_preset) {
    form.avatar_preset = props.user.avatar_preset;
} else {
    // If no preset but currently active preset on user (handled above), or no avatar at all.
    // If user has avatar_path, preset is null.
    // If user has neither, preset is default.
    // The initialization above covers most cases, but let's be explicit.
    if (!props.user.avatar_path && !props.user.avatar_preset) {
        form.avatar_preset = 'default';
    }
}

const submit = () => {
    form.post(route('profile.me.update'), {
         forceFormData: true,
         preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="t('profile.edit.title')" />
    
    <AppLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ t('profile.edit.title') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    
                    <!-- Section 1: Basic Info & Avatar -->
                    <div class="bg-card shadow sm:rounded-lg p-6 space-y-6 border border-border">
                        <AvatarPicker
                            v-model="form.avatar"
                            v-model:preset="form.avatar_preset"
                            :current-avatar="props.user.avatar_path"
                            :error="form.errors.avatar || form.errors.avatar_preset"
                        />

                        <div class="space-y-4">
                            <h3 class="text-lg font-medium leading-6 text-foreground border-b pb-2">
                                Basics
                            </h3>
                             <!-- Name -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.name') }}
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</div>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.phone') }}
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="tel"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                 <div v-if="form.errors.phone" class="mt-1 text-sm text-destructive">{{ form.errors.phone }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Address -->
                    <div class="bg-card shadow sm:rounded-lg p-6 space-y-6 border border-border">
                        <h3 class="text-lg font-medium leading-6 text-foreground border-b pb-2">
                             {{ t('auth.address') }}
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Address (Street) -->
                             <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.address') }}
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                 <div v-if="form.errors.address" class="mt-1 text-sm text-destructive">{{ form.errors.address }}</div>
                            </div>
                            
                            <!-- City -->
                             <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.city') }}
                                </label>
                                <input
                                    v-model="form.city"
                                    type="text"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                 <div v-if="form.errors.city" class="mt-1 text-sm text-destructive">{{ form.errors.city }}</div>
                            </div>

                            <!-- Postal Code -->
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.postal_code') }}
                                </label>
                                <input
                                    v-model="form.postal_code"
                                    type="text"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                 <div v-if="form.errors.postal_code" class="mt-1 text-sm text-destructive">{{ form.errors.postal_code }}</div>
                            </div>

                             <!-- Country -->
                             <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-foreground mb-1">
                                    {{ t('auth.country') }}
                                </label>
                                <input
                                    v-model="form.country"
                                    type="text"
                                    required
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                 <div v-if="form.errors.country" class="mt-1 text-sm text-destructive">{{ form.errors.country }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-end">
                         <button
                            type="submit"
                            :disabled="form.processing"
                             class="flex items-center gap-2 px-6 py-3 text-white bg-primary rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 font-bold shadow-lg transition-transform active:scale-95"
                        >
                            <Loader2 v-if="form.processing" class="animate-spin" :size="20" />
                            <span v-if="form.processing">{{ t('profile.saving') }}</span>
                            <span v-else>{{ t('profile.save') }}</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </AppLayout>
</template>
