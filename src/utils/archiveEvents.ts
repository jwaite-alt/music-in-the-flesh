import { getCollection } from 'astro:content';
import { isoDay, todayLondon } from './eventDates';

const key = (date: string, title: string) => `${isoDay(date)}|${title.trim().toLowerCase()}`;

/**
 * Past events that have no full Archive entry yet. Each gets a basic archive page; adding an
 * Archive entry with the same title and date replaces it. Slugs carry the date so they can't
 * clash with Archive entries (the quarterly concerts share a title).
 */
export async function getDefaultArchiveEvents() {
  const performances = await getCollection('performances', ({ data }) => !data.draft);
  const covered = new Set(performances.map(p => key(p.data.date, p.data.title)));
  const today = todayLondon();
  const events = await getCollection('events', ({ data }) => !data.draft);
  return events
    .filter(e => isoDay(e.data.date) < today && !covered.has(key(e.data.date, e.data.title)))
    .map(e => ({ slug: `${isoDay(e.data.date)}-${e.id}`, entry: e }));
}
