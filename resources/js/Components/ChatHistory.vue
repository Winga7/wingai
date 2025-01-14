<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    conversations: {
        type: Array,
        required: true
    },
    currentConversation: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['select-conversation']);

const selectConversation = (conversation) => {
    emit('select-conversation', conversation);
};

const deleteConversation = (e, conversationId) => {
    e.stopPropagation(); // Empêche le déclenchement du click sur la conversation
    if (confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
        router.delete(route('conversations.destroy', conversationId), {
            preserveScroll: true,
            preserveState: true
        });
    }
};

const newConversation = () => {
    router.post(route('ask.store'), {
        message: 'Nouvelle conversation',
        model: 'gpt-3.5-turbo'
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    });
};
</script>

<template>
    <div class="fixed left-0 top-0 h-screen w-64 border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
        <div class="p-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Historique des conversations
            </h2>
            <button @click="newConversation" class="mt-4 w-full">
                <PrimaryButton class="w-full justify-center">
                    Nouvelle conversation
                </PrimaryButton>
            </button>
        </div>

        <div class="overflow-y-auto h-[calc(100vh-8rem)]">
            <div v-for="conversation in conversations"
                 :key="conversation.id"
                 :class="{'bg-gray-100 dark:bg-gray-800': currentConversation?.id === conversation.id}"
                 class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer group"
                 @click="selectConversation(conversation)">
                <div class="flex items-center justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="font-medium truncate max-w-[180px]" :title="conversation.title">
                            {{ conversation.title }}
                        </h3>
                        <p class="text-sm text-gray-500 truncate max-w-[180px]" :title="formatDate(conversation.created_at)">
                            {{ formatDate(conversation.created_at) }}
                        </p>
                    </div>
                    <button
                        @click="(e) => deleteConversation(e, conversation.id)"
                        class="p-2 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
                        title="Supprimer la conversation"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
