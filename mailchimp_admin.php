<?php

function espresso_mailchimp_add_to_admin_menu($espresso_manager) {
	add_submenu_page('events', __('Event Espresso - MailChimp Integration', 'event_espresso'), __('MailChimp', 'event_espresso'), 'administrator', 'espresso-mailchimp', 'event_espresso_mailchimp_settings');
}

add_action('action_hook_espresso_add_new_submenu_to_group_settings', 'espresso_mailchimp_add_to_admin_menu', 15);



function espresso_register_mailchimp_meta_boxes() {
	if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'edit') {
		$screen = get_current_screen();
		if ($screen->id == 'toplevel_page_events') {
			add_meta_box('espresso_event_editor_mailchimp', __('MailChimp List Integration', 'event_espresso'), 'MailChimpView::event_list_selection', 'toplevel_page_events', 'side', 'default');
		}
	}
}

add_action('current_screen', 'espresso_register_mailchimp_meta_boxes');