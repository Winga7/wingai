<script setup>
import { ref } from 'vue';

const props = defineProps({
    messages: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['select']);

const selectedMessage = ref(null);

const selectMessage = (message) => {
    selectedMessage.value = message;
    emit('select', message);
};
</script>

<template>
    <div class="border-r border-gray-200 dark:border-gray-700">
        <div class="p-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Historique des conversations
            </h2>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <button
                v-for="message in messages"
                :key="message.id"
                @click="selectMessage(message)"
                class="w-full px-4 py-3 flex items-start hover:bg-gray-50 dark:hover:bg-gray-800"
                :class="{ 'bg-gray-50 dark:bg-gray-800': selectedMessage === message }"
            >
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ message.content.substring(0, 50) }}...
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ new Date(message.created_at).toLocaleDateString() }}
                    </p>
                </div>
            </button>
        </div>
    </div>
</template>
