import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: "class",
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            typography: (theme) => ({
                DEFAULT: {
                    css: {
                        "code::before": {
                            content: '""',
                        },
                        "code::after": {
                            content: '""',
                        },
                    },
                },
                dark: {
                    css: {
                        color: theme("colors.gray.300"),
                        '[class~="lead"]': {
                            color: theme("colors.gray.300"),
                        },
                        a: {
                            color: theme("colors.indigo.400"),
                        },
                        strong: {
                            color: theme("colors.gray.100"),
                        },
                        "ul > li::before": {
                            backgroundColor: theme("colors.gray.600"),
                        },
                        hr: {
                            borderColor: theme("colors.gray.700"),
                        },
                        blockquote: {
                            color: theme("colors.gray.300"),
                            borderLeftColor: theme("colors.gray.700"),
                        },
                        h1: {
                            color: theme("colors.gray.100"),
                        },
                        h2: {
                            color: theme("colors.gray.100"),
                        },
                        h3: {
                            color: theme("colors.gray.100"),
                        },
                        h4: {
                            color: theme("colors.gray.100"),
                        },
                        code: {
                            color: theme("colors.gray.100"),
                            backgroundColor: theme("colors.gray.800"),
                        },
                        pre: {
                            color: theme("colors.gray.200"),
                            backgroundColor: theme("colors.gray.800"),
                        },
                        thead: {
                            color: theme("colors.gray.100"),
                            borderBottomColor: theme("colors.gray.600"),
                        },
                        tbody: {
                            tr: {
                                borderBottomColor: theme("colors.gray.700"),
                            },
                        },
                    },
                },
            }),
        },
    },

    plugins: [
        require("@tailwindcss/typography"),
        require("@tailwindcss/forms"),
    ],
};
