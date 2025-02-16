<template>
  <AppLayout title="Chat avec Kon-chan">
    <div class="flex flex-col h-screen">
      <!-- Container principal avec flex-col -->
      <div class="flex flex-1">
        <!-- Sidebar avec z-index et transition -->
        <nav
          :class="[
            'fixed top-0 left-0 h-full w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300 z-40',
            isSidebarOpen ? 'translate-x-0' : '-translate-x-full',
          ]"
        >
          <div class="pt-16">
            <ChatHistory
              :conversations="conversations"
              :selectedModel="form.model"
              :current-conversation="currentConversation"
              @select-conversation="handleSelectConversation"
            />
          </div>
        </nav>

        <!-- Bouton toggle -->
        <button
          @click="toggleSidebar"
          class="fixed z-40 top-1/2 transform -translate-y-1/2 flex items-center justify-center w-6 h-12 bg-gray-100 rounded-r hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
          :style="{ left: isSidebarOpen ? '256px' : '0' }"
        >
          <component
            :is="isSidebarOpen ? ChevronLeftIcon : ChevronRightIcon"
            class="w-4 h-4 text-gray-500"
          />
        </button>

        <!-- Zone de contenu principal avec flex-col et adaptation à la sidebar -->
        <main
          :class="[
            'flex flex-col flex-1 transition-all duration-300',
            isSidebarOpen ? 'ml-64' : 'ml-0',
          ]"
        >
          <!-- Titre h2 -->
          <h2
            class="text-xl font-semibold p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 sticky top-0"
          >
            {{ currentConversation?.title || "Nouvelle conversation" }}
          </h2>

          <!-- Model selector -->
          <div
            class="sticky top-[57px] z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700"
          >
            <div class="flex items-center gap-4 p-2">
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
                  models.find((m) => m.id === form.model)?.name || form.model
                }}
              </span>
            </div>
          </div>

          <!-- Messages Container avec flex-1 -->
          <div
            ref="messagesContainer"
            @scroll="handleScroll"
            class="flex-1 p-4 space-y-4 overflow-y-auto bg-gradient-to-b from-gray-50 to-white dark:from-gray-900 dark:to-gray-800"
          >
            <div
              v-for="(message, index) in localMessages"
              :key="index"
              class="flex items-end gap-2 group animate-fadeIn"
              :class="[
                message.role === 'user' ? 'justify-end' : 'justify-start',
              ]"
            >
              <!-- Avatar Assistant -->
              <div
                v-if="message.role !== 'user'"
                class="flex-shrink-0 mb-2 transition-transform group-hover:scale-110"
              >
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-br from-purple-400 to-purple-600 text-white shadow-lg"
                >
                  K
                </div>
              </div>

              <!-- Message Content -->
              <div
                class="message-bubble relative min-w-[60px] max-w-[85%] sm:max-w-[75%] md:max-w-[65%] px-4 py-2.5 shadow-sm transition-all"
                :class="[
                  message.role === 'user'
                    ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-2xl rounded-br-sm'
                    : 'bg-white dark:bg-gray-800 rounded-2xl rounded-bl-sm',
                ]"
              >
                <MarkdownRenderer
                  :content="message.content"
                  :class="[
                    'prose max-w-none',
                    message.role === 'user'
                      ? 'text-white dark:text-white prose-headings:text-white prose-a:text-white'
                      : 'text-gray-800 dark:text-gray-100',
                  ]"
                />
                <span
                  class="absolute bottom-0 text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity"
                  :class="[message.role === 'user' ? 'right-2' : 'left-2']"
                >
                  {{ new Date().toLocaleTimeString() }}
                </span>
              </div>

              <!-- Avatar Utilisateur -->
              <div
                v-if="message.role === 'user'"
                class="flex-shrink-0 mb-2 transition-transform group-hover:scale-110"
              >
                <div
                  class="w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-blue-600 text-white shadow-lg"
                >
                  U
                </div>
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

          <!-- Input Form fixé en bas -->
          <div
            class="sticky bottom-0 w-full bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-800"
          >
            <div class="relative max-w-4xl mx-auto">
              <div class="command-input-wrapper relative">
                <textarea
                  v-model="form.message"
                  @keydown.up.prevent="navigateCommands('up')"
                  @keydown.down.prevent="navigateCommands('down')"
                  @keydown.tab.prevent="completeCommand"
                  @keydown="handleKeydown"
                  @input="handleInput"
                  ref="messageInput"
                  class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                  rows="1"
                  placeholder="Tapez / pour voir les commandes disponibles..."
                ></textarea>

                <!-- Suggestions de commandes -->
                <div v-if="showCommands" class="command-suggestions">
                  <div
                    v-for="(cmd, index) in filteredCommands"
                    :key="cmd.command"
                    :class="[
                      'command-item',
                      { active: selectedIndex === index },
                    ]"
                    @click="selectCommand(cmd)"
                    @mouseover="selectedIndex = index"
                  >
                    <div class="command-name">{{ cmd.command }}</div>
                    <div class="command-usage" v-if="cmd.usage">
                      {{ cmd.usage }}
                    </div>
                    <div class="command-description">
                      {{ cmd.description }}
                    </div>
                  </div>
                </div>
              </div>

              <form @submit.prevent="handleSubmit" class="flex flex-col gap-2">
                <div
                  class="flex items-center justify-between text-xs text-gray-500"
                >
                  <div class="flex gap-4">
                    <CharacterCount :text="form.message" :max="4000" />
                    <span>Maj+Entrée = nouvelle ligne | Entrée = envoyer</span>
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
        </main>
      </div>
    </div>
  </AppLayout>
