# Music in the Flesh — Development Notes

*Prepared March 2026. Documents the build process, technical decisions, errors encountered and resolved, and notes on future migration.*

---

## Project Overview

A prototype website for *Music in the Flesh*, a performance and research project by Bettina Varwig (University of Cambridge). The site serves a dual audience — academic and general public — and must be editable by Bettina without technical knowledge.

**Live site:** jwaite.com/musicintheflesh
**Repository:** github.com/jwaite-alt/music-in-the-flesh
**CMS:** jwaite.com/musicintheflesh/admin

---

## Technology Stack

| Layer | Technology | Rationale |
|---|---|---|
| Framework | Astro 5 (static site) | Fast, file-based, no database, easy deployment |
| CMS | Sveltia CMS | Browser-based editing via GitHub; no server required |
| Auth | Custom PHP OAuth handler | Required for non-Netlify hosting |
| Hosting | cPanel shared hosting (jwaite.com) | Existing infrastructure |
| Deployment | GitHub Actions → FTP | Automated on every push or CMS save |
| Version control | GitHub (jwaite-alt/music-in-the-flesh) | Public repo; only collaborators can edit |
| Fonts | Cormorant Garamond (display) + EB Garamond (body) | Matches book cover aesthetic |

The site is the first of a small family (Gospel Oak Chorale, Music in Motion). The CSS design system uses custom properties throughout and is designed to be extractable for sibling sites.

---

## Design System

Colours extracted from the book cover photographs:

```css
--color-cream:     #F5ECD7   /* page background */
--color-parchment: #E8D5B0   /* section backgrounds */
--color-kraft:     #C4A882   /* borders, dividers */
--color-crimson:   #8C1C13   /* primary accent */
--color-slate:     #4A5E7A   /* secondary accent */
--color-ink:       #1C150D   /* body text */
```

Typography uses a slightly enlarged base size (1.2rem) to suit the academic/literary register of the content.

---

## Site Structure

```
src/
  content/
    pages/           ← CMS-editable page text
      home.md
      about.md
      about-project.md
    performances/    ← CMS-editable performance entries
    events/          ← CMS-editable event entries
  pages/
    index.astro      ← Home
    about.astro      ← About Bettina
    about-project.astro
    featured.astro   ← Current featured performance
    archive/
      index.astro
      [slug].astro   ← Individual performance pages
    events.astro
    feedback.astro
  layouts/
    BaseLayout.astro
    PageLayout.astro
  components/
    Navigation.astro
    Footer.astro
    EventCard.astro
  styles/
    global.css
public/
  admin/
    index.html       ← Sveltia CMS entry point
    config.yml       ← CMS collections config
  oauth/
    index.php        ← GitHub OAuth handler
    .htaccess        ← Routes /auth and /callback to index.php
  images/
    book-cover.jpg
    bettina.jpg
.github/
  workflows/
    deploy.yml       ← Build and FTP deploy on push to main
```

---

## CMS Architecture

Sveltia CMS (a maintained drop-in replacement for Decap CMS) runs entirely in the browser and commits content changes directly to GitHub as markdown files. GitHub Actions then builds the Astro site and deploys via FTP.

Three content collections are defined:

- **Pages** (files collection) — home, about, about-project; uses `body` widget for markdown prose
- **Performances** (folder collection) — each performance is a markdown file with structured frontmatter
- **Events** (folder collection) — same pattern for upcoming events

Because the CMS writes bare YAML dates (`2027-03-28`) which YAML parses as Date objects rather than strings, the Astro content schema uses a union type to accept both and normalise to a string:

```typescript
const dateField = z.union([z.string(), z.date()]).transform(val =>
  val instanceof Date ? val.toISOString().split('T')[0] : val
);
```

---

## GitHub OAuth for Non-Netlify Hosting

Sveltia CMS authenticates editors via GitHub OAuth. On Netlify this is handled automatically; on cPanel shared hosting a custom PHP handler is required.

