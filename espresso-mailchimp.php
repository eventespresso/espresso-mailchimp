<?php

/*
  Plugin Name: Event Espresso - MailChimp Integration
  Plugin URI: http://www.eventespresso.com
  Description: A Mail Chimp integration addon for Event Espresso.
  Version: 1.1
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

/* Changelog
  1.0.1
  fixed some php shorttags, missing colons and a missing ?>
  ~c
 */
require_once("MCAPI.class.php"); //MailChimp API wrapper
require_once("mailchimp.controller.class.php"); //WP integration routines
require_once("mailchimp.view.class.php"); //Display routines for the mailchimp integration

if (is_admin()) {
	require_once('mailchimp_admin.php');
}

function event_espresso_mailchimp_install() {
	//Create a MailChimp / Attendee relationship table
	$table_name = "events_mailchimp_attendee_rel";
	$table_version = "1.0.1";
	$sql = "id int(11) NOT NULL AUTO_INCREMENT,
	event_id INT(11) DEFAULT NULL,
	attendee_id INT(11) DEFAULT NULL,
	mailchimp_group_id VARCHAR(255) DEFAULT NULL,
	mailchimp_list_id VARCHAR(75) DEFAULT NULL,
	PRIMARY KEY (id)
	";
	event_espresso_run_install($table_name, $table_version, $sql);

	//Create a MailChimp / Event Relationship Table
	$table_name = "events_mailchimp_event_rel";
	$table_version = "1.0";
	$sql = "id int(11) NOT NULL AUTO_INCREMENT,
	event_id INT(11) DEFAULT NULL,
	mailchimp_list_id VARCHAR(75) DEFAULT NULL,
    mailchimp_group_id VARCHAR(255) DEFAULT NULL,
	PRIMARY KEY (id)
	";
	event_espresso_run_install($table_name, $table_version, $sql);

	//run install routines, setup basic Integration variables within the options environment.
	add_option("event_mailchimp_active", "true", "", "yes");
	update_option("event_mailchimp_active", "true");
	add_option("event_mailchimp_settings", "", "", "yes");
}

function event_espresso_mailchimp_deactivate() {
	update_option("event_mailchimp_active", "false"); //set the activation flag to false
	update_option('event_mailchimp_settings', ""); //reset the API key to null.
}

//register basic activation / deactivation hooks for the MailChimp Integration
register_activation_hook(__FILE__, "event_espresso_mailchimp_install");
register_deactivation_hook(__FILE__, "event_espresso_mailchimp_deactivate");

//define some basic variables for the system.
define("EVENTS_MAILCHIMP_ATTENDEE_REL_TABLE", get_option('events_mailchimp_attendee_rel_tbl'));
define("EVENTS_MAILCHIMP_EVENT_REL_TABLE", get_option('events_mailchimp_event_rel_tbl'));
define("EVENT_MAILCHIMP_PLUGINPATH", "/" . plugin_basename(dirname(__FILE__)) . "/");

function espresso_mailchimp_enqueue_scripts() {
	//wp_enqueue_script('ee-mailchimp-group', plugins_url('js/ajax-mailchimp.js', __FILE__), array('jquery'));
}

add_action('init', 'espresso_mailchimp_enqueue_scripts');
$mcconnic = new MailChimpController();
add_action('wp_ajax_change-group', array($mcconnic, 'get_groups'));
add_action('wp_ajax_nopriv_change-group', array($mcconnic, 'get_groups'));