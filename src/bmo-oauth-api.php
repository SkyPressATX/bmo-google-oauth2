<?php

class bmo_api extends bmo_auth {

	private $auth;
	private $google_user;
	private $wp_user;
	private $approved;

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
			print_r( $this );
			if( ! $this->approved ) return new WP_Error( 'oauth-error', $this->google_user->email . ' Is Not Approved' );

			$this->get_wp_user_object();
			if( is_wp_error( $this->wp_user ) ) return $this->wp_user;

			$this->auto_login();
		} catch( Exception $e ){ return $this->error_catch( $e ); }
	}

	public function handle_errors( $param, $request, $key ){
		return new WP_Error( 'oauth-error', $param );
	}

	private function check_if_approved_email(){
		// $new_bmo = new bmo_google_oath;
		$approved_emails = explode( ',', $this->auth->bmo_options->bmo_oauth_allowed_domains );
		$email_check = explode( '@', $this->google_user->email );
		if( sizeof( $email_check ) > 2 ) return false;
		return ( in_array( $email_check[1], $approved_emails ) );
	}

	private function get_wp_user_object(){
		$wp_user = get_user_by( $this->google_user->email, 'login' );
		$this->wp_user = ( ! $wp_user ) ? $this->create_user : $wp_user;
	}

	private function create_user(){
		$random_password = wp_generate_password( 12, false );
		return wp_create_user( $this->google_user->email, $random_password, $this->google_user->email );
	}

	private function auto_login(){
		wp_set_current_user( $this->wp_user->ID );
		wp_set_auth_cookie( $this->wp_user->ID, true );
		do_action( 'wp_login', $this->wp_user->user_login );
	}

}

add_action( 'rest_api_init', [ new bmo_api, 'register_endpoint' ] );
