<?php

class bmo_api extends bmo_auth {

	public function register_endpoint(){
		register_rest_route(
			$this->option_slug . '/v1', // Namespace
			'/' . $this->menu_slug, // URI
			[
				'methods' => \WP_REST_Server::READABLE,
				'callback' => [ $this, 'auth_success' ],
				'args' => [
					'code' => [
						'validate_callback' => [ $this, 'validate_code' ],
						'type' => 'string',
						'sanitize_callback' => 'sanitize_text_field'
					],
					'error' => [
						'validate_callback' => [ $this, 'handle_errors' ],
						'type' => 'string',
						'sanistize_callback' => 'sanitize_text_field'
					]
				]
			]

		);
	}

	public function auth_success( \WP_REST_Request $request ){
		print_r( $request );
		wp_redirect( home_url() );
	}

	public function validate_code( $param, $request, $key ){
		try {
			$this->google->authenticate( $code );
			$access_token = $this->google->getAccessToken();
			var_dump( $access_token );
			return $access_token;
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	public function handle_errors( $param, $request, $key ){
		return WP_Error( 'oauth-error', $param );
	}

}

add_action( 'rest_api_init', [ new bmo_api, 'register_endpoint' ] );
