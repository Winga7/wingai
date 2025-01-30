<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import BroadcastTest from "@/Components/BroadcastTest.vue";
</script>

<template>
    <AppLayout title="Test Broadcast">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg"
                >
                    <BroadcastTest />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
