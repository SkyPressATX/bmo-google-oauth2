<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.4
Author: BMO ^_^
*/

/***** The Master Class to Rule them All *****/
class bmo_google_oath {

	public $menu_slug = 'bmo-oauth';
	public $option_slug = 'bmo_oauth';
	public $section_slug = 'bmo_oauth_options';
	public $bmo_options;

	public function __construct(){
		$this->bmo_options = (object)get_option( $this->option_slug, [] );
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

	/**
     * Is Rest Request
     * Check REQUEST_URI against $api_prefix (c opied from WAR Framework )
     *
     * @param $api_prefix Sting
     * @return Bool
     */
    public function is_rest_request(){
		$api_prefix = apply_filters( 'rest_url_prefix', '' );
        $url = explode('/',$_SERVER["REQUEST_URI"]);
        array_shift($url);
        $is_rest = ( $api_prefix === $url[0] || $url[0] === 'wp-json' );
        define( 'IS_REST', $is_rest );
        return $is_rest;
    }

}

$bmo_google_oath = new bmo_google_oath;
/**
 * Check if this is not an API call, then load bmo-auth.php
 **/
 $bmo_google_oath->is_rest_request();
 if( ! defined( 'IS_REST' ) && ! is_user_logged_in() ){
	var_dump( 'not a rest request' );
 }

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	require_once 'src/bmo-admin.php';

	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oauth2' );
}
