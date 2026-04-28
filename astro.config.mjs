// @ts-check
import { defineConfig } from 'astro/config';
import remarkBreaks from 'remark-breaks';

// https://astro.build/config
export default defineConfig({
  site: 'https://staging.musicintheflesh.org',
  base: '/',
  markdown: {
    remarkPlugins: [remarkBreaks],
  },
});
