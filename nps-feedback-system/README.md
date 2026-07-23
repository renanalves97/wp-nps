# NPS Feedback System

A lightweight WordPress plugin that collects Net Promoter Score (NPS) feedback from your customers through a simple shortcode, stores every submission as a Custom Post Type, and automatically sends tailored follow-up emails based on the score received.

## Features

- **`[formulario_nps]` shortcode** — drop a fully styled 0–10 NPS rating form (name, email, and reason) anywhere posts/pages/widgets support shortcodes.
- **Submission storage** — every submission is saved as a custom post type (`nps_avaliacao` / "Avaliações NPS") with the score and email stored as post meta, visible from a dedicated admin menu with custom columns (Customer, NPS Score, Email, Date).
- **Conditional email notifications**, sent via `wp_mail()`:
  - An internal notification to the admin/team with the full submission details.
  - A customer-facing thank-you email with different content depending on the score:
    - **Promoters (score ≥ 8):** thank-you message plus a call-to-action button linking to your Google Review page.
    - **Detractors/Neutrals (score < 8):** a simple acknowledgment message.
- **Settings screen** (under *Avaliações NPS → Configurações*) to configure, without touching code:
  - Primary and hover colors used by the form and emails.
  - Sender display name and admin notification email(s) (comma-separated for multiple recipients).
  - Google Review URL used in the promoter email.
  - Subject and rich-text body (via `wp_editor`) for both the promoter and the detractor/neutral email templates, with a `{nome}` placeholder for the customer's name.
- **Deliverability-safe emails** — only the sender *display name* is customized (`wp_mail_from_name`); the technical sending address is left untouched to avoid breaking SPF/DKIM/DMARC validation on the receiving server.
- **Anti-spam honeypot** — a hidden field silently discards bot submissions without exposing that they were blocked.
- **AJAX-based submission** with nonce verification, so the page never reloads.
- Failed email deliveries are logged via the `wp_mail_failed` hook for easier debugging.

## Requirements

- WordPress 5.8 or later
- PHP 7.4 or later

## Installation

1. Download or clone this repository into your `wp-content/plugins/` directory:
   ```bash
   git clone https://github.com/<your-username>/nps-feedback-system.git
   ```
2. In the WordPress admin, go to **Plugins** and activate **NPS Feedback System**.
3. A new **Avaliações NPS** menu will appear in the admin sidebar.

## Usage

Add the shortcode to any page, post, or widget area that supports shortcodes:

```
[formulario_nps]
```

By default the form displays a standard NPS question. You can override the title:

```
[formulario_nps titulo="How likely are you to recommend us to a friend or colleague?"]
```

Every submission is stored under **Avaliações NPS** in the admin menu, and the configured notification/thank-you emails are sent automatically based on the score.

## Configuration

Go to **Avaliações NPS → Configurações** to set up:

| Section | Fields |
|---|---|
| Appearance | Primary color, hover (button) color |
| Notifications | Sender name, admin notification email(s), Google Review URL |
| Customer email — Promoters (score ≥ 8) | Subject, intro text |
| Customer email — Detractors/Neutrals (score < 8) | Subject, intro text |

The score summary block, the Google review button/link (for promoters), and the closing signature are appended automatically to the email body — only the intro text needs to be written in the settings screen.

## Project structure

```
nps-feedback-system/
├── nps-feedback-system.php      # Plugin bootstrap, shortcode registration, assets
├── includes/
│   ├── class-nps-cpt.php        # Custom Post Type & admin list columns
│   ├── class-nps-settings.php   # Settings API screen (colors, emails, recipients)
│   ├── class-nps-mailer.php     # Admin & customer email building/sending
│   └── class-nps-ajax.php       # AJAX handler for form submissions
├── templates/
│   └── nps-form-template.php    # Front-end form markup
└── assets/
    ├── css/nps-style.css
    └── js/
        ├── nps-script.js        # Front-end form submission (AJAX)
        └── nps-admin.js         # Admin color picker
```

## Author

**Renan Alves**
[https://renanalves.com.br/](https://renanalves.com.br/)
