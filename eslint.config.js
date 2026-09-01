import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
    {
        ignores: ['node_modules', 'public/build', 'vendor'],
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            globals: globals.browser,
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
);
