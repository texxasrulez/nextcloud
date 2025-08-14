Documentation by @ychaouche

nextcloud with a Reverse Proxy
==========================

The purpose of setting up a reverse proxy is to make urls look different. Some may prefer URL rewriting but since there is no need to do any if conditions or URL matching, a reverse proxy seems to fit perfectly for this job.

So we want http://cloud.domain.tld to appear as http://roundcube.domain.tld/cloud to circumvent the CSRF browser protection.

First, let's setup our roundcube's apache server to act as a reverse proxy for nextcloud : 

On the roundcube server
--------------------------------
Add these directives to your roundcube vhost to make it act as a reverse proxy for nextcloud when looking for the **/cloud** URL.

```
  ProxyPass  "/cloud/" "http://cloud.example.com/"
  ProxyPassReverse "/cloud/" "http://cloud.example.com/"

  # The ProxyRequests directive should usually be set off when using ProxyPass.
  # src:https://httpd.apache.org/docs/2.4/mod/mod_proxy.html#proxypass          
  ProxyRequests off

  # https://httpd.apache.org/docs/current/mod/mod_proxy.html#proxypreservehost \
  # This option should normally be turned Off.                                  
  ProxyPreserveHost off
```


On the nextcloud server
-------------------------------
You need to explicitly add the trusted proxies, also you need to override the webroot, otherwise it won't work because the [URLs will be malformed](http://serverfault.com/questions/783863/many-404-urls-when-using-mod-proxy-html).
```
<?php
$CONFIG = array (
  # for roundcube nextcloud plugin                                                     
  # https://docs.nextcloud.com/server/11/admin_manual/configuration_server/reverse_proxy_configuration.html
                                                 
  "overwritewebroot"  => "/cloud",          
  'trusted_proxies' => array ('10.10.10.20','10.10.10.19'),
  'nextcube_3des_key' => 'some des key',
...
)
```

Caveats
=======

The downside of this is that the original http://cloud.example.com won't work again because of the overriden webroot. 
