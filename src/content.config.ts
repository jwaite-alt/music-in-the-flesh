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
    images: z.array(z.string()).optional(),
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
    hero_intro: z.string().optional(),
  }),
});

export const collections = { performances, events, pages };
