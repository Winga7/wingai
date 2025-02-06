<script setup>
import {
    ref,
    computed,
    onMounted,
    nextTick,
    watch,
    onBeforeUnmount,
} from "vue";
import { useForm, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import ChatHistory from "@/Components/ChatHistory.vue";
import CharacterCount from "@/Components/CharacterCount.vue";
import MarkdownRenderer from "@/Components/MarkdownRenderer.vue";
import SlashCommandAutocomplete from "@/Components/SlashCommandAutocomplete.vue";
import {
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronDownIcon,
} from "@heroicons/vue/24/outline";
import axios from "axios";

const props = defineProps({
    conversations: Array,
    currentConversation: Object,
    messages: Array,
    models: Array,
    selectedModel: String,
    personalization: Object,
});

// Ajout des refs nécessaires
const channelSubscription = ref(null);
const localMessages = ref(props.messages || []);

const form = useForm({
    message: "",
    conversation_id: props.currentConversation?.id,
    model: props.currentConversation?.model || props.selectedModel,
});

// Ajout des commandes disponibles
const availableCommands = ref([
    {
        command: "/help",
        description: "Affiche la liste des commandes disponibles",
    },
    {
        command: "/meteo",
        description: "Affiche la météo pour une ville",
    },
    {
        command: "/resume",
        description: "Résume un texte",
    },
    // Les commandes personnalisées seront ajoutées dynamiquement
]);

// Charger les commandes personnalisées
onMounted(() => {
    if (props.personalization?.slash_commands) {
        availableCommands.value = [
            ...availableCommands.value,
            ...props.personalization.slash_commands,
        ];
    }

    // Ajout de la logique de connexion au canal
    if (props.currentConversation?.id) {
        const channel = `chat.${props.currentConversation.id}`;
        console.log("🔌 Tentative de connexion au canal:", channel);

        const subscription = window.Echo.private(channel)
            .subscribed(() => {
                console.log("✅ Connecté avec succès au canal:", channel);
            })
            .error((error) => {
                console.error("❌ Erreur de connexion au canal:", error);
            })
            .listen(".message.streamed", (event) => {
                if (event.title) {
                    // Mise à jour du titre via Inertia
                    router.reload({
                        only: ["conversations"],
                        data: {
                            title: event.title,
                        },
                    });

                    // Mise à jour locale du titre
                    if (props.currentConversation) {
                        props.currentConversation.title = event.title;
                    }
                }

                console.log("📨 Message reçu:", {
                    content: event.content,
                    contentLength: event.content?.length,
                    isComplete: event.isComplete,
                    error: event.error,
                });

                // Ajout de vérification du contenu
                if (!event.content && !event.isComplete) {
                    console.warn("⚠️ Message reçu sans contenu");
                    return;
                }

                const lastMessage =
                    localMessages.value[localMessages.value.length - 1];

                if (!lastMessage || lastMessage.role !== "assistant") {
                    console.log(
                        "⚠️ Aucun message assistant ciblé pour concaténer"
                    );
                    return;
                }

                if (event.error) {
                    console.error("❌ Erreur reçue:", event.error);
                    localMessages.value.pop();
                    usePage().props.flash.error = event.content;
                    return;
                }

                if (lastMessage.isLoading && event.content) {
                    lastMessage.isLoading = false;
                }

                if (!event.isComplete) {
                    if (lastMessage && lastMessage.role === "assistant") {
                        lastMessage.content =
                            (lastMessage.content || "") + (event.content || "");
                        nextTick(() => scrollToBottom());
                    }
                }

                if (event.isComplete) {
                    console.log("✅ Message complet reçu");
                    if (localMessages.value.length === 2) {
                        router.reload({ only: ["conversations"] });
                    }
                }
            });

        channelSubscription.value = subscription;

        // Maintenir la connexion active
        const keepAlive = setInterval(() => {
            if (channelSubscription.value) {
                console.log("🔄 Maintien de la connexion au canal:", channel);
            }
        }, 30000); // Toutes les 30 secondes

        // Nettoyage
        onBeforeUnmount(() => {
            clearInterval(keepAlive);
            if (channelSubscription.value) {
                channelSubscription.value.unsubscribe();
            }
        });
    }
});

const handleSelectConversation = (conversation) => {
    router.get(route("ask.index", { conversation_id: conversation.id }), {
        preserveState: true,
        preserveScroll: true,
        only: ["messages", "currentConversation"],
    });
    localMessages.value = conversation.messages || [];
    form.conversation_id = conversation.id;
    form.model = conversation.model || props.selectedModel;
};

const messagesContainer = ref(null);
const showScrollButton = ref(false);

const scrollToBottom = () => {
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop =
            messagesContainer.value.scrollHeight;
    }
};

const handleScroll = () => {
    if (!messagesContainer.value) return;

    const { scrollTop, scrollHeight, clientHeight } = messagesContainer.value;
    showScrollButton.value = scrollHeight - scrollTop - clientHeight > 100;
};

const handleSubmit = async () => {
    if (!form.message.trim()) return;

    console.log("Envoi du message:", form.message);

    const userMessage = {
        role: "user",
        content: form.message,
    };

    const assistantMessage = {
        role: "assistant",
        content: "",
        isLoading: true,
    };

    localMessages.value.push(userMessage);
    localMessages.value.push(assistantMessage);

    const originalMessage = form.message;
    form.message = "";

    try {
        console.log(
            "Appel API stream avec conversation:",
            props.currentConversation.id
        );
        await axios.post(route("ask.stream", props.currentConversation.id), {
            message: originalMessage,
            model: form.model,
        });
    } catch (error) {
        console.error("Erreur lors de l'envoi:", error);
        localMessages.value.pop();
        const errorMessage =
            error.response?.data?.error || "Erreur lors de l'envoi du message";
        usePage().props.flash.error = errorMessage;
    }
};

