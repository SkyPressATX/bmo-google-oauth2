<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.7.3
Author: BMO ^_^
*/

if( ! defined( 'WPINC' ) ) die();

/***** The Master Class to Rule them All *****/
class bmo_google_oauth {

	public $menu_slug = 'bmo-oauth';
	public $option_slug = 'bmo_oauth';
	public $section_slug = 'bmo_oauth_options';
	public $cookie_slug = 'bmo_oauth_requested_url';
	public $is_google = false;
	public $requested_url;
	public $bmo_options;
	public $google_client;
	public $valid;
	public $google_user;
	public $wp_user;
	public $error;

	public function __construct(){
		if( ! isset( $this->bmo_options ) ) $this->bmo_options = (object) get_option( $this->option_slug, [] );
	}

	public function init(){
		if( isset( $this->bmo_options ) && $this->bmo_options->bmo_oauth_active ){
			$this->bmo_validate_request();
			if( is_wp_error( $this->error ) ) die( $this->error );

			if( ! $this->is_google && ! is_user_logged_in() ){
				$this->bmo_no_user_redirect();
				if( is_wp_error( $this->error ) ) die( $this->error );
				$this->bmo_set_current_user();
				if( is_wp_error( $this->error ) ) die( $this->error );
			}

			if( $this->is_google && is_user_logged_in() ){
				$this->bmo_redirect_to_requested_url();
				if( is_wp_error( $this->error ) ) die( $this->error );
			}
		}
	}

	public function bmo_validate_request(){
		 //Check if this is a request from Google
		 $this->is_google = $this->is_request_google();
		 if( is_wp_error( $this->is_google ) ) return $this->is_google;
		 if( ! $this->is_google ) return; // Move along if this isn't even a google request

		 //If this is a google request, validate the $_GET[ 'code' ] param
		 $this->valid = $this->google_client->validate_code( $_GET[ 'code' ] );
		 if( is_wp_error( $this->valid ) ) return $this->valid;

		 //Let's strip the $_GET[ 'code' ] param so nothing else can use it
		 $strip = $this->strip_code_param();
		 if( is_wp_error( $strip ) ) return $strip;

		 //Get the Google User
		 $this->google_user = $this->google_client->get_google_user();
		 if( is_wp_error( $this->google_user ) ) return $this->google_user;

		 //Check if the Google User Email is allowed agains our list of Approved Domains
		 $approved = $this->approve_google_user();
		 if( is_wp_error( $approved ) ) return $approved;
		 if( ! $approved ){
			 $this->google_user = NULL;
			 $this->is_google = FALSE;
		 }
	}

	/*
	 * is_request_google
	 *
	 * Check if there is a "code" param set in $_GET
	 * If so, try and validate it with the Google Client
	 */
	public function is_request_google(){
		if( ! isset( $_GET[ 'code' ] ) ) return false; // No $_GET[ 'code' ]? Not a google request

		$this->google_client = new bmo_google_client( $this->bmo_options );
		$this->google_client->init();
		if( is_wp_error( $this->google_client ) ) return $this->google_client;
		return true;
	}

	public function bmo_no_user_redirect(){
		// First save the originally requested url
		$this->set_requested_url_cookie();
		// Build the google_client (it won't be built yet if we are here)
		$this->google_client = new bmo_google_client( $this->bmo_options );
		$this->google_client->init();
		if( is_wp_error( $this->google_client ) ) return $this->google_client;
		// Get the official $auth_url from the google_client
		$auth_url = $this->google_client->get_auth_url();
		if( is_wp_error( $auth_url ) ) return $auth_url;
		// Redirect the user to Google
		wp_redirect( filter_var( $auth_url, FILTER_SANITIZE_URL ) );
		exit();
	}

	public function strip_code_param(){
		try {
			$_GET[ 'code' ] = NULL;
			return true;
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	public function bmo_set_current_user(){
		if( ! $this->is_google ) return $this->error_catch( new WP_Error( 'invalid-oauth', 'Not a Google Request') );
		if( ! isset( $this->google_user ) ) return $this->error_catch( new WP_Error( 'invalid-oauth', 'No Google User' ) );

		$this->wp_user = $this->get_wp_user_object();
		if( is_wp_error( $this->wp_user ) ) return $this->wp_user;

		$this->auto_login();
	}

	public function approve_google_user(){
		try {
			if( ! isset( $this->bmo_options->bmo_oauth_allowed_domains ) ) return true;
			$domains = explode( ',', $this->bmo_options->bmo_oauth_allowed_domains );
			if( empty( $domains ) ) return true;
			return ( in_array( $this->google_user->hd , $domains ) );
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	public function bmo_redirect_to_requested_url(){
		$requested_url = $this->get_requested_url_cookie();
		$this->delete_requested_url_cookie();
		wp_redirect( $requested_url );
		exit();
	}

	private function get_wp_user_object(){
		$wp_user = get_user_by( 'login', $this->google_user->email );
		return ( ! $wp_user ) ? $this->create_user() : $wp_user;
	}

	private function create_user(){
		$random_password = wp_generate_password( 12, false );
		$new_id = wp_create_user( $this->google_user->email, $random_password, $this->google_user->email );
		return get_user_by( $new_id, 'id' );
	}

	private function auto_login(){
		wp_set_current_user( $this->wp_user->ID );
		wp_set_auth_cookie( $this->wp_user->ID, true );
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

	public function set_requested_url_cookie(){
		$this->requested_url = home_url( add_query_arg( [] ) );
		setcookie( $this->cookie_slug, $this->requested_url, ( time() + 8600 ), COOKIEPATH, COOKIE_DOMAIN );
	}

	public function get_requested_url_cookie(){
		if( ! isset( $_COOKIE[ $this->cookie_slug ] ) ) return home_url();
		return $_COOKIE[ $this->cookie_slug ];
	}

	public function delete_requested_url_cookie(){
		if( ! isset( $_COOKIE[ $this->cookie_slug ] ) ) return;
		setcookie( $this->cookie_slug, '', ( time() - 1 ), COOKIEPATH, COOKIE_DOMAIN );
	}

	public function error_catch( $e = false ){
		if( ! is_wp_error( $e ) ) $e = new WP_Error( $e->getMessage() );
		$this->error = $e;
		return $e;
	}

}

/** Require Normal Files **/
require_once 'src/bmo-oauth-google-client.php';

$bga = new bmo_google_oauth;
$bga->wp_hooks();

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	require_once 'src/bmo-admin.php';

	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oauth2' );
}
