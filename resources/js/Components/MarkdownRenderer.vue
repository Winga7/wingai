<script setup>
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import { computed } from 'vue';
import 'highlight.js/styles/github-dark.css';

const props = defineProps({
    content: {
        type: String,
        required: true
    }
});

const md = new MarkdownIt({
    html: true,
    linkify: true,
    typographer: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) {}
        }
        return '';
    }
});

const renderedContent = computed(() => {
    return md.render(props.content);
});
</script>

<template>
    <div class="prose dark:prose-invert max-w-none" v-html="renderedContent"></div>
</template>
