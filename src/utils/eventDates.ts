/** Today's date in the UK as YYYY-MM-DD, so an event stays "upcoming" until its day is over. */
export function todayLondon(): string {
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/London' }).format(new Date());
}

export const isoDay = (date: string) => String(date).slice(0, 10);
