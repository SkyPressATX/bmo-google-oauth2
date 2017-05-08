<?php

if( ! defined( 'WPINC' ) ){
	die;
}


class bmo_admin_options extends bmo_google_oath {

	private $active;
	private $client_id;
	private $secret_key;
	private $allowed_domains;


	public function __construct(){
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_admin_page(){
		add_options_page(
			'Your Dot', //$page_title
			'Your Dot', //$menu_title
			'edit_users', //$capability
			'your-dot', //$menu_slug
			[ $this, 'render_admin_page' ] //$function
		);
	}

	public function register_settings(){
		register_setting(
			'bmo_oauth',
			'bmo_oauth',
			[ $this, 'bmo_sanitize' ]
		);

		add_settings_section(
			'bmo_oauth_options',
			null,
			null,
			'bmo-oauth'
		);

		add_settings_field(
			'bmo_oauth_active', //$id
			'OAuth Active?', // $title
			[ $this, 'bmo_oauth_active_cb' ], //$callback
			'bmo-oauth', //$page
			'bmo_oauth_options' // $section
		);
		add_settings_field(
			'bmo_oauth_client_id', //$id
			'Oauth Client ID', // $title
			[ $this, 'bmo_oauth_client_id_cb' ], //$callback
			'bmo-oauth', //$page
			'bmo_oauth_options' // $section
		);
		add_settings_field(
			'bmo_oauth_secret_key', //$id
			'OAuth Secret Key', // $title
			[ $this, 'bmo_oauth_secret_key_cb' ], //$callback
			'bmo-oauth', //$page
			'bmo_oauth_options' // $section
		);
		add_settings_field(
			'bmo_oauth_allowd_domains', //$id
			'Allowed Domains', // $title
			[ $this, 'bmo_oauth_allowed_domains_cb' ], //$callback
			'bmo-oauth', //$page
			'bmo_oauth_options' // $section
		);
	}

	public function bmo_sanitize( $a ){
		if( is_array( $a ) && isset( $a[ 'bmo_oauth_secret_key_cb' ] ) ) $a[ 'bmo_oauth_secret_key_cb' ] = $this->key_encrypt( $a[ 'bmo_oauth_secret_key_cb' ] );
		return $a;
	}

	public function bmo_oauth_secret_key_cb(){
		printf(
			'<input type="password" id="your_dot_key" size="50" name="bmo_oauth[bmo_oauth_secret_key_cb]" value="%s" />',
			isset( $this->secret_key ) ? esc_attr( $this->secret_key ) : esc_attr( 'NONE' )
		);
	}

	public function render_admin_page(){
		$this->secret_key = $this->bmo_oauth_secret_key();

		echo '<div class="wrap"><H1>BMO Google OAuth2 Options</H1><form method="post" action="options.php">';
		settings_fields( 'bmo_oauth' );
		do_settings_sections( 'bmo-oauth' );
		submit_button();
		echo '</form></div>';

	}


}

if( is_admin() ) $your_dot_options = new your_dot_options;
