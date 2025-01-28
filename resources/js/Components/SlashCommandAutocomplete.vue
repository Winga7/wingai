<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    message: String,
    commands: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['select-command']);
const showSuggestions = ref(false);
const filteredCommands = ref([]);

watch(() => props.message, (newMessage) => {
    if (newMessage.startsWith('/')) {
        const search = newMessage.slice(1).toLowerCase();
        filteredCommands.value = props.commands.filter(cmd =>
            cmd.command.slice(1).toLowerCase().startsWith(search)
        );
        showSuggestions.value = filteredCommands.value.length > 0;
    } else {
        showSuggestions.value = false;
    }
});

const selectCommand = (command) => {
    emit('select-command', command);
    showSuggestions.value = false;
};
</script>

<template>
    <div v-if="showSuggestions" class="absolute bottom-full left-0 w-full bg-white dark:bg-gray-800 border dark:border-gray-700 rounded-lg shadow-lg mb-2">
        <div class="max-h-48 overflow-y-auto">
            <div
                v-for="cmd in filteredCommands"
                :key="cmd.command"
                @click="selectCommand(cmd)"
                class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
            >
                <div class="font-medium">{{ cmd.command }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    {{ cmd.description }}
                </div>
            </div>
        </div>
    </div>
</template>
