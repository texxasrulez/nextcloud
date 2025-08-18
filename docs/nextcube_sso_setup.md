NEXTCUBE: Same‑Origin SSO Setup (Recommended)
============================================

Date: 2025-08-14
Maintainer: Gene Hawkins

Goal
----
Roundcube login should transparently authenticate the Nextcloud iframe,
and Roundcube logout should end the Nextcloud session. This guide enforces
**same-origin** delivery (via reverse proxy) so modern cookie rules don't block the session.

Requirements
-----------
- Root/sudo on both hosts as needed.
- TLS (HTTPS) on the Roundcube origin.
- The Nextcloud app shipped in this plugin (ID: `nextcube_external`).

Roundcube (Server) Steps
------------------------
1. Ensure the plugin is present:
   - Path: `<roundcube>/plugins/nextcube/` (this directory, unchanged).

2. Enable the plugin in Roundcube config:
   - File: `<roundcube>/config/config.inc.php`
   - Find the `$config['plugins']` entry.
   - Add `'nextcube'` to the array. Example (line numbers vary by install):
     Lines: (search for 'plugins' and edit that line)
       $config['plugins'] = array_merge($config['plugins'] ?? [], ['nextcube']);

3. Create plugin config with SAME 24-char 3DES key used by Nextcloud:
   - File: `<roundcube>/plugins/nextcube/config.inc.php`
   - Content:
     <?php
     $rcmail_config['nextcloud_url'] = 'https://mail.example.com/cloud';
     $rcmail_config['roundcube_nextcloud_des_key'] = 'my_key_is_good_need_to24';
     // Optional debug: write verbose logs to logs/nextcube
     $rcmail_config['nextcube_debug'] = true;

Nextcloud (Server) Steps
------------------------
1. Install the Nextcube external login app:
   - Copy from Roundcube host (this plugin tree):
     Source: plugins/nextcube/upload_to_nextcloud_apps/nextcube_external/
     Destination on Nextcloud host: /var/www/nextcloud/apps/nextcube_external/

   Sample commands:
     sudo mkdir -p /var/www/nextcloud/apps/nextcube_external
     sudo rsync -a /path/to/plugins/nextcube/upload_to_nextcloud_apps/nextcube_external/ /var/www/nextcloud/apps/nextcube_external/
     sudo chown -R www-data:www-data /var/www/nextcloud/apps/nextcube_external
     sudo -u www-data php /var/www/nextcloud/occ app:enable nextcube_external

2. Set the SAME 24-char DES key in Nextcloud:
   - File: /var/www/nextcloud/config/config.php
   - Inside the returned array, add:
     'roundcube_nextcloud_des_key' => 'my_key_is_good_need_to24',

3. Reverse proxy Nextcloud under the Roundcube origin (example path `/cloud`):
   - In your Roundcube vhost, proxy `/cloud` → Nextcloud.
   - In Nextcloud config.php add:
     'overwritewebroot' => '/cloud',
     'trusted_proxies'  => ['<proxy_ip>'],
     'trusted_domains'  => ['mail.example.com'],

   - See docs/reverse_proxy_examples.md for Apache and Nginx snippets.

Verification
------------
1) Clear site data for the Roundcube origin in your browser.
2) Login to Roundcube → the Nextcloud iframe should show your account (no prompt).
3) Logout from Roundcube → the Nextcloud session ends.

Troubleshooting
---------------
- If the iframe shows Nextcloud’s login page:
  * Ensure the app `nextcube_external` is ENABLED on Nextcloud.
  * Confirm the **exact same** 24-char DES key on both sides.
  * Confirm `nextcloud_url` points to the **proxied** same-origin path (e.g., https://mail.example.com/cloud).
- Enable debug:
  * Roundcube: set `$rcmail_config['nextcube_debug'] = true;` then check `<roundcube>/logs/nextcube`.
- Separate domains?
  * Avoid. If unavoidable, you must set Nextcloud cookies with `SameSite=None; Secure` and configure CORS to allow credentials from the Roundcube origin.
