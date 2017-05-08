<?php

	require_once __DIR__ . '/../vendor/google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

	class bmo_auth extends bmo_google_oath {

		private $google;
		private $secret_key;
		private $redirect_url;
		private $auth_ur;
		private $token_uri;

		public function init(){
			if( isset( $this->bmo_options->client_secret ) ) $this->bmo_options->client_secret = $this->key_decode( $this->bmo_options->client_secret );
			$this->bmo_options->auth_uri = "https://accounts.google.com/o/oauth2/auth";
			$this->bmo_options->token_uri = "https://accounts.google.com/o/oauth2/token";
			$this->bmo_options->auth_provider_x509_cert_url = "https://www.googleapis.com/oauth2/v1/certs";
			$client_secrets = [
				'web' => (array)$this->bmo_options
			];
			$this->google = new Google_Client();
			$this->google->setAuthConfig( $client_secrets );
			$this->auth_url = filter_var( $this->google->createAuthUrl(), FILTER_SANITIZE_URL );
			wp_redirect( $this->auth_url );
			exit();
		}

	}
