<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Upload, X, User } from 'lucide-vue-next';

const props = defineProps<{
    modelValue: File | null;
    currentAvatar?: string | null;
    preset?: string | null;
    error?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'update:preset', preset: string | null): void;
}>();

const { t } = useI18n();

const mode = ref<'upload' | 'preset'>('upload');
const previewUrl = ref<string | null>(null);

// Presets definition
const presets = [
    'bg-gradient-to-br from-red-400 to-orange-500',
    'bg-gradient-to-br from-amber-400 to-yellow-500',
    'bg-gradient-to-br from-lime-400 to-green-500',
    'bg-gradient-to-br from-emerald-400 to-teal-500',
    'bg-gradient-to-br from-cyan-400 to-sky-500',
    'bg-gradient-to-br from-blue-400 to-indigo-500',
    'bg-gradient-to-br from-violet-400 to-purple-500',
    'bg-gradient-to-br from-fuchsia-400 to-pink-500',
    'bg-gradient-to-br from-rose-400 to-red-500',
    'bg-gradient-to-br from-slate-400 to-zinc-500',
];

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Simple client-side validation for immediate feedback
        if (file.size > 2 * 1024 * 1024) {
            alert(t('validation.avatarTooLarge'));
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            previewUrl.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
        
        emit('update:modelValue', file);
        emit('update:preset', null); // Clear preset if uploading
    }
};

const removeAvatar = () => {
    previewUrl.value = null;
    emit('update:modelValue', null);
    emit('update:preset', null);
};

const selectPreset = (presetClass: string) => {
    emit('update:preset', presetClass);
    emit('update:modelValue', null); // Clear file if selecting preset
    previewUrl.value = null;
};

// Initialize mode based on current state
if (props.preset) {
    mode.value = 'preset';
} else if (props.currentAvatar) {
    // If has actual image, default to upload tab
    mode.value = 'upload';
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-foreground">
                {{ t('profile.avatar.title') }}
            </label>
        </div>

        <div class="w-full">
            <!-- Tabs -->
            <div class="grid w-full grid-cols-2 p-1 mb-4 bg-muted rounded-lg">
                <button
                    type="button"
                    class="px-3 py-1.5 text-sm font-medium transition-all rounded-md"
                    :class="mode === 'upload' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'upload'"
                >
                    {{ t('profile.avatar.uploadTab') }}
                </button>
                <button
                    type="button"
                    class="px-3 py-1.5 text-sm font-medium transition-all rounded-md"
                    :class="mode === 'preset' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:text-foreground'"
                    @click="mode = 'preset'"
                >
                    {{ t('profile.avatar.chooseTab') }}
                </button>
            </div>

            <!-- Upload Mode -->
            <div v-if="mode === 'upload'" class="space-y-4">
                <div class="flex items-center gap-6">
                    <!-- Preview Circle -->
                    <div class="relative shrink-0 group">
                        <div 
                            class="h-24 w-24 rounded-full overflow-hidden border-2 border-border bg-muted flex items-center justify-center relative"
                        >
                            <img 
                                v-if="previewUrl" 
                                :src="previewUrl" 
                                class="h-full w-full object-cover"
                            />
                            <img 
                                v-else-if="currentAvatar && !preset" 
                                :src="`/storage/${currentAvatar}`" 
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="text-muted-foreground/30">
                                <User :size="48" />
                            </div>

                            <!-- Remove Button Overlay -->
                            <button
                                v-if="previewUrl || currentAvatar"
                                type="button"
                                @click="removeAvatar"
                                class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                            >
                                <X class="text-white" />
                            </button>
                        </div>
                    </div>

                    <!-- Upload Input -->
                    <div class="flex-1 space-y-2">
                        <div class="flex items-center gap-2">
                            <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors text-sm font-medium">
                                <Upload :size="16" />
                                {{ t('profile.avatar.uploadTab') }}
                                <input 
                                    type="file" 
                                    class="hidden" 
                                    accept="image/png, image/jpeg, image/webp"
                                    @change="handleFileChange"
                                >
                            </label>
                            <button 
                                v-if="previewUrl || currentAvatar"
                                type="button" 
                                @click="removeAvatar"
                                class="text-sm text-destructive hover:underline px-2"
                            >
                                {{ t('profile.avatar.remove') }}
                            </button>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('profile.avatar.uploadHint') }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Preset Mode -->
            <div v-else class="space-y-3">
                 <p class="text-sm text-muted-foreground mb-2">{{ t('profile.avatar.presetsLabel') }}</p>
                 <div class="grid grid-cols-5 md:grid-cols-8 gap-3">
                     <button
                        v-for="p in presets"
                        :key="p"
                        type="button"
                        @click="selectPreset(p)"
                        class="h-12 w-12 rounded-full cursor-pointer transition-all hover:scale-110 focus:ring-2 focus:ring-primary focus:ring-offset-2"
                        :class="[p, preset === p ? 'ring-2 ring-primary ring-offset-2 scale-110 shadow-md' : 'opacity-80 hover:opacity-100']"
                     >
                        <!-- Checkmark for selected -->
                         <div v-if="preset === p" class="h-full w-full flex items-center justify-center">
                             <div class="bg-white/30 rounded-full p-1">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                             </div>
                         </div>
                     </button>
                 </div>
            </div>
        </div>
        
        <p v-if="error" class="text-xs text-destructive mt-2">{{ error }}</p>
    </div>
</template>
