# Music in the Flesh — Website Prototype: Standing Brief

*For use as a reference document in the Claude Code project.*

---

## Project Overview

Build a prototype website for **Music in the Flesh**, promoting the book and associated work of musicologist **Bettina Varwig** (University of Cambridge). The site documents the ideas in her book and archives practical performance experiments stemming from it.

It serves a **dual audience** — academic (credibility, rigour, depth) and general public including performers and concert-goers (accessibility, warmth, plain language) — with both audiences weighted equally for impact measurement purposes. The site exists in part to demonstrate measurable public and academic engagement to university funders.

---

## Tech Stack

- **Astro** (static site framework)
- **Decap CMS** (browser-based content editing, authenticated via GitHub, so that the non-technical site owner can edit all content without touching code)
- **GitHub** repository as the project backbone
- **Local dev server** for initial testing, then deployed to jwaite.com
- **Google Forms embeds** for audience feedback (linked to specific performance pages)

---

## Site Structure

1. **Home** — introduction to the book and its core ideas; visually led; accessible to a general reader; links to all main sections

2. **About Bettina** — biography, academic context, the project's intellectual territory; editable via Decap

3. **Featured Performance** — lead content area for the current or most recent event; supports video embed, image gallery, and text; fully editable via Decap; content from this area can be pushed to the Archive when superseded

4. **Archive** — structured library of past performances, each with its own page (date, venue, description, images, video where available, linked feedback form); new entries addable via Decap

5. **Upcoming Events** — trail of forthcoming events with expressions of interest capture (simple form or mailing list signup)

6. **Feedback** — Google Forms embeds linked to specific performances; responses feed to a Google Sheet for Bettina's review; one form per event, manageable without technical help

---

## Content

- Placeholder text throughout initially
- Visual language drawn from the book's artwork (images to be supplied — see Visual Style below)
- Tone: intellectually serious but warm and publicly accessible; no jargon without explanation

---

## Visual Style

Reference images from the book will be supplied. Claude Code should:

- Extract and apply the colour palette
- Match typographic weight and character (use Google Fonts equivalents as needed)
- Reflect the spatial feel — whitespace, proportion, layout density
- **Do not reproduce supplied artwork directly** — use as interpretive reference only

*"Match the colour palette, typographic weight and spatial feel of the reference images supplied. Do not reproduce them directly."*

---

## Setup Requirements

- Initialise a GitHub repository for the project
- Guide the developer through GitHub setup if not already configured
- Set up Decap CMS with GitHub authentication
- Configure Astro local dev server
- Structure content in markdown files with clear frontmatter so Decap can manage it cleanly

---

## Constraints

- Bettina must be able to edit all content areas without technical knowledge
- Site must be deployable to a standard server (jwaite.com) without specialist infrastructure
- Design should be clean, uncluttered, and reflect the visual warmth of the book's aesthetic once artwork is supplied
- Build with eventual handoff in mind — clean separation of content and presentation throughout
- The site may ultimately be adopted and hosted by the University of Cambridge; build accordingly

---

## Site Family — Design System Note

This site is the **first of a family of related projects**, currently anticipated to include:

- **Music in the Flesh** (this site — Bettina Varwig)
- **Gospel Oak Chorale** (choral ensemble site)
- **Music in Motion** (separate project, in development)

Build with a **shared design system** in mind — extractable components and CSS variables that sibling sites can inherit and inflect differently. Same architectural skeleton, distinct visual skin for each. Shared components likely to include: navigation pattern, footer, event card, archive structure, feedback form integration.

*"This site is the first of a family of related projects. Build with a shared design system in mind — extractable components and CSS variables that sibling sites can inherit."*

---

*Last updated: March 2026*
