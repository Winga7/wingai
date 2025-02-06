<script setup>
import { onMounted, ref } from 'vue';

const messages = ref([]);

onMounted(() => {
    window.Echo.channel('test-channel')
        .listen('.test-event', (event) => {
            messages.value.push({
                message: event.message,
                timestamp: event.timestamp
            });
            console.log('Événement reçu:', event);
        });
});
</script>

<template>
    <div class="p-4">
        <h3 class="text-lg font-bold mb-4">Messages Broadcast</h3>
        <div v-if="messages.length === 0" class="text-gray-500">
            En attente de messages...
        </div>
        <div v-else class="space-y-2">
            <div v-for="(msg, index) in messages"
                 :key="index"
                 class="p-3 bg-gray-100 dark:bg-gray-800 rounded">
                <p>{{ msg.message }}</p>
                <small class="text-gray-500">{{ new Date(msg.timestamp).toLocaleString() }}</small>
            </div>
        </div>
    </div>
</template>
