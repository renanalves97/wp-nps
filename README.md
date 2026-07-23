# NPS Feedback System

[![WordPress Version](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-GPLv2%20or%20later-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A lightweight WordPress plugin that collects Net Promoter Score (NPS) feedback from your customers through a simple shortcode, stores every submission as a Custom Post Type, and automatically sends tailored follow-up emails based on the score received.

---

## 🚀 Features

* **`[formulario_nps]` shortcode** — Drop a fully styled 0–10 NPS rating form (name, email, and reason) anywhere posts, pages, or widgets support shortcodes.
* **Submission storage** — Every submission is saved as a Custom Post Type (`nps_avaliacao` / *"Avaliações NPS"*) with the score and email stored as post meta. Visible from a dedicated admin menu with custom columns (*Customer*, *NPS Score*, *Email*, *Date*).
* **Conditional email notifications** — Sent via `wp_mail()`:
  * **Internal notification:** Sent to the admin/team with full submission details.
  * **Customer thank-you email:** Features dynamic content based on the rating:
    * **Promoters (score ≥ 8):** Thank-you message plus a Call-to-Action button linking to your Google Review page.
    * **Detractors/Neutrals (score < 8):** A simple acknowledgment message.
* **Settings screen** — Accessible via **Avaliações NPS → Configurações** to configure options without touching code:
  * Primary and hover colors used by the form and emails.
  * Sender display name and admin notification email(s) (comma-separated for multiple recipients).
  * Google Review URL used in the promoter email.
  * Subject and rich-text body (via `wp_editor`) for both promoter and detractor/neutral templates, with a `{nome}` placeholder for the customer's name.
* **Deliverability-safe emails** — Only the sender display name is customized (`wp_mail_from_name`); the technical sending address is left untouched to avoid breaking SPF/DKIM/DMARC validation on the receiving server.
* **Anti-spam honeypot** — A hidden field silently discards bot submissions without exposing that they were blocked.
* **AJAX-based submission** — Uses nonce verification so the page never reloads.
* **Email debugging** — Failed email deliveries are logged via the `wp_mail_failed` hook for easier troubleshooting.

---

## 📋 Requirements

* **WordPress:** 5.8 or later
* **PHP:** 7.4 or later

---

## 📦 Installation

1. Clone or download this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins/
   git clone [https://github.com/](https://github.com/)<your-username>/nps-feedback-system.git
