<?php

if( ! defined( 'WPINC' ) ) die();

	require_once __DIR__ . '/../vendor/google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

	class bmo_google_client extends bmo_google_oauth {

		public $google;
		public $service;
		private $client_secrets;

		public function __construct( $options = [] ){

			if( empty( $options ) ) throw new Exception( 'No Client Options Set' ) ;
			if( ! isset( $options->client_id ) ) throw new Exception( 'No Client ID Set' );
			if( ! isset( $options->project_id ) ) throw new Exception( 'No Project ID Set' );
			if( ! isset( $options->client_secret ) ) throw new Exception( 'No Client Secret Set' );

			$this->client_secrets = [
				'web' => [
					'client_id' => $options->client_id,
					'project_id' => $options->project_id,
					'client_secret' => $this->key_decrypt( $options->client_secret ),
					'redirect_uris' => [ site_url() ],
					'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
					'token_uri' => 'https://accounts.google.com/o/oauth2/token',
					'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs'
				]
			];
		}

		public function init(){
			// Create new Google Client
			$this->google = $this->create_google_client();

			// Set Authorization Configurations
			$auth_config = $this->set_auth_config();

			// Set Service to be used after validation of code
			$this->service = $this->set_service();

			//Add proper Scope to Google Client
			$scope = $this->add_scope();
		}

		public function validate_code( $code = null ){
			if( empty( $code ) ) throw new Exception( 'No Code Provided to Validate' );
			try{
				return $this->google->authenticate( $code );
			} catch( Exception $e ){ throw new Exception( $e->getMessage() ); }
		}

		public function get_google_user(){
			try {
				return $this->service->userinfo->get();
			} catch( Exception $e ){ throw new Exception( $e->getMessage() ); }
		}

		private function create_google_client(){
			try {
				return new Google_Client();
			} catch( Exception $e ){ throw new Exception( $e->getMessage() ); }
		}

		private function set_auth_config(){
			try {
				$this->google->setAuthConfig( $this->client_secrets );
			} catch( Exception $e){ throw new Exception( $e->getMessage() ); }
		}

		public function get_auth_url(){
			try {
				return $this->google->createAuthUrl();
			} catch( Exception $e ){ throw new Exception( $e->getMessage() ); }
		}

		private function set_service(){
			try {
				return new Google_Service_Oauth2( $this->google );
			} catch( Exception $e ){ throw new Exception( $e->getMessage() )); }
		}

		private function add_scope(){
			try {
				$this->google->addScope( Google_Service_Oauth2::USERINFO_EMAIL );
			} catch( Exception $e ){ throw new Exception( $e->getMessage() ); }
		}

	}
