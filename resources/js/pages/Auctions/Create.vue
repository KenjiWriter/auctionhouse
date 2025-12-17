<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref } from 'vue';

const { t } = useI18n();

const props = defineProps<{
    categories: Array<{ id: number; name: string }>;
    relistData?: {
        title: string;
        category_id: number;
        description: string;
        starting_price: number;
        buy_now_price: number | null;
    };
}>();

const step = ref(1);

const defaultValues = {
    title: props.relistData?.title ?? '',
    category_id: props.relistData?.category_id ?? '',
    description: props.relistData?.description ?? '',
    starting_price: props.relistData?.starting_price ?? '',
    buy_now_price: props.relistData?.buy_now_price ?? '',
};

const form = useForm({
    title: String(defaultValues.title),
    category_id: String(defaultValues.category_id),
    description: String(defaultValues.description),
    starting_price: String(defaultValues.starting_price),
    buy_now_price: String(defaultValues.buy_now_price),
    starts_at: '',
    ends_at: '',
    images: [] as File[],
});

const imagePreviews = ref<string[]>([]);

const draggedIndex = ref<number | null>(null);

const handleImageUpload = (event: Event) => {
    const files = (event.target as HTMLInputElement).files;
    if (files) {
        for (let i = 0; i < files.length; i++) {
            form.images.push(files[i]);
            imagePreviews.value.push(URL.createObjectURL(files[i]));
        }
    }
};

const onDragStart = (index: number) => {
    draggedIndex.value = index;
};

const onDrop = (dropIndex: number) => {
    if (draggedIndex.value === null || draggedIndex.value === dropIndex) return;

    // Swap images in form.images
    const tempFile = form.images[draggedIndex.value];
    form.images.splice(draggedIndex.value, 1);
    form.images.splice(dropIndex, 0, tempFile);

    // Swap previews
    const tempPreview = imagePreviews.value[draggedIndex.value];
    imagePreviews.value.splice(draggedIndex.value, 1);
    imagePreviews.value.splice(dropIndex, 0, tempPreview);

    draggedIndex.value = null;
};

const nextStep = () => {
    if (step.value < 4) step.value++;
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submit = () => {
    form.post(route('auctions.store'));
};
</script>

<template>
    <Head :title="t('nav.add_auction')" />

    <AppLayout>
        <div class="max-w-3xl mx-auto bg-white p-6 rounded-lg shadow dark:bg-gray-800">
            <h1 class="text-2xl font-bold mb-6 text-foreground">{{ t('nav.add_auction') }}</h1>

            <!-- Steps Indicator -->
            <div class="flex items-center justify-between mb-8 text-sm">
                <span :class="{'text-primary font-bold': step >= 1, 'text-muted-foreground': step < 1}">1. Images</span>
                <div class="flex-1 h-px bg-border mx-2"></div>
                <span :class="{'text-primary font-bold': step >= 2, 'text-muted-foreground': step < 2}">2. Details</span>
                <div class="flex-1 h-px bg-border mx-2"></div>
                 <span :class="{'text-primary font-bold': step >= 3, 'text-muted-foreground': step < 3}">3. Pricing</span>
                 <div class="flex-1 h-px bg-border mx-2"></div>
                <span :class="{'text-primary font-bold': step >= 4, 'text-muted-foreground': step < 4}">4. Review</span>
            </div>

            <form @submit.prevent="submit">
                <!-- Step 1: Images -->
                <div v-if="step === 1" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground">Upload Images</label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed border-gray-300 rounded-md dark:border-gray-600">
                             <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="file-upload" class="relative cursor-pointer bg-white rounded-md font-medium text-primary hover:text-primary/90 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary dark:bg-gray-800">
                                        <span>Upload a file</span>
                                        <input id="file-upload" name="file-upload" type="file" class="sr-only" multiple @change="handleImageUpload">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500">PNG, JPG, GIF up to 2MB</p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-4">
                            <div 
                                v-for="(preview, index) in imagePreviews" 
                                :key="index" 
                                class="relative aspect-square rounded overflow-hidden border cursor-move transition-all duration-200"
                                :class="{'opacity-50 ring-2 ring-primary scale-95': draggedIndex === index}"
                                draggable="true"
                                @dragstart="onDragStart(index)"
                                @dragover.prevent
                                @drop="onDrop(index)"
                            >
                                <img :src="preview" class="object-cover w-full h-full pointer-events-none" />
                                <button type="button" @click.prevent="form.images.splice(index, 1); imagePreviews.splice(index, 1)" class="absolute top-1 right-1 bg-black/50 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 hover:bg-red-500 transition-opacity">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Details -->
                <div v-if="step === 2" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-foreground">Title</label>
                        <input v-model="form.title" type="text" class="w-full mt-1 rounded-md border-border bg-background" required />
                        <div v-if="form.errors.title" class="text-red-500 text-xs">{{ form.errors.title }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-foreground">Category</label>
                        <select v-model="form.category_id" class="w-full mt-1 rounded-md border-border bg-background" required>
                            <option value="" disabled>Select a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-500 text-xs">{{ form.errors.category_id }}</div>
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-foreground">Description</label>
                        <textarea v-model="form.description" rows="4" class="w-full mt-1 rounded-md border-border bg-background" required></textarea>
                         <div v-if="form.errors.description" class="text-red-500 text-xs">{{ form.errors.description }}</div>
                    </div>
                </div>

                <!-- Step 3: Pricing -->
                <div v-if="step === 3" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground">Starting Price</label>
                            <input v-model="form.starting_price" type="number" step="0.01" class="w-full mt-1 rounded-md border-border bg-background" required />
                             <div v-if="form.errors.starting_price" class="text-red-500 text-xs">{{ form.errors.starting_price }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground">Buy Now Price (Optional)</label>
                            <input v-model="form.buy_now_price" type="number" step="0.01" class="w-full mt-1 rounded-md border-border bg-background" />
                             <div v-if="form.errors.buy_now_price" class="text-red-500 text-xs">{{ form.errors.buy_now_price }}</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-foreground">Starts At</label>
                            <input v-model="form.starts_at" type="datetime-local" class="w-full mt-1 rounded-md border-border bg-background" />
                             <div v-if="form.errors.starts_at" class="text-red-500 text-xs">{{ form.errors.starts_at }}</div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-foreground">Ends At</label>
                            <input v-model="form.ends_at" type="datetime-local" class="w-full mt-1 rounded-md border-border bg-background" required />
                             <div v-if="form.errors.ends_at" class="text-red-500 text-xs">{{ form.errors.ends_at }}</div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review -->
                <div v-if="step === 4" class="space-y-4">
                    <h3 class="text-lg font-medium">Review Details</h3>
                     <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-2">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Title</dt>
                            <dd class="mt-1 text-sm text-foreground">{{ form.title }}</dd>
                        </div>
                         <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Starting Price</dt>
                            <dd class="mt-1 text-sm text-foreground">${{ form.starting_price }}</dd>
                        </div>
                    </dl>
                    <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-900">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            By clicking Publish, you agree to our terms and conditions. The auction will start at the specified time.
                        </p>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="mt-8 flex justify-between">
                    <button
                        v-if="step > 1"
                        type="button"
                        @click="prevStep"
                        class="px-4 py-2 border border-border rounded-md text-foreground hover:bg-muted"
                    >
                        Previous
                    </button>
                    <div v-else></div>

                    <button
                        v-if="step < 4"
                        type="button"
                        @click="nextStep"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90"
                    >
                        Next
                    </button>
                    <button
                        v-if="step === 4"
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 disabled:opacity-50"
                    >
                        Publish Auction
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
