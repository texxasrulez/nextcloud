Reverse Proxy Examples for Nextcube (Same-Origin)
=================================================

These examples assume:
- Roundcube is served at https://mail.example.com
- Nextcloud backend runs at http://10.0.0.50:8080
- We want Nextcloud reachable at https://mail.example.com/cloud

Apache (on Roundcube host)
--------------------------
Enable modules (once):
  a2enmod proxy proxy_http headers rewrite

VHost (snippet):
  # Proxy Nextcloud under /cloud
  ProxyPass        /cloud  http://10.0.0.50:8080/ retry=0 timeout=60
  ProxyPassReverse /cloud  http://10.0.0.50:8080/

  # Allow cookies/headers to pass through
  RequestHeader unset  Cookie early
  RequestHeader edit   Cookie "(^|;\s*)([^=]+)=;?" "$1$2=" early

Nextcloud config.php additions:
  'overwritewebroot' => '/cloud',
  'trusted_proxies'  => ['10.0.0.1'],   # IP of this proxy
  'trusted_domains'  => ['mail.example.com'],

Nginx (on Roundcube host)
-------------------------
location /cloud/ {
    proxy_pass         http://10.0.0.50:8080/;
    proxy_set_header   Host              $host;
    proxy_set_header   X-Real-IP         $remote_addr;
    proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
    proxy_set_header   X-Forwarded-Proto $scheme;
    proxy_read_timeout 90s;
}
