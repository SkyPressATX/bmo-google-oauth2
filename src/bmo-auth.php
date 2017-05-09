<?php

	require_once __DIR__ . '/../vendor/google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

	class bmo_auth extends bmo_google_oath {

		private $google;
		private $google_secrets;
		private $secret_key;
		private $redirect_url;
		private $auth_ur;
		private $token_uri;
		private $code;

		public function init(){

			//Config Google Client first and always
			$this->config_google_client();

			// Handle Actions Next
			if( isset( $_GET[ 'code' ] ) ) $this->validate_google_code( $_GET[ 'code' ] );
			if( ! is_user_logged_in() ) $this->login_init();
		}

		public function validate_google_code( $code ){
			// THIS NEEDS TO BE A REST API ENDPOINT IN THE END
			try {
				$this->google->authenticate( $code );
				$access_token = $this->google->getAccessToken();
				var_dump( $access_token );
				return $access_token;
			} catch( Exception $e ){ return $this->error_catch( $e ); }

		}

		public function login_init(){
			$this->get_auth_url();
			if( is_wp_error( $this->auth_url ) ) return $this->auth_url;

			wp_redirect( filter_var( $this->auth_url, FILTER_SANITIZE_URL ) );
			exit();
		}

		private function create_google_client(){
			$this->google = new Google_Client();
		}

		private function config_google_client(){
			$this->create_google_client();
			$this->configure_auth_config();
			$auth_config = $this->set_auth_config();
			if( is_wp_error( $auth_config ) ) return $auth_config;
			$scope = $this->add_scope();
			if( is_wp_error( $scope ) ) return $scope;
		}

		private function configure_auth_config(){
			unset( $this->bmo_options->bmo_oauth_allowd_domains );
			$this->bmo_options->client_secret = $this->bmo_oauth_secret_key();
			$this->bmo_options->auth_uri = "https://accounts.google.com/o/oauth2/auth";
			$this->bmo_options->token_uri = "https://accounts.google.com/o/oauth2/token";
			$this->bmo_options->auth_provider_x509_cert_url = "https://www.googleapis.com/oauth2/v1/certs";
			$this->bmo_options->redirect_uri = [ site_url() ];
			$this->google_secrets = [
				'web' => (array)$this->bmo_options
			];
		}

		private function set_auth_config(){
			try {
				$this->google->setAuthConfig( $this->google_secrets );
			} catch( Exception $e){
 				return $this->error_catch( $e );
			}
		}

		private function get_auth_url(){
			try {
				$this->auth_url = $this->google->createAuthUrl();
			} catch( Exception $e ){ return $this->error_catch( $e ); }
		}

		private function add_scope(){
			try {
				$this->google->addScope( 'https://www.googleapis.com/auth/userinfo.email' );
			} catch( Exception $e ){ return $this->error_catch( $e ); }
		}

		private function error_catch( $e = false ){
			var_dump( $e );
			return new WP_Error( $e->getMessage() );
		}

	}
