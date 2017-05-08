<?php

/*
Plugin Name: BMO Google OAuth2
Description: Google OAuth2 Plugin
Version: 0.1
Author: BMO ^_^
*/

require_once 'google-api-php-client/vendor/autoload.php'; //Require Google API PHP Client

/***** The Master Class to Rule them All *****/
class bmo_google_oath {

	public function __contsruct(){

	}

}

/** Updater Class only needs to be available in wp-admin **/
if( is_admin() ){
	require_once 'src/bmo-update.php';
	new bmo_plugin_updater( __FILE__, 'SkyPressATX', 'bmo-google-oath2' );
}
