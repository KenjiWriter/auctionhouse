<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AvatarPicker from '@/components/Profile/AvatarPicker.vue';
import { Loader2 } from 'lucide-vue-next';

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
    avatar: null as File | null,
    avatar_preset: null as string | null,
});

const submit = () => {
    form.post('/register/complete', {
         forceFormData: true,
    });
};
</script>

<template>
    <Head :title="t('profile.complete.title')" />
    
    <div class="min-h-screen bg-muted/30 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto space-y-8">
            
            <!-- Header -->
            <div class="text-center">
                <h2 class="text-3xl font-extrabold text-foreground">
                    {{ t('profile.complete.title') }}
                </h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ t('profile.complete.subtitle') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Section 1: Basic Info & Avatar -->
                <div class="bg-card shadow rounded-lg p-6 space-y-6 border border-border">
                    <AvatarPicker
                        v-model="form.avatar"
                        v-model:preset="form.avatar_preset"
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
                <div class="bg-card shadow rounded-lg p-6 space-y-6 border border-border">
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

                <!-- Section 3: Security -->
                <div class="bg-card shadow rounded-lg p-6 space-y-6 border border-border">
                    <h3 class="text-lg font-medium leading-6 text-foreground border-b pb-2">
                         Security
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                {{ t('auth.password') }}
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                             <div v-if="form.errors.password" class="mt-1 text-sm text-destructive">{{ form.errors.password }}</div>
                        </div>
                        
                         <!-- Password Confirmation -->
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1">
                                {{ t('auth.password_confirmation') }}
                            </label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end pt-4">
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
</template>
