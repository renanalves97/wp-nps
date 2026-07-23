# NPS Feedback System

A lightweight WordPress plugin that collects Net Promoter Score (NPS) feedback from your customers through a simple shortcode, stores every submission as a Custom Post Type, and automatically sends tailored follow-up emails based on the score received.

## Features

- **`[nps_form]` shortcode** — drop a fully styled 0–10 NPS rating form (name, email, and reason) anywhere posts/pages/widgets support shortcodes.
- **Submission storage** — every submission is saved as a custom post type (`nps_submission` / "NPS Submissions") with the score and email stored as post meta, visible from a dedicated admin menu with custom columns (Customer, NPS Score, Email, Date).
- **Conditional email notifications**, sent via `wp_mail()`:
  - An internal notification to the admin/team with the full submission details.
  - A customer-facing thank-you email with different content depending on the score:
    - **Promoters (score ≥ 8):** thank-you message plus, if a Google Review URL is configured, a call-to-action button linking to it.
    - **Detractors/Neutrals (score < 8):** a simple acknowledgment message.
- **Settings screen** (under *NPS Submissions → Settings*) to configure, without touching code:
  - Primary and hover colors used by the form and emails.
  - Sender display name and admin notification email(s) (comma-separated for multiple recipients).
  - Google Review URL used in the promoter email and the front-end success message. **Empty by default** — no placeholder/example link ships with the plugin, so the Google review button/link only appears once you set your own URL.
  - Subject and rich-text body (via `wp_editor`) for both the promoter and the detractor/neutral email templates, with a `{name}` placeholder for the customer's name.
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
3. A new **NPS Submissions** menu will appear in the admin sidebar.

## Usage

Add the shortcode to any page, post, or widget area that supports shortcodes:

```
[nps_form]
```

By default the form displays a standard NPS question. You can override the title:

```
[nps_form title="How likely are you to recommend us to a friend or colleague?"]
```

Every submission is stored under **NPS Submissions** in the admin menu, and the configured notification/thank-you emails are sent automatically based on the score.

## Configuration

Go to **NPS Submissions → Settings** to set up:

| Section | Fields |
|---|---|
| Appearance | Primary color, hover (button) color |
| Notifications | Sender name, admin notification email(s), Google Review URL (empty by default) |
| Customer email — Promoters (score ≥ 8) | Subject, intro text |
| Customer email — Detractors/Neutrals (score < 8) | Subject, intro text |

The score summary block and the closing signature are appended automatically to the email body — only the intro text needs to be written in the settings screen. The Google review button/link (for promoters) is only included once you set a Google Review URL; it stays hidden on a fresh install.

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
