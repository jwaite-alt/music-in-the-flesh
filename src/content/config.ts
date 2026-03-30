import { defineCollection, z } from 'astro:content';

const pages = defineCollection({
  type: 'content',
  schema: z.object({
    title: z.string(),
  }).passthrough(),
});

const performances = defineCollection({
  type: 'content',
  schema: z.object({
    title:          z.string(),
    date:           z.coerce.date(),
    venue:          z.string(),
    location:       z.string().optional(),
    description:    z.string().optional(),
    featured:       z.boolean().default(false),
    videoUrl:       z.string().optional(),
    feedbackFormUrl:z.string().optional(),
    images:         z.array(z.string()).optional(),
    draft:          z.boolean().default(false),
  }),
});

const events = defineCollection({
  type: 'content',
  schema: z.object({
    title:     z.string(),
    date:      z.coerce.date(),
    venue:     z.string(),
    location:  z.string().optional(),
    description: z.string().optional(),
    ticketUrl: z.string().optional(),
    draft:     z.boolean().default(false),
  }),
});

const bios = defineCollection({
  type: 'content',
  schema: z.object({
    name:  z.string(),
    role:  z.string().optional(),
    order: z.number().default(99),
    photo: z.string().optional(),
  }),
});

export const collections = { pages, performances, events, bios };
