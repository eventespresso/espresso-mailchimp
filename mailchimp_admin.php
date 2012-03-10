<?php

function espresso_mailchimp_add_to_admin_menu($espresso_manager) {
	add_submenu_page('events', __('Event Espresso - MailChimp Integration', 'event_espresso'), __('MailChimp', 'event_espresso'), 'administrator', 'espresso-mailchimp', 'event_espresso_mailchimp_settings');
}

add_action('action_hook_espresso_add_new_submenu_to_group_settings', 'espresso_mailchimp_add_to_admin_menu', 15);