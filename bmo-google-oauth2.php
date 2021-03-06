<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.8.5
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

	public function __construct(){
		if( ! isset( $this->bmo_options ) ) $this->bmo_options = (object) get_option( $this->option_slug, [] );
		if( isset( $_COOKIE[ $this->cookie_slug ] ) ) $this->requested_url = $_COOKIE[ $this->cookie_slug ];
	}

	public function init(){
		if( isset( $this->bmo_options ) && $this->bmo_options->bmo_oauth_active ){

			try {
				$this->bmo_validate_request();

				if( ! $this->is_google && ! is_user_logged_in() && $this->bmo_options->force_login )
					$this->bmo_no_user_redirect();


				if( $this->is_google && ! is_user_logged_in() && $this->bmo_options->force_login )
					$this->bmo_set_current_user();

				if( is_user_logged_in() && isset( $this->requested_url ) )
					$this->bmo_redirect_to_requested_url();

			} catch( Exception $e ){
				return $this->error_catch( $e );
			}

		}
	}

	public function bmo_validate_request(){
		 //Check if this is a request from Google
		 $this->is_google = $this->is_request_google();
		 if( ! $this->is_google ) return; // Move along if this isn't even a google request

		 //If this is a google request, validate the $_GET[ 'code' ] param
		 $this->valid = $this->google_client->validate_code( $_GET[ 'code' ] );

		 //Let's strip the $_GET[ 'code' ] param so nothing else can use it
		 $strip = $this->strip_code_param();

		 //Get the Google User
		 $this->google_user = $this->google_client->get_google_user();

		 //Check if the Google User Email is allowed agains our list of Approved Domains
		 $approved = $this->approve_google_user();

		 return;
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
		return true;
	}

	public function bmo_no_user_redirect(){
		// First save the originally requested url
		$this->set_requested_url_cookie();
		// Build the google_client (it won't be built yet if we are here)
		$this->google_client = new bmo_google_client( $this->bmo_options );
		$this->google_client->init();
		// Get the official $auth_url from the google_client
		$auth_url = $this->google_client->get_auth_url();
		// Redirect the user to Google
		wp_redirect( filter_var( $auth_url, FILTER_SANITIZE_URL ) );
		exit();
	}

	public function strip_code_param(){
		$_GET[ 'code' ] = NULL;
		return true;
	}

	public function bmo_set_current_user(){
		if( ! $this->is_google ) throw new Exception('Not a Google Reques');
		if( ! isset( $this->google_user ) ) throw new Exception( 'No Google User' );

		$this->wp_user = $this->get_wp_user_object();
		if( is_wp_error( $this->wp_user ) ) return $this->wp_user;

		$this->auto_login();
		return;
	}

	public function approve_google_user(){
		if( ! isset( $this->bmo_options->bmo_oauth_allowed_domains ) ) return true;
		$domains = explode( ',', $this->bmo_options->bmo_oauth_allowed_domains );
		if( empty( $domains ) ) return true;
		if( in_array( $this->google_user->hd , $domains ) ) return true;
		throw new Exception( "Not An Approved Email" );
	}

	public function bmo_redirect_to_requested_url(){
		$this->delete_requested_url_cookie();
		wp_redirect( $this->key_decrypt( $this->requested_url ) );
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
		wp_redirect( home_url() );
		exit();
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

	public function get_requested_url(){
		return home_url( add_query_arg( [] ) );
	}

	public function set_requested_url_cookie(){
		$req_url = $this->get_requested_url();
		if( $req_url !== home_url() )
			setcookie( $this->cookie_slug, $this->key_encrypt( $req_url ), ( time() + 8600 ), COOKIEPATH, COOKIE_DOMAIN );
	}

	public function delete_requested_url_cookie(){
		if( ! isset( $_COOKIE[ $this->cookie_slug ] ) ) return;
		setcookie( $this->cookie_slug, '', ( time() - 1 ), COOKIEPATH, COOKIE_DOMAIN );
	}

	public function error_catch( $e = false ){
		if( is_wp_error( $e ) ) $e = $e->get_error_message();
		if( is_object( $e ) && method_exists( $e, 'getMessage' ) ) $e = $e->getMessage();
		$this->bmo_die( $e );
	}

	public function bmo_die( $e = '', $code = 403 ){
		$this->delete_requested_url_cookie();
		wp_die( $e, $code );
	}

}

/** Require Normal Files **/
require_once 'src/bmo-oauth-google-client.php';

add_action( 'init', [ new bmo_google_oauth, 'init' ] );

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	require_once 'src/bmo-admin.php';

	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oauth2' );
}
