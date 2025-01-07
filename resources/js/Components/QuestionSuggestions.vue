<script setup>
const suggestions = [
    "Explique-moi comment fonctionne Laravel?",
    "Quelles sont les bonnes pratiques en Vue.js?",
    "Comment implémenter l'authentification dans une API?",
    "Peux-tu m'expliquer les design patterns?",
];

const emit = defineEmits(['select']);

const selectSuggestion = (suggestion) => {
    emit('select', suggestion);
};
</script>

<template>
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
            Questions suggérées
        </h3>
        <div class="space-y-2">
            <button
                v-for="suggestion in suggestions"
                :key="suggestion"
                @click="selectSuggestion(suggestion)"
                class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
            >
                {{ suggestion }}
            </button>
        </div>
    </div>
</template>
