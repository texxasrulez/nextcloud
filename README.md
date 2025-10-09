# Plugin to embed NextCloud within Roundcube

[![Packagist Downloads](https://img.shields.io/packagist/dt/texxasrulez/nextcloud?style=plastic&logo=packagist&logoColor=white&label=Downloads&labelColor=blue&color=gold)](https://packagist.org/packages/texxasrulez/nextcloud)
[![Packagist Version](https://img.shields.io/packagist/v/texxasrulez/nextcloud?style=plastic&logo=packagist&logoColor=white&label=Version&labelColor=blue&color=limegreen)](https://packagist.org/packages/texxasrulez/nextcloud)
[![Github License](https://img.shields.io/github/license/texxasrulez/nextcloud?style=plastic&logo=github&label=License&labelColor=blue&color=coral)](https://github.com/texxasrulez/nextcloud/LICENSE)
[![GitHub Stars](https://img.shields.io/github/stars/texxasrulez/nextcloud?style=plastic&logo=github&label=Stars&labelColor=blue&color=deepskyblue)](https://github.com/texxasrulez/nextcloud/stargazers)
[![GitHub Issues](https://img.shields.io/github/issues/texxasrulez/nextcloud?style=plastic&logo=github&label=Issues&labelColor=blue&color=aqua)](https://github.com/texxasrulez/nextcloud/issues)
[![GitHub Contributors](https://img.shields.io/github/contributors/texxasrulez/nextcloud?style=plastic&logo=github&logoColor=white&label=Contributors&labelColor=blue&color=orchid)](https://github.com/texxasrulez/nextcloud/graphs/contributors)
[![GitHub Forks](https://img.shields.io/github/forks/texxasrulez/nextcloud?style=plastic&logo=github&logoColor=white&label=Forks&labelColor=blue&color=darkorange)](https://github.com/texxasrulez/nextcloud/forks)
[![Donate Paypal](https://img.shields.io/badge/Paypal-Money_Please!-blue.svg?style=plastic&labelColor=blue&color=forestgreen&logo=paypal)](https://www.paypal.me/texxasrulez)

Open nextcloud instance directly in Roundcube with authentication

You can have interactions between mails and nextcloud attachments with the Roundrive plugin

License
-------

This plugin is released under the GNU General Public License Version 3
(http://www.gnu.org/licenses/gpl-3.0.html).

Install
-------

* Place this plugin folder into plugins directory of Roundcube
* Add nextcloud to $config['plugins'] in your Roundcube config
* Upload contents of upload_to_nextcloud_apps into your apps directory for Nextcloud and enable plugin within Nextcloud.


NB: When downloading the plugin from GitHub you will need to create a
directory called nextcloud and place the files in there,
ignoring the root directory in the downloaded archive directory in the
downloaded archive.

Configuration
-------------

* In Roundcube plugin you need to rename config.inc.php.dist to config.inc.php. And configure your nextcloud URL and a random DES key of 24 characters.
* To avoid cross-domain errors you should use the same url (domain), no subdomains either, for Roundcube and NextCloud (See the [reverse proxy documentation](reverseproxy.md), to use nextcloud and Roundcube on separate servers with Apache)
* Add and enable "roundcube_external" apps to your nextcloud instance (in nextcloud/apps/)
* Edit Nextcloud's config/config.php and add this right below the 'secret' => '', entry:
```
  'app_install_overwrite' => 
  array (
    0 => 'nextcube_external',
  ),
```
* Add this line anywhere in config:
```
  'nextcube_3des_key' => '24_key_to_match_rc-plugin',
```

Save and you should be good to go.

:moneybag: **Donations** :moneybag:

If you use this plugin and would like to show your appreciation by buying me a cup of coffee, I surely would appreciate it. A regular cup of Joe is sufficient, but a Starbucks Coffee would be better ... \
Zelle (Zelle is integrated within many major banks Mobile Apps by default) - Just send to texxasrulez at yahoo dot com \
No Zelle in your banks mobile app, no problem, just click [Paypal](https://paypal.me/texxasrulez?locale.x=en_US) and I can make a Starbucks run ...

