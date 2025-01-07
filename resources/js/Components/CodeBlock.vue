<script setup>
import { ref } from 'vue';

const props = defineProps({
    code: {
        type: String,
        required: true
    },
    language: {
        type: String,
        default: ''
    }
});

const copied = ref(false);

const copyCode = async () => {
    try {
        await navigator.clipboard.writeText(props.code);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Erreur lors de la copie:', err);
    }
};
</script>

<template>
    <div class="relative group">
        <div class="absolute right-2 top-2">
            <button
                @click="copyCode"
                class="p-2 text-gray-400 hover:text-gray-300 transition-colors duration-200 bg-gray-800 rounded-md opacity-0 group-hover:opacity-100"
                :class="{ 'text-green-400': copied }"
            >
                <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
        </div>
        <pre class="rounded-lg bg-gray-900 p-4"><code :class="language">{{ code }}</code></pre>
    </div>
</template>
