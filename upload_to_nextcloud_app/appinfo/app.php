<?php
$userSession = \OC::$server->getUserSession();

// Login from roundcube
if (isset($_POST["rc_user"]) && isset($_POST["rc_pwd"])) {
  // Check if another user is not logged in
  if ($userSession->isLoggedIn() && $userSession->getUser()->getUID() != $_POST["rc_user"]) {
    $userSession->logout();
  }

  // DES key decrypt
  $des = new DES();

  $uid = ( string ) $_POST["rc_user"];
  $password = ( string ) $des->decrypt($_POST["rc_pwd"]);
  if ($userSession->login($uid, $password)) {
    $user = $userSession->getUser();
    $userSession->createSessionToken(\OC::$server->getRequest(), $user->getUID(), $uid, $password, \OC\Authentication\Token\IToken::REMEMBER);
    $userSession->updateTokens($user->getUID(), $password);
  }
}

// Logout from roundcube
if (isset($_GET['rc_logout']) && isset($_GET['from_roundcube'])) {
  $userSession->logout();
}

class DES {
  /**
   * Decrypt 3DES-encrypted string
   *
   * @param string $cipher encrypted text
   * @param string $key encryption key to retrieve from the configuration, defaults to 'des_key'
   * @param boolean $base64 whether or not input is base64-encoded
   * @return string decrypted text
   */
  public function decrypt($cipher, $key = 'nextcube_3des_key', $base64 = true) {
    if (! $cipher) {
      return '';
    }

    $cipher = $base64 ? base64_decode($cipher) : $cipher;
    $ckey = \OC::$server->getConfig()->getSystemValue($key);

    if (function_exists('openssl_decrypt')) {
      $method = 'DES-EDE3-CBC';
      $opts = defined('OPENSSL_RAW_DATA') ? OPENSSL_RAW_DATA : true;
      $iv_size = openssl_cipher_iv_length($method);
      $iv = substr($cipher, 0, $iv_size);

      // session corruption? (#1485970)
      if (strlen($iv) < $iv_size) {
        return '';
      }

      $cipher = substr($cipher, $iv_size);
      $clear = openssl_decrypt($cipher, $method, $ckey, $opts, $iv);
    }
    else if (function_exists('mcrypt_module_open') && ($td = mcrypt_module_open(MCRYPT_TripleDES, "", MCRYPT_MODE_CBC, ""))) {
      $iv_size = mcrypt_enc_get_iv_size($td);
      $iv = substr($cipher, 0, $iv_size);

      // session corruption? (#1485970)
      if (strlen($iv) < $iv_size) {
        return '';
      }

      $cipher = substr($cipher, $iv_size);
      mcrypt_generic_init($td, $ckey, $iv);
      $clear = mdecrypt_generic($td, $cipher);
      mcrypt_generic_deinit($td);
      mcrypt_module_close($td);
    }

    /*
     * -
     * Trim PHP's padding and the canary byte; see note in
     * rcube::encrypt() and http://php.net/mcrypt_generic#68082
     */
    $clear = substr(rtrim($clear, "\0"), 0, - 1);

    return $clear;
  }

  /**
   * Generates encryption initialization vector (IV)
   *
   * @param int Vector size
   * @return string Vector string
   */
  private function create_iv($size) {
    // mcrypt_create_iv() can be slow when system lacks entrophy
    // we'll generate IV vector manually
    $iv = '';
    for ($i = 0; $i < $size; $i ++) {
      $iv .= chr(mt_rand(0, 255));
    }

    return $iv;
  }
}