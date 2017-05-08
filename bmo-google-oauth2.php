<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.4.1
Author: BMO ^_^
*/

/***** The Master Class to Rule them All *****/
class bmo_google_oath {

	public $menu_slug = 'bmo-oauth';
	public $option_slug = 'bmo_oauth';
	public $section_slug = 'bmo_oauth_options';
	public $is_rest = false;
	public $bmo_options;

	public function __construct(){
		$this->bmo_options = (object)get_option( $this->option_slug, [] );
		$this->is_rest = $this->is_rest_request();
	}

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

	public function bmo_oauth_secret_key(){
        if( isset( $this->bmo_options->bmo_oauth_secret_key ) ) return $this->key_decrypt( $this->bmo_options->bmo_oauth_secret_key );
    }

	public function bmo_redirect_to_google(){
		var_dump( $this );
	}
	/**
     * Is Rest Request
     * Check REQUEST_URI against $api_prefix (c opied from WAR Framework )
     *
     * @param $api_prefix Sting
     * @return Bool
     */
    private function is_rest_request(){
		$api_prefix = apply_filters( 'rest_url_prefix', '' );
        $url = explode('/',$_SERVER["REQUEST_URI"]);
        array_shift($url);
        return ( $api_prefix === $url[0] || $url[0] === 'wp-json' );
    }

}

add_action( 'wp', [ new bmo_google_oath, 'bmo_redirect_to_google' ] );
/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	require_once 'src/bmo-admin.php';

	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oauth2' );
}
