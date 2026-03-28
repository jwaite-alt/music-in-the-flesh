# Music in the Flesh — University IT Handoff Note

*For the attention of the receiving web / IT team. Prepared March 2026.*

---

## What This Is

A static website for an academic performance project by Professor Bettina Varwig (Faculty of Music, University of Cambridge). The site is currently live at jwaite.com/musicintheflesh and managed by James Waite.

This document covers what the site is built from, what it needs to run, known issues for a university environment, and what would need to change on migration.

---

## What the Site Consists Of

The production site is **entirely static files** — HTML, CSS, JavaScript, images, and one PHP script. There is no database, no application server, and no runtime framework. Any web server that can serve files and run PHP can host it.

The file breakdown:
- `*.html`, `*.css`, `*.js` — the compiled website (built from source using Astro)
- `images/` — photographs (book cover, author portrait, performance images)
- `admin/` — the CMS interface (a single HTML file loading Sveltia CMS from a CDN)
- `oauth/index.php` — a PHP script handling GitHub login for the CMS
- `oauth/.htaccess` — Apache rewrite rules routing `/oauth/auth` and `/oauth/callback` to the PHP script

The site is built from source (in the GitHub repository) using Node.js / Astro at deploy time. The build output is what gets served — the build toolchain does not need to be installed on the web server.

---

## Minimum Server Requirements

| Requirement | Detail |
|---|---|
| Web server | Apache or Nginx (Apache preferred — the OAuth handler uses `.htaccess`) |
| PHP | 7.4 or later, with cURL extension enabled |
| HTTPS | Required — the CMS will not load over plain HTTP |
| Outbound HTTPS | The PHP OAuth script calls `api.github.com` — outbound port 443 must not be blocked |
| Static file serving | Standard — no special modules required |

No database. No Node.js at runtime. No application server.

---

## The CMS

Content is managed through a browser-based CMS (Sveltia CMS) at `/admin/`. Editors log in with a GitHub account — no separate CMS credentials are needed.

When an editor saves a change:
1. Sveltia CMS commits the change to the GitHub repository as a markdown file
2. GitHub Actions automatically builds the site and deploys the new files to the web server
3. The live site updates within 2–3 minutes

This means the web server receives files via automated FTP/SFTP deployment from GitHub Actions. The deployment credentials (FTP username and password) are stored as GitHub Actions secrets and are not in the repository.

### Editor Access

Editors need a GitHub account with Write access to the repository. Currently one editor account exists (`musicintheflesh-editor`). Additional editors can be added by a repository administrator without touching the web server.

---

## What Would Need to Change on Migration

### 1. Domain and paths — essential

The site is currently deployed at `jwaite.com/musicintheflesh/`. If moved to a different domain or root path, the following must be updated:

**In `astro.config.mjs` (rebuild required):**
```javascript
site: 'https://your.university.domain',
base: '/path/if/subfolder/',   // omit if deploying to root
```

**In `public/admin/config.yml`:**
```yaml
backend:
  base_url: https://your.university.domain   // origin only, no path
  auth_endpoint: /path/if/subfolder/oauth/auth
```

**In `public/oauth/credentials.php` (see Credentials below):**
```php
$redirectUri = 'https://your.university.domain/path/oauth/callback';
```

After changing `astro.config.mjs`, the site must be rebuilt (`npm run build`) and redeployed.

### 2. GitHub OAuth App — essential

A new GitHub OAuth App must be registered (or the existing one updated) with the new domain's callback URL:

- Go to: GitHub → Settings → Developer settings → OAuth Apps
- Update (or create) an app with:
  - **Homepage URL:** `https://your.university.domain/`
  - **Authorization callback URL:** `https://your.university.domain/path/oauth/callback`
- Note the new Client ID and Client Secret for use in step 3

### 3. OAuth credentials — essential

The PHP OAuth handler (`oauth/index.php`) reads credentials from either:

**Option A — environment variables (recommended):**
Set these on the web server via server config, cPanel, or `.htaccess`:
```
OAUTH_CLIENT_ID      = [from GitHub OAuth App]
OAUTH_CLIENT_SECRET  = [from GitHub OAuth App]
OAUTH_REDIRECT_URI   = https://your.university.domain/path/oauth/callback
```

**Option B — local credentials file (simpler for shared hosting):**
Copy `oauth/credentials.php.example` to `oauth/credentials.php` in the same directory on the server, and fill in the values. This file is not in the repository and must be placed on the server manually. It must not be committed to version control.

### 4. Deployment pipeline — likely needs adapting

Currently GitHub Actions pushes built files to the web server via FTP using stored credentials. University environments often have different deployment models — Git hooks, SSH, a CI/CD system, or a managed deployment process controlled by IT.

The build command is simply `npm run build` (requires Node.js 18+). The output goes into a `dist/` directory and can be deployed by any mechanism. GitHub Actions can be reconfigured to use SSH/rsync instead of FTP if preferred.

If the university prefers to control deployments manually: an editor saves in the CMS, a developer runs `git pull && npm run build`, and the `dist/` folder contents are uploaded to the server. This removes the automation but requires no CI/CD infrastructure.

### 5. Repository location — optional

The repository is currently at `github.com/jwaite-alt/music-in-the-flesh` (a personal GitHub account). It can be transferred to a university GitHub organisation without any changes to the site itself. The GitHub OAuth App and any Actions secrets would need to be recreated in the new location.

### 6. Sveltia CMS CDN dependency — optional

The CMS interface loads Sveltia CMS from `unpkg.com` (a public CDN). If the university's content security policy or network configuration restricts external script loads, the Sveltia CMS script can be downloaded and served locally instead. The file is `public/admin/index.html` and the change is a one-line update to the `<script>` src attribute.

---

## Security Notes

### Credentials are not in the repository

The OAuth client secret was removed from version control and is now loaded from a gitignored `credentials.php` file on the server, or from environment variables. The repository contains only a `credentials.php.example` template with placeholder values.

### The repository is public

The source code is publicly visible on GitHub (as is the compiled website). This is intentional — the content is academic and non-sensitive, and public repositories have no access cost. Only accounts with explicit Write access can edit content or trigger deployments.

### GitHub Actions secrets

FTP credentials are stored as GitHub Actions repository secrets and are not visible in the repository or logs.

### OAuth scope

The CMS requests `repo` and `user` GitHub OAuth scopes. `repo` is required so the CMS can commit content changes to the repository on behalf of the editor.

---

## What Is Not in Scope for This Handoff

- **WordPress migration** — see `development_notes.md` for a full assessment. Content migration can be automated; theme development cannot. Estimated effort: approximately one week of developer time.
- **Email / mailing list** — the Events page has a placeholder registration form. A real form (Mailchimp, Buttondown, or similar) has not yet been integrated.
- **Analytics** — not currently installed.

---

## Contacts

| Role | Name | Contact |
|---|---|---|
| Site owner / developer | James Waite | jwaite@gmail.com |
| Content author / editor | Bettina Varwig | Faculty of Music, University of Cambridge |
| GitHub account (developer) | jwaite-alt | github.com/jwaite-alt |
| GitHub account (editor) | musicintheflesh-editor | musicintheflesh.editor@gmail.com |

---

## Repository

`github.com/jwaite-alt/music-in-the-flesh`

Further technical detail, including a full log of errors encountered and resolved during the build, is in `development_notes.md` in the repository root.