const handleKeydown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
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

const handleCommandSelect = (command) => {
    form.message = command.command + " ";
};

const isSidebarOpen = ref(true);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
    console.log("Sidebar state:", isSidebarOpen.value); // Pour déboguer
};

// Modification du watch pour utiliser localMessages
watch(
    localMessages,
    () => {
        nextTick(() => {
            scrollToBottom();
        });
    },
    { deep: true }
);
</script>

<template>
    <AppLayout title="Chat avec Kon-chan">
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Chat IA
            </h2>
        </template>

        <div class="flex h-[calc(100vh-8rem)]">
            <!-- Sidebar avec transition -->
            <div class="relative flex">
                <div
                    v-show="isSidebarOpen"
                    class="w-64 transition-transform duration-300 border-r border-gray-200 dark:border-gray-700"
                >
                    <ChatHistory
                        :conversations="conversations"
                        :selectedModel="form.model"
                        :current-conversation="currentConversation"
                        @select-conversation="handleSelectConversation"
                    />
                </div>

                <!-- Bouton avec nouvelle fonction toggleSidebar -->
                <button
                    @click="toggleSidebar"
                    class="absolute z-10 flex items-center justify-center w-6 h-12 transform -translate-y-1/2 bg-gray-100 rounded-r top-1/2 -right-6 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
                >
                    <component
                        :is="isSidebarOpen ? ChevronLeftIcon : ChevronRightIcon"
                        class="w-4 h-4 text-gray-500"
                    />
                </button>
            </div>

            <!-- Main Content -->
            <div class="flex flex-col flex-1">
                <!-- Model selector -->
                <div
                    class="flex items-center gap-4 p-2 border-b border-gray-200 dark:border-gray-700"
                >
                    <div class="flex items-center gap-2">
                        <label
                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Modèle :
                        </label>
                        <select
                            v-model="form.model"
                            @change="handleModelChange"
                            class="text-sm border-gray-300 rounded-md dark:border-gray-700 dark:bg-gray-900"
                        >
                            <option
                                v-for="model in models"
                                :key="model.id"
                                :value="model.id"
                            >
                                {{ model.name }}
                            </option>
                        </select>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        Modèle actuel :
                        {{
                            models.find((m) => m.id === form.model)?.name ||
                            form.model
                        }}
                    </span>
                </div>

                <!-- Messages Container avec événement scroll -->
                <div
                    ref="messagesContainer"
                    @scroll="handleScroll"
                    class="relative flex-1 p-4 space-y-4 overflow-y-auto"
                >
                    <div
                        v-for="(message, index) in localMessages"
                        :key="index"
                        :class="[
                            message.role === 'user' ? 'ml-auto' : 'mr-auto',
                            'max-w-[80%] p-3 rounded-lg',
                            message.role === 'user'
                                ? 'bg-blue-50 dark:bg-blue-900/20'
                                : 'bg-gray-50 dark:bg-gray-900/50',
                        ]"
                    >
                        <div class="flex items-start gap-2">
                            <div class="shrink-0">
                                <div
                                    :class="[
                                        message.role === 'user'
                                            ? 'bg-blue-100 dark:bg-blue-800'
                                            : 'bg-purple-100 dark:bg-purple-800',
                                        'px-2 py-1 rounded-full text-xs',
                                    ]"
                                >
                                    {{
                                        message.role === "user"
                                            ? "Vous"
                                            : "Kon-chan"
                                    }}
                                </div>
                            </div>
                            <MarkdownRenderer :content="message.content" />
                        </div>
                    </div>
                </div>

                <!-- Bouton Scroll to Bottom -->
                <button
                    v-show="showScrollButton"
                    @click="scrollToBottom"
                    class="fixed p-2 transition-all bg-gray-100 rounded-full shadow-lg bottom-24 right-8 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700"
                >
                    <ChevronDownIcon class="w-6 h-6 text-gray-500" />
                </button>

                <!-- Input Form -->
                <div
                    class="p-4 bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                >
                    <div class="relative max-w-4xl mx-auto">
                        <SlashCommandAutocomplete
                            :message="form.message"
                            :commands="availableCommands"
                            @select-command="handleCommandSelect"
                        />

                        <form
                            @submit.prevent="handleSubmit"
                            class="flex flex-col gap-2"
                        >
                            <textarea
                                v-model="form.message"
                                @keydown="handleKeydown"
                                rows="2"
                                class="w-full text-sm border-gray-300 rounded-lg dark:border-gray-700 dark:bg-gray-900"
                                placeholder="Tapez / pour les commandes. Entrée pour envoyer, Maj+Entrée pour nouvelle ligne"
                            ></textarea>
                            <div
                                class="flex items-center justify-between text-xs text-gray-500"
                            >
                                <div class="flex gap-4">
                                    <CharacterCount
                                        :text="form.message"
                                        :max="4000"
                                    />
                                    <span
                                        >Maj+Entrée = nouvelle ligne | Entrée =
                                        envoyer</span
                                    >
                                </div>
                                <PrimaryButton
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-3 py-1"
                                >
                                    Envoyer
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
