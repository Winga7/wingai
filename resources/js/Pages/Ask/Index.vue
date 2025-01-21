<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ChatHistory from '@/Components/ChatHistory.vue';
import CharacterCount from '@/Components/CharacterCount.vue';
import MarkdownRenderer from '@/Components/MarkdownRenderer.vue';

const props = defineProps({
    conversations: Array,
    currentConversation: Object,
    messages: Array,
    models: Array,
    selectedModel: String
});

const messages = ref(props.messages || []);

const form = useForm({
    message: '',
    conversation_id: props.currentConversation?.id,
    model: props.currentConversation?.model || props.selectedModel || 'mistralai/mistral-7b-instruct'
});

const handleSelectConversation = (conversation) => {
    router.get(route('ask.index', { conversation_id: conversation.id }), {
        preserveState: true,
        preserveScroll: true,
        only: ['messages', 'currentConversation']
    });
    messages.value = conversation.messages || [];
    form.conversation_id = conversation.id;
};

const handleSubmit = async () => {
    form.post(route('ask.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            if (response.props.flash.message) {
                messages.value.push({
                    role: 'user',
                    content: form.message
                });
                messages.value.push({
                    role: 'assistant',
                    content: response.props.flash.message
                });
                form.message = '';
            }
        }
    });
};

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSubmit();
    }
};

const handleModelChange = (event) => {
    form.model = event.target.value;
};

const markdownToHtml = (content) => {
    return marked(content, { breaks: true });
};
</script>

<template>
    <AppLayout title="Chat avec Kon-chan">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chat IA
            </h2>
        </template>

        <div class="flex h-[calc(100vh-64px)]">
            <!-- Sidebar -->
            <div class="hidden lg:block w-64 border-r border-gray-200 dark:border-gray-700">
                <ChatHistory
                    :conversations="conversations"
                    :current-conversation="currentConversation"
                    @select-conversation="handleSelectConversation"
                />
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Modèle
                    </label>
                    <select
                        v-model="form.model"
                        @change="handleModelChange"
                        class="mt-1 block w-full max-w-xs rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option v-for="model in models" :key="model.id" :value="model.id">
                            {{ model.name }}
                        </option>
                    </select>
                </div>

                <!-- Messages Container -->
                <div class="flex-1 overflow-y-auto p-4 space-y-6">
                    <div v-for="(message, index) in messages"
                         :key="index"
                         :class="[
                             message.role === 'user'
                                 ? 'bg-blue-50 dark:bg-blue-900/20 ml-auto max-w-[80%]'
                                 : 'bg-gray-50 dark:bg-gray-900/50 mr-auto max-w-[80%]',
                             'p-4 rounded-lg shadow-sm'
                         ]"
                    >
                        <div class="flex items-start gap-4">
                            <div class="shrink-0">
                                <div :class="[
                                    message.role === 'user'
                                        ? 'bg-blue-100 dark:bg-blue-800'
                                        : 'bg-purple-100 dark:bg-purple-800',
                                    'p-2 rounded-full'
                                ]">
                                    <span class="text-sm font-medium">
                                        {{ message.role === 'user' ? 'Vous' : 'Kon-chan' }}
                                    </span>
                                </div>
                            </div>
                            <MarkdownRenderer :content="message.content" />
                        </div>
                    </div>
                </div>

                <!-- Input Form -->
                <div class="border-t border-gray-200 dark:border-gray-700 p-4 bg-white dark:bg-gray-800">
                    <form @submit.prevent="handleSubmit" class="max-w-4xl mx-auto">
                        <div class="flex flex-col space-y-4">
                            <textarea
                                v-model="form.message"
                                @keydown="handleKeydown"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                placeholder="Posez votre question... (Entrée pour envoyer, Shift+Entrée pour sauter une ligne)"
                            ></textarea>
                            <div class="flex justify-between items-center">
                                <CharacterCount :text="form.message" :max="4000" />
                                <PrimaryButton type="submit" :disabled="form.processing">
                                    Envoyer
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
