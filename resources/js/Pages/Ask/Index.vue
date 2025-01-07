<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MarkdownRenderer from '@/Components/MarkdownRenderer.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import ChatHistory from '@/Components/ChatHistory.vue';
import QuestionSuggestions from '@/Components/QuestionSuggestions.vue';
import TypingIndicator from '@/Components/TypingIndicator.vue';
import CharacterCount from '@/Components/CharacterCount.vue';
import CodeBlock from '@/Components/CodeBlock.vue';
import CopyableText from '@/Components/CopyableText.vue';

const props = defineProps({
    models: Array,
    selectedModel: String,
});

const page = usePage();
const form = useForm({
    message: '',
    model: props.selectedModel
});

const response = ref('');
const error = ref('');
const isTyping = ref(false);
const MAX_MESSAGE_LENGTH = 4000;

watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.message) {
        response.value = newFlash.message;
    }
    if (newFlash?.error) {
        error.value = newFlash.error;
    }
}, { deep: true });

const submit = () => {
    if (form.processing || !form.message.trim() || form.message.length > MAX_MESSAGE_LENGTH) {
        return;
    }

    isTyping.value = true;
    form.post(route('ask.post'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message');
            isTyping.value = false;
        },
        onError: () => {
            isTyping.value = false;
        }
    });
};

const handleSuggestionSelect = (suggestion) => {
    form.message = suggestion;
};

const handleKeydown = (e) => {
    if (e.key === 'Enter') {
        if (!e.shiftKey) {
            e.preventDefault();
            if (!form.processing && form.message.trim()) {
                submit();
            }
        }
    }
};

const isSubmitDisabled = computed(() => {
    return form.processing ||
           !form.message.trim() ||
           form.message.length > MAX_MESSAGE_LENGTH;
});
</script>

<template>
    <AppLayout title="Chat IA">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Chat IA
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex gap-6">
                    <!-- Sidebar avec historique -->
                    <div class="hidden lg:block w-64">
                        <ChatHistory :messages="[]" @select="handleSuggestionSelect" />
                    </div>

                    <!-- Contenu principal -->
                    <div class="flex-1">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                            <!-- Suggestions de questions -->
                            <QuestionSuggestions
                                v-if="!response && !form.message"
                                @select="handleSuggestionSelect"
                            />

                            <form @submit.prevent="submit" class="space-y-4">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- Sélection du modèle -->
                                    <div>
                                        <InputLabel for="model" value="Modèle" />
                                        <select
                                            id="model"
                                            v-model="form.model"
                                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        >
                                            <option v-for="model in models" :key="model.id" :value="model.id">
                                                {{ model.name }}
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.model" class="mt-2" />
                                    </div>

                                    <!-- Message -->
                                    <div class="relative">
                                        <textarea
                                            id="message"
                                            v-model="form.message"
                                            rows="4"
                                            @keydown="handleKeydown"
                                            :disabled="form.processing"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:opacity-50"
                                            :placeholder="form.processing ? 'En attente de réponse...' : 'Posez votre question... (Entrée pour envoyer, Shift+Entrée pour sauter une ligne)'"
                                        ></textarea>
                                        <CharacterCount :text="form.message" :max-length="MAX_MESSAGE_LENGTH" />
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <TypingIndicator :is-typing="isTyping" />
                                        <PrimaryButton
                                            :disabled="isSubmitDisabled"
                                            :class="{ 'opacity-25': isSubmitDisabled }"
                                        >
                                            Envoyer
                                        </PrimaryButton>
                                    </div>
                                </div>
                            </form>

                            <!-- Affichage de la réponse -->
                            <div v-if="error" class="mt-6 p-4 bg-red-50 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg">
                                {{ error }}
                            </div>

                            <div v-if="response" class="mt-6 prose dark:prose-invert max-w-none">
                                <MarkdownRenderer :content="response" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
