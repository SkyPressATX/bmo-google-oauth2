<?php

if( ! defined( 'WPINC' ) ){
	die;
}


class bmo_admin_options extends bmo_google_oath {

	public function __construct(){
		add_action( 'admin_menu', [ $this, 'add_admin_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	public function add_admin_page(){
		add_options_page(
			'BMO Google OAuth', //$page_title
			'BMO Google OAuth Settings', //$menu_title
			'edit_users', //$capability
			$this->menu_slug, //$menu_slug
			[ $this, 'render_admin_page' ] //$function
		);
	}

	public function register_settings(){
		register_setting(
			$this->option_slug, //$option_group
			$this->option_slug, //$option_name
			[ $this, 'bmo_sanitize' ]//$sanistize_callback
		);

		add_settings_section(
			$this->section_slug, //$id
			null, //$title
			null, //$callback
			$this->menu_slug //$page
		);

		add_settings_field(
			'bmo_oauth_active', //$id
			'OAuth Active?', // $title
			[ $this, 'bmo_oauth_active_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'bmo_oauth_client_id', //$id
			'Oauth Client ID', // $title
			[ $this, 'bmo_oauth_client_id_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'bmo_oauth_secret_key', //$id
			'OAuth Secret Key', // $title
			[ $this, 'bmo_oauth_secret_key_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'bmo_oauth_allowd_domains', //$id
			'Allowed Domains', // $title
			[ $this, 'bmo_oauth_allowed_domains_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
	}

	public function bmo_sanitize( $a ){
		if( is_array( $a ) && isset( $a[ 'bmo_oauth_secret_key_cb' ] ) ) $a[ 'bmo_oauth_secret_key_cb' ] = $this->key_encrypt( $a[ 'bmo_oauth_secret_key_cb' ] );
		return $a;
	}

	public function bmo_oauth_active_cb(){
		printf(
			'<input type="checkbox" id="autologin_active" name="bmo_oauth[bmo_oauth_active]" value="1" %s>',
			( $this->bmo_options->bmo_oauth_active == '1' ? 'checked="checked"' : '' )
		);
	}

	public function bmo_oauth_client_id_cb(){
		echo '<input type="text" id="client_id" name="bmo_oauth[bmo_oauth_client_id]" value="' . $this->bmo_options->bmo_oauth_client_id . '">';
	}

	public function bmo_oauth_secret_key_cb(){
		printf(
			'<input type="password" id="bmo_oauth_secret_key" size="50" name="bmo_oauth[bmo_oauth_secret_key_cb]" value="%s" />',
			isset( $this->secret_key ) ? esc_attr( $this->secret_key ) : esc_attr( 'NONE' )
		);
	}

	public function bmo_oauth_allowed_domains_cb(){
		echo '<input type="text" id="bmo_oauth_allowd_domains" name="bmo_oauth[bmo_oauth_allowd_domains]" value="' . $this->bmo_options->bmo_oauth_allowd_domains . '">';
	}

	public function render_admin_page(){
		$this->secret_key = $this->bmo_oauth_secret_key();

		echo '<div class="wrap"><H1>BMO Google OAuth2 Options</H1><form method="post" action="options.php">';
		settings_fields( $this->option_slug );
		do_settings_sections( $this->menu_slug );
		submit_button();
		echo '</form></div>';

	}


}

if( is_admin() ) $bmo_admin_options = new bmo_admin_options;
