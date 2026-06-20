import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

// Sveltia/Decap CMS writes bare YAML dates (2027-03-28) which YAML parses
// as Date objects. Accept both and normalise to YYYY-MM-DD string.
const dateField = z.union([z.string(), z.date()]).transform(val =>
  val instanceof Date ? val.toISOString().split('T')[0] : val
);

const performances = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/performances' }),
  schema: z.object({
    title: z.string(),
    date: dateField,
    venue: z.string(),
    location: z.string().optional(),
    description: z.string(),
    featured: z.boolean().default(false),
    videoUrl: z.string().optional(),
    feedbackFormUrl: z.string().optional(),
    images: z.array(z.union([
      z.string().transform(s => ({ image: s, caption: undefined as string | undefined })),
      z.object({ image: z.string(), caption: z.string().optional() }),
    ])).default([]),
    draft: z.boolean().default(false),
  }),
});

const events = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/events' }),
  schema: z.object({
    title: z.string(),
    date: dateField,
    venue: z.string(),
    location: z.string().optional(),
    description: z.string(),
    ticketUrl: z.string().optional(),
    draft: z.boolean().default(false),
  }),
});

const pages = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/pages' }),
  schema: z.object({
    title: z.string().optional(),
    hero_intro: z.string().optional(),
    teaser: z.string().optional(),
  }),
});

const videos = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/videos' }),
  schema: z.object({
    title: z.string(),
    date: dateField,
    description: z.string(),
    embedUrl: z.string().optional(),
    videoUrl: z.string().optional(),
    videoUrlH265: z.string().optional(),
    thumbnail: z.string().optional(),
    featured: z.boolean().default(false),
    draft: z.boolean().default(false),
  }),
});

const galleries = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/galleries' }),
  schema: z.object({
    title: z.string(),
    date: dateField,
    description: z.string(),
    images: z.array(z.object({
      image: z.string(),
      caption: z.string().optional(),
    })).default([]),
    featured: z.boolean().default(false),
    draft: z.boolean().default(false),
  }),
});

const words = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/words' }),
  schema: z.object({
    title: z.string(),
    date: dateField,
    description: z.string(),
    cover: z.string().optional(),
    award: z.string().optional(),
    external_url: z.string().optional(),
    buy_url: z.string().optional(),
    buy_label: z.string().optional(),
    download_url: z.string().optional(),
    download_label: z.string().optional(),
    featured: z.boolean().default(false),
    draft: z.boolean().default(false),
  }),
});

export const collections = { performances, events, pages, videos, galleries, words };
