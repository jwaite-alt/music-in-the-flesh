// Rehype plugin: footnote styling for blog posts only.
//
// In the CMS, footnotes are written as:
//   - a marker in the text, in brackets:        ... in the subject mix (1).
//   - a "heading 6" line at the end of a post:  ###### 1 Source details...
//
// This turns the markers into superscript links that jump to the matching
// footnote, and the heading-6 lines into small footnote paragraphs whose
// number links back to the marker. The CMS editor needs no special footnote
// feature. Only files under src/content/blog/ are touched.

const MARKER = /\s*\((\d{1,2})\)/g;

const isElement = (node) => node && node.type === 'element';
const isFootnoteLine = (node) => isElement(node) && node.tagName === 'h6';
const leadingNumber = (node) => {
  const first = node.children?.[0];
  return first && first.type === 'text' ? first.value.match(/^\s*(\d{1,2})\s+/) : null;
};

// Superscript number. With `href`, it is a link; `id` marks the link as an anchor target.
function superscript(n, { href, id, label } = {}) {
  const inner = { type: 'text', value: n };
  const child = href
    ? {
        type: 'element',
        tagName: 'a',
        properties: { href, ...(id ? { id } : {}), ariaLabel: label },
        children: [inner],
      }
    : inner;
  return {
    type: 'element',
    tagName: 'sup',
    properties: { className: ['footnote-ref'] },
    children: [child],
  };
}

// Footnote numbers that actually have a footnote line, so we only link those.
function collectFootnotes(node, found = new Set()) {
  for (const child of node.children ?? []) {
    if (isFootnoteLine(child)) {
      const m = leadingNumber(child);
      if (m) found.add(m[1]);
    } else if (isElement(child)) {
      collectFootnotes(child, found);
    }
  }
  return found;
}

export default function blogFootnotes() {
  return (tree, file) => {
    const path = (file.path ?? '').replace(/\\/g, '/');
    if (!path.includes('/src/content/blog/')) return;

    const notes = collectFootnotes(tree);
    const seenRefs = new Set();

    // Split a text node into text + <sup> pieces wherever "(n)" appears.
    function splitMarkers(value) {
      const out = [];
      let last = 0;
      for (const m of value.matchAll(MARKER)) {
        if (m.index > last) out.push({ type: 'text', value: value.slice(last, m.index) });
        const n = m[1];
        if (notes.has(n)) {
          // Only the first marker for a number carries the id (ids must be unique).
          const id = seenRefs.has(n) ? undefined : `fnref-${n}`;
          seenRefs.add(n);
          out.push(superscript(n, { href: `#fn-${n}`, id, label: `Footnote ${n}` }));
        } else {
          out.push(superscript(n));
        }
        last = m.index + m[0].length;
      }
      if (last === 0) return null;
      if (last < value.length) out.push({ type: 'text', value: value.slice(last) });
      return out;
    }

    function walk(node, inFootnote) {
      if (!node.children) return;
      const next = [];
      for (const child of node.children) {
        if (child.type === 'text' && !inFootnote) {
          next.push(...(splitMarkers(child.value) ?? [child]));
          continue;
        }
        if (isElement(child)) {
          if (isFootnoteLine(child)) {
            // Footnote line: <p class="footnote" id="fn-1"><sup><a>1</a></sup> text…</p>
            const m = leadingNumber(child);
            child.tagName = 'p';
            child.properties = { className: ['footnote'], ...(m ? { id: `fn-${m[1]}` } : {}) };
            if (m) {
              child.children[0].value = child.children[0].value.slice(m[0].length);
              child.children.unshift(
                superscript(m[1], { href: `#fnref-${m[1]}`, label: 'Back to the text' }),
                { type: 'text', value: ' ' },
              );
            }
            walk(child, true);
          } else if (!['a', 'code', 'pre'].includes(child.tagName)) {
            walk(child, inFootnote);
          }
        }
        next.push(child);
      }
      node.children = next;
    }

    walk(tree, false);
  };
}
