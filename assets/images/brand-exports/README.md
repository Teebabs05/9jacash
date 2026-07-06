# Branding Asset Pack

Rasterized from the source SVGs in `assets/images/logo/` (plus two new
source SVGs kept here: `app-icon.svg` and `social-banner.svg`). None of
this is loaded by the running application — it's a delivery pack for
whoever manages app store listings, social media, and print/email use.
Replace any of these once you have your own final logo design; the
live site itself only ever reads `assets/images/logo/favicon.svg` and
whatever an admin uploads via **Admin → Settings**.

## Favicons

| File | Use |
|---|---|
| `favicon.ico` | Legacy multi-resolution icon (16/32/48/64px in one file) |
| `favicon-16x16.png`, `favicon-32x32.png`, `favicon-48x48.png` | Standard browser tab favicons |
| `favicon-180x180.png` | Apple touch icon (`<link rel="apple-touch-icon">`) |
| `favicon-192x192.png`, `favicon-512x512.png` | PWA / Android home-screen icons (e.g. a `manifest.json`) |

All favicon PNGs are transparent (RGBA) — the circular badge, not a
filled square.

## App Icons

| File | Use |
|---|---|
| `app-icon-512x512.png` | Google Play Store listing icon (512×512 required) |
| `app-icon-1024x1024.png` | Apple App Store listing icon (1024×1024 required) |

Both are **fully opaque** (no alpha channel) on purpose — both stores
reject icons with transparency. Source: `app-icon.svg`.

## Social Media

| File | Use |
|---|---|
| `social-banner-1200x630.png` | Open Graph / Twitter Card preview image, Facebook link previews, etc. (standard 1200×630 size) |

Source: `social-banner.svg`.

## Wordmark Logo Exports

| File | Use |
|---|---|
| `logo-dark-bg-2x.png` / `-3x.png` | Full "SURECASH MINING" wordmark, white text — for placement on dark backgrounds (dark email footers, dark slide decks, etc.) |
| `logo-light-bg-2x.png` / `-3x.png` | Same wordmark, navy text — for light backgrounds |

`2x`/`3x` refer to pixel density (680×128 / 1020×192) for crisp
rendering in documents and presentations rather than any specific
screen size.
