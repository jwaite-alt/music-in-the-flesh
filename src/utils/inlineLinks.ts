const escapeHtml = (s: string) =>
  s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

const LINK = /\[([^\]]+)\]\(([^)\s]+)\)/g;

/** Escapes text, then turns [label](url) into links. External links open in a new tab. */
export function inlineLinks(text: string): string {
  return escapeHtml(text).replace(LINK, (_, label, url) => {
    const external = /^https?:\/\//.test(url);
    return `<a href="${url}"${external ? ' target="_blank" rel="noopener noreferrer"' : ''}>${label}</a>`;
  });
}

/** Link-free text for places that can't show links (page meta descriptions). */
export function stripLinks(text: string): string {
  return text.replace(LINK, '$1');
}
