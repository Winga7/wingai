<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    personalization: Object
});

const form = useForm({
    identity: props.personalization?.identity || '',
    behavior: props.personalization?.behavior || '',
    slash_commands: props.personalization?.slash_commands || []
});

const addCommand = () => {
    form.slash_commands.push({
        command: '',
        description: '',
        prompt: ''
    });
};

const removeCommand = (index) => {
    form.slash_commands.splice(index, 1);
};

const submit = () => {
    form.post(route('ia.personalization.store'));
};
</script>

<template>
    <AppLayout title="Personnalisation de l'IA">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Personnalisation de l'IA
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <!-- Bloc Identité -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Qui suis-je ?
                            </h3>
                            <textarea
                                v-model="form.identity"
                                rows="4"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                placeholder="Décrivez qui vous êtes pour que l'IA puisse adapter ses réponses..."
                            ></textarea>
                            <InputError :message="form.errors.identity" class="mt-2" />
                        </div>

                        <!-- Bloc Comportement -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                                Comportement de l'IA
                            </h3>
                            <textarea
                                v-model="form.behavior"
                                rows="4"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                placeholder="Définissez comment l'IA doit se comporter..."
                            ></textarea>
                            <InputError :message="form.errors.behavior" class="mt-2" />
                        </div>

                        <!-- Section Commandes Slash -->
                        <div class="mb-8">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    Commandes Slash
                                </h3>
                                <PrimaryButton type="button" @click="addCommand">
                                    Ajouter une commande
                                </PrimaryButton>
                            </div>

                            <div v-for="(command, index) in form.slash_commands" :key="index"
                                 class="mb-4 p-4 border rounded-lg dark:border-gray-700">
                                <div class="flex justify-between mb-2">
                                    <InputLabel :value="`Commande ${index + 1}`" />
                                    <button type="button" @click="removeCommand(index)"
                                            class="text-red-500 hover:text-red-700">
                                        Supprimer
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <InputLabel value="Commande (ex: /meteo)" />
                                        <TextInput
                                            v-model="command.command"
                                            class="w-full mt-1"
                                            placeholder="/macommande"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel value="Description" />
                                        <TextInput
                                            v-model="command.description"
                                            class="w-full mt-1"
                                            placeholder="Description de ce que fait la commande"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel value="Prompt" />
                                        <textarea
                                            v-model="command.prompt"
                                            rows="3"
                                            class="w-full mt-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900"
                                            placeholder="Instructions pour l'IA (ex: Donne la météo actuelle pour la ville mentionnée)"
                                        ></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <PrimaryButton type="submit" :disabled="form.processing">
                                Sauvegarder
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
