// @ts-check
import { defineConfig } from 'astro/config';
import remarkBreaks from 'remark-breaks';
import blogFootnotes from './src/plugins/blog-footnotes.mjs';

// https://astro.build/config
export default defineConfig({
  site: 'https://staging.musicintheflesh.org',
  base: '/',
  markdown: {
    remarkPlugins: [remarkBreaks],
    rehypePlugins: [blogFootnotes],
  },
});
