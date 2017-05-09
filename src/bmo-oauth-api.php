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
						'type' => 'string'
					],
					'error' => [
						'validate_callback' => [ $this, 'handle_errors' ],
						'type' => 'string'
					]
				]
			]

		);
	}

	public function auth_success( \WP_REST_Request $request ){

		$params = (object)$request;
		if( isset( $params->error ) ) return WP_Error( 'oath-error', $params->error );

		try {
			// $auth = new bmo_auth;
			// $auth->init();
			$this->google->authenticate( $params->code );
			$access_token = $this->google->getAccessToken();

			$this->auto_login( 1 );
			return $access_token;
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	public function handle_errors( $param, $request, $key ){
		return WP_Error( 'oauth-error', $param );
	}

	private function auto_login( $user ){
		wp_set_current_user( $user );
		wp_set_auth_cookie( $user, true );

		$user_obj = wp_get_current_user();
		do_action( 'wp_login', $user_obj->user_login );
	}

}

add_action( 'rest_api_init', [ new bmo_api, 'register_endpoint' ] );
