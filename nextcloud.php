<?php
/**
 * Plugin nextcloud
 *
 * Display your nextcloud instance in Roundcube w/ auth
 *
 * Original author: Thomas Payen
 * License: GNU GPLv2+
 */

class nextcloud extends rcube_plugin {
  /**
   * Run for all tasks
   * @var string
   */
  public $task = '.*';

  /**
   * Initialize plugin
   */
  function init()
    {
        $rc = rcmail::get_instance();
        $task = (string)$rc->task;
        $action = (string)$rc->action;
        if ($task === 'settings' && (strpos($action, 'plugin.password') === 0 || $action === 'password')) {
            $this->add_texts('localization/', true);
            $this->include_stylesheet('nextcloud_password_note.css');
            $this->include_script('js/nextcloud_password_note.js');
            $rc->output->set_env('nextcloud_pw_note_enabled', true);
            $rc->output->set_env('nextcloud_labels', array(
                'nc_reminder' => $this->gettext('nc_reminder'),
            ));
        }

    $rcmail = rcmail::get_instance();

    // Load the configuration
    $this->load_config();
    $this->add_texts('localization/', false);

    // Hook on logout to disconnect Nextcloud
    $this->add_hook('logout_after', array($this, 'logout_after'));

    // Base CSS
    $this->include_stylesheet($this->local_skin_path() . '/nextcloud.css');

    // Create & register the task/button
    $this->register_task('nextcloud');
    $this->add_button(array(
      'command'    => 'nextcloud',
      'class'      => 'button-nextcloud',
      'classsel'   => 'button-nextcloud button-selected',
      'innerclass' => 'button-inner',
      'label'      => 'nextcloud.task',
      'type'       => 'link'
    ), 'taskbar');

    // If task is nextcloud load the frame
    if ($rcmail->task == 'nextcloud') {
      // Frame CSS
      $this->include_stylesheet($this->local_skin_path() . '/frame.css');
      // Disable refresh
      $rcmail->output->set_env('refresh_interval', 0);
      $this->register_action('index', array($this, 'action'));
      $this->login_nextcloud();
    }
    elseif ($rcmail->task == 'mail' || $rcmail->task == 'addressbook' || $rcmail->task == 'calendar') {
      // Link handler for external Nextcloud URLs
      $this->include_script('nextcloud_link.js');
      $rcmail->output->set_env('nextcloud_file_url', $rcmail->url(array(
        '_task'   => 'nextcloud',
        '_params' => '%%other_params%%'
      )));
      $rcmail->output->set_env('nextcloud_external_url', $rcmail->config->get('nextcloud_external_url'));
    }
  }

  /**
   * Render Nextcloud page
   */
  function action() {
    $rcmail = rcmail::get_instance();

    // register UI objects
    $rcmail->output->add_handlers(array(
      'nextcloud_frame' => array($this, 'nextcloud_frame')
    ));

    // template
    $rcmail->output->set_pagetitle($this->gettext('title'));
    $rcmail->output->send('nextcloud.nextcloud');
  }

  /**
   * After Roundcube logout, trigger Nextcloud disconnect
   */
  function logout_after($args) {
    $rcmail = rcmail::get_instance();
    $rcmail->output->set_env('nextcloud_url', $rcmail->config->get('nextcloud_url'));
    // Call the disconnect script to logout from nextcloud
    $this->include_script('disconnect.js');
  }

  /**
   * Frame display
   *
   * @param array $attrib
   * @return string
   */
  function nextcloud_frame($attrib) {
    if (empty($attrib['id'])) {
      $attrib['id'] = 'rcmnextcloudframe';
    }

    $rcmail = rcmail::get_instance();

    $attrib['name'] = $attrib['id'];

    $rcmail->output->set_env('contentframe', $attrib['name']);

    // Avoid undefined index and avoid passing null to abs_url()
    $src = (isset($attrib['src']) && $attrib['src']) ? $attrib['src'] : null;
    $rcmail->output->set_env(
      'blankpage',
      $src ? $rcmail->output->abs_url($src) : 'program/resources/blank.gif'
    );

    return $rcmail->output->frame($attrib);
  }