**Flow:**
1. Editor clicks "Sign in with GitHub" in the CMS
2. CMS opens a popup to `/musicintheflesh/oauth/auth`
3. PHP handler redirects to GitHub's OAuth authorisation page
4. GitHub redirects back to `/musicintheflesh/oauth/callback` with a temporary code
5. PHP exchanges the code for an access token via the GitHub API (using cURL)
6. Callback page posts the token back to the CMS opener window via `postMessage`
7. CMS receives the token, closes the popup, and logs the editor in

**GitHub OAuth App settings:**
- Homepage URL: `https://jwaite.com/musicintheflesh/`
- Authorization callback URL: `https://jwaite.com/musicintheflesh/oauth/callback`

**CMS config.yml:**
```yaml
backend:
  name: github
  repo: jwaite-alt/music-in-the-flesh
  branch: main
  base_url: https://jwaite.com
  auth_endpoint: /musicintheflesh/oauth/auth
```

Note: `base_url` must be the origin only (no path). Including a path causes the CMS to silently discard the OAuth token because it checks `event.origin` against `new URL(base_url).origin` — origins never include paths.

**Editor access:** The GitHub account `musicintheflesh-editor` (email: musicintheflesh.editor@gmail.com) has Write access to the repository. Bettina logs in as this account. Any editor with Write access to the repo can use the CMS.

---

## Deployment Pipeline

```
CMS save / git push to main
        ↓
GitHub Actions: checkout → npm ci → npm run build → FTP deploy
        ↓
jwaite.com/musicintheflesh (cPanel subfolder)
```

The FTP deployment account root maps directly to the `musicintheflesh` subfolder, so `server-dir: /` in the workflow is correct.

The Astro build must be configured with a trailing slash on `base`:
```javascript
// astro.config.mjs
base: '/musicintheflesh/'
```
Without the trailing slash, production builds generate broken internal links (e.g. `/musicinthefleshabout` rather than `/musicintheflesh/about`).

---

## Errors Encountered and How They Were Resolved

### 1. Astro 5 content config breaking changes

Astro 5 moved from `src/content/config.ts` to `src/content.config.ts`, changed the API to use a `glob()` loader, replaced `entry.render()` with `render(entry)` imported from `astro:content`, and replaced `entry.slug` with `entry.id`.

*Resolution:* Moved config file, updated all imports and API calls throughout the page files.

### 2. Missing `workflow` scope on GitHub Personal Access Token

The initial PATs generated to push the repository lacked the `workflow` scope and could not push `.github/workflows/` files.

*Resolution:* Regenerated the PAT with both `repo` and `workflow` scopes checked.

### 3. FTP hostname not resolving from GitHub Actions

The cPanel FTP hostname (`ftp.formuladrone.uk`) failed DNS lookup from GitHub Actions runners.

*Resolution:* Used the shared server IP address as `FTP_SERVER` in the GitHub Actions secrets instead of the hostname.

### 4. Wrong FTP `server-dir`

Initially set to `/public_html/jwaite.com/music-in-the-flesh/` but the FTP account's root IS the target folder.

*Resolution:* Set `server-dir: /`.

### 5. 123-reg stripped hyphens from folder name

The cPanel File Manager created `musicintheflesh` rather than `music-in-the-flesh`.

*Resolution:* Updated `base` in `astro.config.mjs` to `/musicintheflesh/`.

### 6. Production BASE_URL missing trailing slash

The Astro dev server returns `BASE_URL = /musicintheflesh/` but the production build returned `/musicintheflesh` (no trailing slash), causing all internal links to render incorrectly.

*Resolution:* Set `base: '/musicintheflesh/'` (with trailing slash) in `astro.config.mjs`. The lesson: always verify the production build with `npm run build` and grep the dist HTML before pushing — the dev server can mask this class of error.

### 7. Sveltia CMS OAuth — blank popup / no reaction

Getting the custom PHP OAuth handler working with Sveltia CMS on a subfolder deployment required resolving several layered issues:

- **Missing `.htaccess`** — the PHP handler lives at `/oauth/index.php` but the CMS requests `/oauth/auth` and `/oauth/callback`. An Apache rewrite rule was needed to route both to `index.php`.
- **Netlify identity widget in admin HTML** — a boilerplate `netlify-identity-widget.js` script was intercepting the OAuth `postMessage` events. Removing it unblocked the flow.
- **Wrong `base_url`** — setting `base_url: https://jwaite.com/musicintheflesh` included a path, which caused the CMS to discard incoming tokens because `event.origin` never includes a path. Changing to `base_url: https://jwaite.com` with the path moved to `auth_endpoint` resolved it.
- **Decap CMS vs Sveltia CMS** — Decap CMS 3.x appeared to handle the external OAuth `postMessage` handshake differently from documented behaviour. Switching to Sveltia CMS (a maintained drop-in replacement whose auth spec matched our PHP handler exactly) resolved the remaining stall.
- **`type="module"` on script tag** — Sveltia CMS is not an ES module; the attribute caused unexpected behaviour and a console warning. Removed.

### 8. CMS breaking the build after saves

Sveltia CMS writes dates as bare YAML (`2027-03-28`), which the YAML parser treats as a `Date` object. The Astro content schema was typed as `z.string()`, causing a type mismatch and build failure on every CMS save.

*Resolution:* Changed the date field schema to a union that accepts both strings and Date objects and normalises to `YYYY-MM-DD`.

### 9. Pages collection — empty frontmatter parse error

The `about.md` and `about-project.md` page files had empty frontmatter (`---\n---\n`), which caused Sveltia CMS to throw a parse error when loading the collection.

*Resolution:* Added a `title` field to each file's frontmatter to ensure it is never empty.

---

## Content Notes

All placeholder content — performances, events, and most page text — was AI-generated during the build and is clearly marked. Bettina Varwig should replace it via the CMS before the site goes public. The book description on the home page was OCR'd from back-cover photographs supplied during the build.

---

## Future Migration: WordPress or University Hosting

If the project transfers to university infrastructure — which may require WordPress — migration has two distinct components with different levels of automation possible.

### What can be automated

All content is stored as structured markdown files with YAML frontmatter. A migration script could read every performance, event and page file and push them into WordPress using either:

- **WordPress REST API** — standard HTTP requests, no server access required
- **WP-CLI** — command-line tool available on most managed WordPress hosts

A single PHP or Python script could migrate all content in a matter of hours. Because the data is clean and consistently structured, this is a reliable automation target.

### What requires manual work

The visual design and templates cannot be meaningfully automated. WordPress runs on PHP theme files (or a block theme), and each layout component — the hero, card, archive listing, navigation — would need to be rebuilt as a WordPress template part. The CSS custom properties and colour palette translate directly and could be pasted into a WordPress theme's `style.css` with minimal adaptation. A realistic estimate for a developer familiar with WordPress theme development is:

- Content migration script: 0.5 days
- WordPress theme build matching current design: 3–5 days
- Custom post types for Performances and Events (with ACF or equivalent): 1 day
- **Total: approximately one week**

### Important caveat

University WordPress installations are frequently locked down — restricted plugin access, IT department involvement, managed hosting with limited FTP or SSH access. It is worth establishing what the university's environment actually permits before committing to WordPress as the destination. Some institutions run heavily constrained environments that would complicate both migration and ongoing management.

### Alternative: headless WordPress

A middle path worth considering: WordPress as a headless CMS (providing a REST API) with the existing Astro front-end retained. This would give Bettina the familiar WordPress editor while keeping the static site performance and existing design. The migration effort would be similar but the deployment model more complex.

---

## Possible Next Steps

- Replace all placeholder content via the CMS
- Set up a real audience interest / mailing list form (Mailchimp, Buttondown, or a Google Form embed) to replace the current mailto placeholder on the Events page
- Add real performance images and video embeds when available
- Consider a simple analytics integration (Plausible or Fathom — lightweight, privacy-respecting)
- When the project expands, extract the CSS design system into a shared package for the sibling sites (Gospel Oak Chorale, Music in Motion)
