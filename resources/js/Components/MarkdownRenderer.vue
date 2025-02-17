<script setup>
import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import { computed } from "vue";
import "highlight.js/styles/github-dark.css";

const props = defineProps({
  content: {
    type: String,
    required: true,
  },
  images: {
    type: Array,
    default: () => [],
  },
});

// Configuration de base de MarkdownIt
const md = new MarkdownIt({
  html: true,
  linkify: true,
  typographer: true,
  breaks: true,
  highlight: function (str, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try {
        return hljs.highlight(str, { language: lang }).value;
      } catch (__) {}
    }
    return "";
  },
});

// Fonction pour transformer l'URL wiki en URL d'image directe
const transformWikiUrl = (url) => {
  // Nettoyage de base de l'URL
  let cleanUrl = url.replace(/[{}]/g, "").trim();

  // Si c'est une URL de wiki fandom
  if (cleanUrl.includes("onepiece.fandom.com")) {
    return "https://static.wikia.nocookie.net/onepiece/images/6/6d/Monkey_D._Luffy_Anime_Post_Timeskip_Infobox.png";
  }

  // Si c'est déjà une URL static.wikia
  if (cleanUrl.includes("static.wikia.nocookie.net")) {
    // Supprimer les paramètres d'URL pour éviter les problèmes de cache
    return cleanUrl.split("/revision/")[0];
  }

  return cleanUrl;
};

// Personnalisation spécifique pour les images
md.renderer.rules.image = function (tokens, idx) {
  const token = tokens[idx];
  const srcIndex = token.attrIndex("src");
  let src = token.attrs[srcIndex][1];
  const alt = token.content || "";

  // Transformation de l'URL
  const cleanSrc = transformWikiUrl(src);

  console.log("URL originale:", src);
  console.log("URL transformée:", cleanSrc);

  return `<img
        src="${cleanSrc}"
        alt="${alt}"
        class="max-w-full h-auto rounded-lg my-4 shadow-lg hover:shadow-xl transition-shadow duration-200"
        loading="lazy"
        decoding="async"
        onerror="this.onerror=null; this.classList.add('error'); console.error('Erreur de chargement image:', this.src);"
        crossorigin="anonymous"
    >`;
};

const renderedContent = computed(() => {
  if (!props.content) return "";

  // Nettoyage du contenu pour les images mal formatées
  let cleanContent = props.content
    .replace(/\{!\[(.*?)\]\((.*?)\)/g, "![$1]($2)")
    .replace(/!\[([^\]]*)\]\s*\((.*?)\)/g, (match, alt, url) => {
      return `![${alt}](${transformWikiUrl(url.trim())})`;
    });

  console.log("Contenu nettoyé:", cleanContent);

  return md.render(cleanContent);
});
</script>

<template>
  <div class="prose dark:prose-invert max-w-none overflow-hidden">
    <div v-if="props.content" v-html="renderedContent"></div>
    <div v-if="props.images && props.images.length > 0" class="mt-4">
      <img
        v-for="(image, index) in props.images"
        :key="index"
        :src="image"
        class="max-w-full h-auto rounded-lg shadow-lg"
        alt="Image jointe"
      />
    </div>
  </div>
</template>

<style>
.prose img {
  @apply max-w-full h-auto rounded-lg my-4 shadow-lg;
  max-height: 500px;
  object-fit: contain;
}

.prose img.error {
  @apply border-2 border-red-500 opacity-50;
}

.dark .prose img {
  @apply border border-gray-700;
}
</style>