  /**
   * Login nextcloud
   */
  private function login_nextcloud() {
    $rcmail = rcmail::get_instance();
    $nextcloud_url = $rcmail->config->get('nextcloud_url');

    // Env variables
    $rcmail->output->set_env('nextcloud_username', $rcmail->user->get_username());
    $rcmail->output->set_env('nextcloud_password', urlencode($this->encrypt($rcmail->get_user_password())));
    $rcmail->output->set_env('nextcloud_url', $nextcloud_url);

    if (isset($_GET['_params'])) {
      $params = rcube_utils::get_input_value('_params', rcube_utils::INPUT_GET);
      $rcmail->output->set_env('nextcloud_gotourl', $nextcloud_url . $params);
    }
    else {
      $rcmail->output->set_env('nextcloud_gotourl', $nextcloud_url);
    }

    // Call the connection to nextcloud script
    $this->include_script('nextcloud.js');
  }

  /**
   * Encrypt using 3DES (EDE3-CBC) with a raw 24-byte key from plugin config.
   *
   * IMPORTANT: Do NOT use get_crypto_key(). We read a plain string setting to
   * avoid Roundcube's crypto-key registry errors.
   *
   * Primary key:   $config['nextcube_3des_key']   (preferred)
   * Fallback:      $config['des_key']             (legacy)
   */
  private function encrypt($clear, $key = 'nextcube_3des_key', $base64 = true) {
    if (!$clear) {
      return '';
    }

    $rcmail = rcmail::get_instance();

    // Preferred plugin key (never triggers crypto registry)
    $raw = (string) $rcmail->config->get($key);

    // Fallback: Roundcube's general des_key
    if ($raw === '' || $raw === null) {
      $raw = (string) $rcmail->config->get('des_key');
    }

    if ($raw === '' || $raw === null) {
      // No fatal; just empty string
      rcube::raise_error(array(
        'code'    => 500,
        'type'    => 'php',
        'line'    => __LINE__,
        'file'    => __FILE__,
        'message' => "Nextcube: 3DES key missing. Define nextcube_3des_key in plugins/nextcloud/config.inc.php (24 chars)."
      ), true, false);
      return '';
    }

    // Normalize to exactly 24 bytes (3DES key length)
    $ckey = (strlen($raw) < 24) ? str_pad($raw, 24, '0') : substr($raw, 0, 24);

    // Add a single canary byte (0x80) at the end of the clear text (legacy behavior)
    $clear = pack("a*H2", $clear, "80");

    if (function_exists('openssl_encrypt')) {
      $method = 'DES-EDE3-CBC';
      $opts   = defined('OPENSSL_RAW_DATA') ? OPENSSL_RAW_DATA : true;
      $iv     = $this->create_iv(openssl_cipher_iv_length($method));
      $cipher = $iv . openssl_encrypt($clear, $method, $ckey, $opts, $iv);
    }
    elseif (function_exists('mcrypt_module_open') && ($td = @mcrypt_module_open(MCRYPT_TripleDES, "", MCRYPT_MODE_CBC, ""))) {
      $iv = $this->create_iv(mcrypt_enc_get_iv_size($td));
      mcrypt_generic_init($td, $ckey, $iv);
      $cipher = $iv . mcrypt_generic($td, $clear);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }
    else {
      // No encryptor available
      rcube::raise_error(array(
        'code'    => 500,
        'type'    => 'php',
        'line'    => __LINE__,
        'file'    => __FILE__,
        'message' => "Encryption function not available (need OpenSSL or mcrypt)."
      ), true, false);
      return '';
    }

    return $base64 ? base64_encode($cipher) : $cipher;
  }

  /**
   * Generates encryption initialization vector (IV)
   *
   * @param int $size Vector size
   * @return string Vector string
   */
  private function create_iv($size) {
    // generate IV manually
    $iv = '';
    for ($i = 0; $i < $size; $i++) {
      $iv .= chr(mt_rand(0, 255));
    }
    return $iv;
  }
}
