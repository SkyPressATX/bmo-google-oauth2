<?php

class bmo_api extends bmo_auth {

	private $auth;
	private $google_user;
	private $wp_user;
	private $approved;
	private $requested_url;

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

		$params = (object)$request->get_params();
		if( isset( $params->error ) ) return new WP_Error( 'oath-error', $params->error );
		if( ! isset( $params->code ) ) return new WP_Error( 'oauth-error', 'No Auth Code' );

		try {
			$this->auth = new bmo_auth;
			$this->auth->init();

			$this->auth->google->authenticate( $params->code );

			$this->google_user = $this->auth->service->userinfo->get();

			$this->approved = $this->check_if_approved_email();
			if( ! $this->approved ) return new WP_Error( 'oauth-error', $this->google_user->email . ' Is Not Approved' );

			$this->get_wp_user_object();
			if( is_wp_error( $this->wp_user ) ) return $this->wp_user;

			$this->auto_login();

			$this->requested_url = $this->get_requested_url();
			$this->remove_cookies();

			if( is_wp_error( ( $this->requested_url ) ) ) return $this->requested_url;

			wp_redirect( $this->requested_url );
			exit();
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	private function get_requested_url(){
		if( ! isset( $_COOKIE[ $this->option_slug . '_requested_url' ] ) ) return home_url();
		return $_COOKIE[ $this->option_slug . '_requested_url' ];
	}

	private function remove_cookies(){
		setcookie( $this->option_slug . '_requested_url', '', time() - 300, COOKIEPATH, COOKIE_DOMAIN );
	}

	public function handle_errors( $param, $request, $key ){
		return new WP_Error( 'oauth-error', $param );
	}

	private function check_if_approved_email(){
		$domains = explode( ',', $this->bmo_options->bmo_oauth_allowed_domains );
		return ( in_array( $this->google_user->hd , $domains ) );
	}

	private function get_wp_user_object(){
		$wp_user = get_user_by( 'login', $this->google_user->email );
		$this->wp_user = ( ! $wp_user ) ? $this->create_user() : $wp_user;
	}

	private function create_user(){
		$random_password = wp_generate_password( 12, false );
		$new_id = wp_create_user( $this->google_user->email, $random_password, $this->google_user->email );
		return get_user_by( $new_id, 'id' );
	}

	private function auto_login(){
		wp_set_current_user( $this->wp_user->ID );
		wp_set_auth_cookie( $this->wp_user->ID, true );
		do_action( 'wp_login', $this->wp_user->user_login );
	}

}

add_action( 'rest_api_init', [ new bmo_api, 'register_endpoint' ] );
