import {resolve} from 'path';
import {defineConfig} from 'vite';
import vue from '@vitejs/plugin-vue';
import i18nExtractKeys from './i18nExtractKeys.vite.js';

export default defineConfig({
	plugins: [
		i18nExtractKeys({
			extraKeys: [
				'plugins.generic.thoth.workStatus.active',
				'plugins.generic.thoth.workStatus.forthcoming',
				'plugins.generic.thoth.workStatus.withdrawn',
				'plugins.generic.thoth.workStatus.superseded',
				'plugins.generic.thoth.workStatus.postponedIndefinitely',
				'plugins.generic.thoth.workStatus.cancelled',
			],
		}),
		vue(),
	],
	build: {
		lib: {
			entry: resolve(__dirname, 'resources/js/main.js'),
			name: 'ThothPlugin',
			fileName: 'build',
			formats: ['iife'],
		},
		outDir: resolve(__dirname, 'public/build'),
		rollupOptions: {
			external: ['vue'],
			output: {
				globals: {
					vue: 'pkp.modules.vue',
				},
			},
		},
	},
});
