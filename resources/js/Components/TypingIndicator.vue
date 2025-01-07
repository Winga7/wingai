<script setup>
defineProps({
    isTyping: {
        type: Boolean,
        default: false
    }
});
</script>

<template>
    <div v-if="isTyping" class="flex items-center space-x-2 text-gray-400 dark:text-gray-500">
        <div class="flex space-x-1">
            <div class="w-2 h-2 rounded-full bg-current animate-bounce" style="animation-delay: 0ms"></div>
            <div class="w-2 h-2 rounded-full bg-current animate-bounce" style="animation-delay: 150ms"></div>
            <div class="w-2 h-2 rounded-full bg-current animate-bounce" style="animation-delay: 300ms"></div>
        </div>
        <span class="text-sm">Assistant en train d'écrire...</span>
    </div>
</template>
