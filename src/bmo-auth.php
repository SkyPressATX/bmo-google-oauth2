<?php

	require_once __DIR__ . '/../vendor/google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

	class bmo_auth extends bmo_google_oath {

		public $google;
		private $google_secrets;
		private $secret_key;
		public $redirect_url;
		private $auth_url;
		private $token_uri;
		private $code;
		public $rest_prefix;

		public function init(){

			$this->rest_prefix = rest_get_url_prefix();

			//Config Google Client first and always
			$this->config_google_client();

			$requested_url = explode( '/', $_SERVER[ 'REQUEST_URI' ] );
			array_shift( $requested_url );

			if( ! is_user_logged_in() && ( $requested_url[0] !== $this->rest_prefix ) ){
				$this->login_init();
			}
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

		public function config_google_client(){
			$this->create_google_client();
			$this->configure_auth_config();
			$auth_config = $this->set_auth_config();
			if( is_wp_error( $auth_config ) ) return $auth_config;
			$this->set_service();
			if( is_wp_error( $this->service ) ) return $this->service;
			$scope = $this->add_scope();
			if( is_wp_error( $scope ) ) return $scope;
		}

		private function configure_auth_config(){
			$this->redirect_url = site_url() . '/' . $this->rest_prefix;
			$this->redirect_url .= '/' . $this->option_slug . '/v1/' . $this->menu_slug;
			$this->bmo_options->client_secret = $this->bmo_oauth_secret_key();
			$this->bmo_options->auth_uri = "https://accounts.google.com/o/oauth2/auth";
			$this->bmo_options->token_uri = "https://accounts.google.com/o/oauth2/token";
			$this->bmo_options->auth_provider_x509_cert_url = "https://www.googleapis.com/oauth2/v1/certs";
			$this->bmo_options->redirect_uris = [ $this->redirect_url ];
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

		private function set_service(){
			try {
				$this->service = new Google_Service_Oauth2( $this->google );
			} catch( Exception $e ){ return $this->error_catch( $e ); }
		}

		private function add_scope(){
			try {
				$this->google->addScope( Google_Service_Oauth2::USERINFO_EMAIL );
			} catch( Exception $e ){ return $this->error_catch( $e ); }
		}

		public function error_catch( $e = false ){
			var_dump( $e->getMessage() );
			return new WP_Error( $e->getMessage() );
		}

	}