</template>

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

// Supprimer availableCommands et garder uniquement
const commands = ref([
  {
    command: "/help",
    description: "Affiche la liste des commandes disponibles",
    usage: "/help",
  },
  {
    command: "/meteo",
    description: "Affiche la météo pour une ville",
    usage: "/meteo <ville>",
  },
  {
    command: "/resume",
    description: "Résume un texte",
    usage: "/resume <texte>",
  },
]);

// Charger les commandes personnalisées
onMounted(() => {
  if (props.personalization?.slash_commands) {
    commands.value = [
      ...commands.value,
      ...props.personalization.slash_commands.map((cmd) => ({
        ...cmd,
        usage: cmd.usage || cmd.command,
      })),
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

        const lastMessage = localMessages.value[localMessages.value.length - 1];

        if (!lastMessage || lastMessage.role !== "assistant") {
          console.log("⚠️ Aucun message assistant ciblé pour concaténer");
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
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
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

    if (showCommands.value && filteredCommands.value.length > 0) {
      // Si les suggestions sont affichées, sélectionner la commande
      const selectedCommand = filteredCommands.value[selectedIndex.value];
      selectCommand(selectedCommand);
    } else if (!e.shiftKey) {
      // Sinon, si ce n'est pas Shift+Enter, envoyer le message
      handleSubmit();
    }
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

const message = ref("");
const showCommands = ref(false);
const selectedIndex = ref(0);
const messageInput = ref(null);

function handleInput() {
  showCommands.value = form.message.startsWith("/");
  if (showCommands.value) {
    selectedIndex.value = 0;
  }
}

const filteredCommands = computed(() => {
  if (!form.message.startsWith("/")) return [];
  const search = form.message.toLowerCase();
  return commands.value.filter((cmd) =>
    cmd.command.toLowerCase().startsWith(search)
  );
});

function navigateCommands(direction) {
  if (!showCommands.value) return;

  if (direction === "up") {
    selectedIndex.value = Math.max(0, selectedIndex.value - 1);
  } else {
    selectedIndex.value = Math.min(
      filteredCommands.value.length - 1,
      selectedIndex.value + 1
    );
  }
}

function completeCommand() {
  if (!showCommands.value) return;

  const selectedCommand = filteredCommands.value[selectedIndex.value];
  if (selectedCommand) {
    form.message = selectedCommand.usage;
    showCommands.value = false;
    messageInput.value.focus();
  }
}

function selectCommand(cmd) {
  form.message = cmd.command + " ";

  nextTick(() => {
    const input = messageInput.value;
    if (input && cmd.usage) {
      const placeholderStart = cmd.usage.indexOf("<");
      const placeholderEnd = cmd.usage.indexOf(">");

      if (placeholderStart !== -1 && placeholderEnd !== -1) {
        input.focus();
        const cursorPosition = cmd.command.length + 1;
        input.setSelectionRange(cursorPosition, cursorPosition);
      }
    }
  });
  showCommands.value = false;
}
</script>

<style scoped>
/* Animations et transitions */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out forwards;
}

/* Style des bulles de messages */
.message-bubble {
  transition: all 0.2s ease;
}

.message-bubble:hover {
  transform: scale(1.01);
}

/* Customisation du markdown dans les messages */
:deep(.prose) {
  max-width: none;
}

:deep(.prose pre) {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  margin: 0.5rem 0;
}

:deep(.prose code) {
  background: rgba(0, 0, 0, 0.1);
  padding: 0.2rem 0.4rem;
  border-radius: 0.25rem;
  font-size: 0.875em;
}

/* Style du conteneur de messages */
.messages-container::-webkit-scrollbar {
  width: 6px;
}

.messages-container::-webkit-scrollbar-track {
  background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 3px;
}

/* Dark mode adjustments */
.dark .message-bubble {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

/* ...vos autres styles existants... */
</style>
