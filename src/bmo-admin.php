<?php

if( ! defined( 'WPINC' ) ){
	die;
}


class bmo_admin_options extends bmo_google_oauth {

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
			[ $this, 'bmo_sanitize' ] //$sanistize_callback
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
			'force_login', //$id
			'Force Login Everywhere?', // $title
			[ $this, 'bmo_oauth_force_login_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'client_id', //$id
			'Oauth Client ID', // $title
			[ $this, 'bmo_oauth_client_id_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'project_id', //$id
			'Oauth Project ID', // $title
			[ $this, 'bmo_oauth_project_id_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'client_secret', //$id
			'OAuth Secret Key', // $title
			[ $this, 'bmo_oauth_secret_key_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
		add_settings_field(
			'bmo_oauth_allowed_domains', //$id
			'Allowed Domains', // $title
			[ $this, 'bmo_oauth_allowed_domains_cb' ], //$callback
			$this->menu_slug, //$page
			$this->section_slug // $section
		);
	}

	public function bmo_sanitize( $a ){
		if( is_array( $a ) && isset( $a[ 'client_secret' ] ) ) $a[ 'client_secret' ] = $this->key_encrypt( $a[ 'client_secret' ] );
		return $a;
	}

	public function bmo_oauth_active_cb(){
		printf(
			'<input type="checkbox" id="autologin_active" name="bmo_oauth[bmo_oauth_active]" value="1" %s>',
			( $this->bmo_options->bmo_oauth_active == '1' ? 'checked="checked"' : '' )
		);
	}
	public function bmo_oauth_force_login_cb(){
		printf(
			'<input type="checkbox" id="force_login" name="bmo_oauth[force_login]" value="1" %s>',
			( $this->bmo_options->force_login == '1' ? 'checked="checked"' : '' )
		);
	}

	public function bmo_oauth_project_id_cb(){
		echo '<input type="text" id="project_id" size="100" name="bmo_oauth[project_id]" value="' . $this->bmo_options->project_id . '">';
	}
	public function bmo_oauth_client_id_cb(){
		echo '<input type="text" id="client_id" size="100" name="bmo_oauth[client_id]" value="' . $this->bmo_options->client_id . '">';
	}

	public function bmo_oauth_secret_key_cb(){
		printf(
			'<input type="text" id="client_secret" size="100" name="bmo_oauth[client_secret]" value="%s" />',
			isset( $this->bmo_options->client_secret ) ? esc_attr( $this->key_decrypt( $this->bmo_options->client_secret ) ) : esc_attr( 'NONE' )
		);
	}

	public function bmo_oauth_allowed_domains_cb(){
		echo '<input type="text" id="bmo_oauth_allowed_domains" name="bmo_oauth[bmo_oauth_allowed_domains]" value="' . $this->bmo_options->bmo_oauth_allowed_domains . '">';
	}

	public function render_admin_page(){

		echo '<div class="wrap"><H1>BMO Google OAuth2 Options</H1><form method="post" action="options.php">';
		settings_fields( $this->option_slug );
		do_settings_sections( $this->menu_slug );
		submit_button();
		echo '</form></div>';

	}


}

if( is_admin() ){
	$bmo_admin_options = new bmo_admin_options;
	add_action( 'admin_menu', [ $bmo_admin_options, 'add_admin_page' ] );
	add_action( 'admin_init', [ $bmo_admin_options, 'register_settings' ] );
}
