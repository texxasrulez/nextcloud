# Plugin to embed NextCloud within Roundcube

Open nextcloud instance directly in Roundcube with authentication

You can have interactions between mails and nextcloud attachments with the Roundrive plugin

License
-------

This plugin is released under the GNU General Public License Version 3
(http://www.gnu.org/licenses/gpl-3.0.html).

Install
-------

** Roundcube Requirements **
* Place this plugin folder into plugins directory of Roundcube
* Rename config.inc.php.dist to config.inc.php. Enter your domain, with trailing slash, this is important.
* Create a 24 character password key to add to nextcube_3des_key portion of config.inc.php.
* Add nextcloud to $config['plugins'] in your Roundcube config.

** Nextcloud Requirements **
* Upload the contents of 'upload_to_nextcloud_app' folder to your nextcloud installation as follows:
  /cloud_root_dir/apps/nextcube_external/
  
* The exact key above will need to be added to nextcloud's config.php with this line:
  'nextcube_3des_key' => 'same_24_key_as_roundcube_plugin',
* Enable 'External login for Roundcube' app.  

* To avoid cross-domain errors you should use the same url (domain), no subdomains either, for Roundcube and NextCloud (See the [reverse proxy documentation](reverseproxy.md), to use nextcloud and Roundcube on separate servers with Apache)
* Add and enable "roundcube_external" apps to your nextcloud instance (in nextcloud/apps/)

Configuration
-------------

* In Roundcube plugin you need to rename config.inc.php.dist to config.inc.php. And configure your nextcloud URL and a random DES key of 24 characters.
* In nextcloud, you need to edit the config.php file and add a 'nextcube_3des_key' property with the same DES key.

:moneybag: **Donations** :moneybag:

If you use this plugin and would like to show your appreciation by buying me a cup of coffee, I surely would appreciate it. A regular cup of Joe is sufficient, but a Starbucks Coffee would be better ... \
Zelle (Zelle is integrated within many major banks Mobile Apps by default) - Just send to texxasrulez at yahoo dot com \
No Zelle in your banks mobile app, no problem, just click [Paypal](https://paypal.me/texxasrulez?locale.x=en_US) and I can make a Starbucks run ...

