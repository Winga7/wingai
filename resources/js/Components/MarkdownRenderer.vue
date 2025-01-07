<script setup>
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import { computed } from 'vue';
import CodeBlock from './CodeBlock.vue';
import 'highlight.js/styles/github-dark.css';

const props = defineProps({
    content: {
        type: String,
        required: true
    }
});

const md = new MarkdownIt({
    html: true,
    breaks: true,
    linkify: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return `<code-block code="${encodeURIComponent(str)}" language="${lang}"></code-block>`;
            } catch (__) {}
        }
        return `<code-block code="${encodeURIComponent(str)}"></code-block>`;
    }
});

const renderedContent = computed(() => md.render(props.content));
</script>

<template>
    <div class="markdown-response">
        <div class="prose dark:prose-invert max-w-none" v-html="renderedContent"></div>
    </div>
</template>

<style>
.markdown-response {
    @apply p-6 rounded-lg bg-white dark:bg-gray-800 shadow-sm;
}

.prose pre {
    @apply m-0;
}

.prose code {
    @apply bg-transparent p-0;
}

.code-block-wrapper {
    @apply rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700;
}
</style>
