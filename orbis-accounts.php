<?php
/*
Plugin Name: Orbis Accounts
Plugin URI: https://www.pronamic.eu/plugins/orbis-accounts/
Description:

Version: 1.0.0
Requires at least: 3.5

Author: Pronamic
Author URI: https://www.pronamic.eu/

Text Domain: orbis_accounts
Domain Path: /languages/

License: Copyright (c) Pronamic

GitHub URI: https://github.com/wp-orbis/wp-orbis-accounts
*/

function orbis_accounts_bootstrap() {
	// Classes
	require_once 'classes/orbis-accounts-plugin.php';

	// Initialize
	global $orbis_accounts_plugin;

	$orbis_accounts_plugin = new Orbis_Accounts_Plugin( __FILE__ );
}

add_action( 'orbis_bootstrap', 'orbis_accounts_bootstrap' );
