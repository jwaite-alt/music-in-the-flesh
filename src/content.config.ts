import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const performances = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/performances' }),
  schema: z.object({
    title: z.string(),
    date: z.string(),
    venue: z.string(),
    location: z.string().optional(),
    description: z.string(),
    featured: z.boolean().default(false),
    videoUrl: z.string().optional(),
    feedbackFormUrl: z.string().optional(),
    images: z.array(z.string()).optional(),
    draft: z.boolean().default(false),
  }),
});

const events = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/events' }),
  schema: z.object({
    title: z.string(),
    date: z.string(),
    venue: z.string(),
    location: z.string().optional(),
    description: z.string(),
    ticketUrl: z.string().optional(),
    draft: z.boolean().default(false),
  }),
});

export const collections = { performances, events };
