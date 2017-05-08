<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.3.2
Author: BMO ^_^
*/

require_once 'vendor/google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

/***** The Master Class to Rule them All *****/
class bmo_google_oath {

	private $oauth_secret_key;

	public function key_encrypt( $string ){
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND) ;
        $key = hash( 'sha256', SECURE_AUTH_KEY, true );
        $enc = mcrypt_encrypt( MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CFB, $iv);
        $com = $iv . $enc;
        return base64_encode( $com );
    }

    public function key_decrypt( $string ){
        if(! $string) return true;
        $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CFB );
        $debase = base64_decode( $string );
        $iv = substr( $debase, 0, $iv_size );
        $val = substr( $debase, $iv_size );
        $dc = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, hash( 'sha256', SECURE_AUTH_KEY, true ), $val, MCRYPT_MODE_CFB, $iv );
        return ( $dc ) ? $dc : $val;
    }

	public function bmo_oauth_secret_key( $oauth_secret_key = false ){
        if( $oauth_secret_key ) return $oauth_secret_key; // Helps with Testing
        $bmo_opts = get_option( 'bmo_oauth', [] );
        if( isset( $bmo_opts[ 'bmo_oauth_secret_key' ] ) ) return $this->key_decrypt( $bmo_opts[ 'bmo_oauth_secret_key' ] );
    }

}

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	require_once 'src/bmo-admin.php';

	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oauth2' );
}
