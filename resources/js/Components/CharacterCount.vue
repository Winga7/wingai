<script setup>
import { computed } from 'vue';

const props = defineProps({
    text: {
        type: String,
        required: true
    },
    maxLength: {
        type: Number,
        default: 4000
    }
});

const remainingChars = computed(() => props.maxLength - props.text.length);
const isNearLimit = computed(() => remainingChars.value < props.maxLength * 0.1);
</script>

<template>
    <div class="text-xs text-right mt-1" :class="{
        'text-gray-500 dark:text-gray-400': !isNearLimit,
        'text-orange-500 dark:text-orange-400': isNearLimit && remainingChars > 0,
        'text-red-500 dark:text-red-400': remainingChars <= 0
    }">
        {{ remainingChars }} caractères restants
    </div>
</template>
