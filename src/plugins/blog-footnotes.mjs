// Rehype plugin: footnote styling for blog posts only.
//
// In the CMS, footnotes are written as:
//   - a marker in the text, in brackets:        ... in the subject mix (1).
//   - a "heading 6" line at the end of a post:  ###### 1 Source details...
//
// This turns the markers into superscripts and the heading-6 lines into small
// footnote paragraphs, so the CMS editor needs no special footnote feature.
// Only files under src/content/blog/ are touched.

const MARKER = /\s*\((\d{1,2})\)/g;

const isElement = (node) => node && node.type === 'element';

function superscript(n) {
  return {
    type: 'element',
    tagName: 'sup',
    properties: { className: ['footnote-ref'] },
    children: [{ type: 'text', value: n }],
  };
}

// Split a text node into text + <sup> pieces wherever "(n)" appears.
function splitMarkers(value) {
  const out = [];
  let last = 0;
  for (const m of value.matchAll(MARKER)) {
    if (m.index > last) out.push({ type: 'text', value: value.slice(last, m.index) });
    out.push(superscript(m[1]));
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
      const parts = splitMarkers(child.value);
      next.push(...(parts ?? [child]));
      continue;
    }
    if (isElement(child)) {
      if (child.tagName === 'h6') {
        // Footnote line: becomes <p class="footnote"><sup>1</sup> text…</p>
        child.tagName = 'p';
        child.properties = { className: ['footnote'] };
        const first = child.children[0];
        if (first && first.type === 'text') {
          const m = first.value.match(/^\s*(\d{1,2})\s+/);
          if (m) {
            first.value = first.value.slice(m[0].length);
            child.children.unshift(superscript(m[1]), { type: 'text', value: ' ' });
          }
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

export default function blogFootnotes() {
  return (tree, file) => {
    const path = (file.path ?? '').replace(/\\/g, '/');
    if (!path.includes('/src/content/blog/')) return;
    walk(tree, false);
  };
}
