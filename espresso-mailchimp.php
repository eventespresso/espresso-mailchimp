<?php
/**
Plugin Name: Event Espresso - MailChimp Integration
Plugin URI: http://www.eventespresso.com
Description: A MailChimp integration addon for Event Espresso.
Version: 1.2
Usage: Configure the MailChimp API credentials under Event Espresso -> MailChimp integration.  When creating/updating an event, select the Mail Chimp list you would like to integrate with.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

//Update notifications
add_action( 'action_hook_espresso_mailchimp_update_api', 'ee_mailchimp_load_pue_update' );
function ee_mailchimp_load_pue_update( ) {
	global $org_options, $espresso_check_for_updates;
	if ( $espresso_check_for_updates == false )
		return;
		
	if ( file_exists( EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php' ) ) { //include the file 
		require( EVENT_ESPRESSO_PLUGINFULLPATH . 'class/pue/pue-client.php' );
		$api_key = $org_options['site_license_key'];
		$host_server_url = 'http://eventespresso.com';
		$plugin_slug = array(
			'premium' => array('p' => 'espresso-mailchimp'),
			'prerelease' => array('b' => 'espresso-mailchimp-pr')
			);
		$options = array(
			'apikey' 			=> $api_key,
			'lang_domain' 		=> 'event_espresso',
			'checkPeriod' 		=> '24',
			'option_key' 		=> 'site_license_key',
			'options_page_slug' => 'event_espresso',
			'plugin_basename' => plugin_basename(__FILE__),
			'use_wp_update' => FALSE, //if TRUE then you want FREE versions of the plugin to be updated from WP
		);
		do_action( 'event_espresso_mailchimp_pre_update_check' );
		$check_for_updates = new PluginUpdateEngineChecker( $host_server_url, $plugin_slug, $options ); //initiate the class and start the plugin update engine!
	}
}

require_once( "MCAPI.class.php" ); //MailChimp API wrapper
require_once( "mailchimp.model.class.php" ); //integration logic
require_once( "mailchimp.controller.class.php" ); //WP integration routines
require_once( "mailchimp.view.class.php" ); //Display routines for the mailchimp integration


function event_espresso_mailchimp_install( ) {
	//Create a MailChimp / Attendee relationship table
	$table_name="events_mailchimp_attendee_rel";
	$table_version = "1.2";
	
	if ( ! function_exists( 'event_espresso_run_install' )) {
		require_once( EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/functions/database_install.php' ); 		
	}
	
	$sql= apply_filters( 'events_mailchimp_attendee_rel_sql', 
		"id int(11) NOT NULL AUTO_INCREMENT,
		event_id INT(11) DEFAULT NULL,
		attendee_id INT(11) DEFAULT NULL,
		mailchimp_list_id VARCHAR(75) DEFAULT NULL,
		PRIMARY KEY  (id)"
	);
	event_espresso_run_install( $table_name, $table_version, $sql );

	//Create a MailChimp / Event Relationship Table
	$table_name = "events_mailchimp_event_rel";
	$table_version = "1.2";
	$sql = apply_filters( 'events_mailchimp_event_rel_sql', 
		"id int(11) NOT NULL AUTO_INCREMENT,
		event_id INT(11) DEFAULT NULL,
		mailchimp_list_id VARCHAR(75) DEFAULT NULL,
		mailchimp_group_id VARCHAR(255) DEFAULT NULL,
		PRIMARY KEY  (id)"
	);
	event_espresso_run_install( $table_name, $table_version, $sql );

	//run install routines, setup basic Integration variables within the options environment.
	add_option( "event_mailchimp_active", "true", "", "yes" );
	update_option( "event_mailchimp_active", "true" );
	//update_option( 'ee-mailchimp-group_id_set', true );
	
	// commented out below: when activating, this removes the API key already entered
	// this happens when WordPress updates, deactivates and re-activates the plugin as well.
	//add_option("event_mailchimp_settings","","","yes");
	do_action( 'event_espresso_mailchimp_post_install'  );
}

function event_espresso_mailchimp_deactivate( ) {
	update_option( "event_mailchimp_active","false" ); //set the activation flag to false

	// commented out below: when activating, this removes the API key already entered
	// this happens when WordPress updates, deactivates and re-activates the plugin as well.
	//update_option('event_mailchimp_settings', ""); //reset the API key to null.
	
	do_action( 'event_espresso_mailchimp_post_deactivate'  );
}

//register basic activation / deactivation hooks for the MailChimp Integration
register_activation_hook( __FILE__, "event_espresso_mailchimp_install" );
register_deactivation_hook( __FILE__, "event_espresso_mailchimp_deactivate" );

//define some basic variables for the system.
global $wpdb;
define( "EVENTS_MAILCHIMP_ATTENDEE_REL_TABLE", $wpdb->prefix."events_mailchimp_attendee_rel" );
define( "EVENTS_MAILCHIMP_EVENT_REL_TABLE", $wpdb->prefix."events_mailchimp_event_rel" );
define( "EVENT_MAILCHIMP_PLUGINPATH", "/" . plugin_basename( dirname( '__FILENAME__' ) ) . "/" );

// ajax to display groups when list is changed
add_action( 'wp_ajax_change-group', array( 'MailChimpController', 'get_groups' ) );
