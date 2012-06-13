<?php

function espresso_mailchimp_add_to_admin_menu($submenu_page_sections, $espresso_manager) {
	global $espresso_premium;
	$submenu_page_sections['mailchimp'] = array(
			($espresso_premium),
			'events',
			__('Event Espresso - MailChimp Integration', 'event_espresso'),
			__('MailChimp', 'event_espresso'),
			'administrator',
			'espresso-mailchimp',
			'event_espresso_mailchimp_settings'
	);
	return $submenu_page_sections;
}

add_filter('filter_hook_espresso_submenus_settings_section', 'espresso_mailchimp_add_to_admin_menu', 30, 2);

function espresso_register_mailchimp_meta_boxes() {
	global $espresso_premium;
	$screen = get_current_screen();
	$screen_id = $screen->id;
	switch ($screen_id) {
		case 'event-espresso_page_espresso-mailchimp':
			add_meta_box('espresso_news_post_box', __('New @ Event Espresso', 'event_espresso'), 'espresso_news_post_box', $screen_id, 'side');
			add_meta_box('espresso_links_post_box', __('Helpful Plugin Links', 'event_espresso'), 'espresso_links_post_box', $screen_id, 'side');
			if (!$espresso_premium)
				add_meta_box('espresso_sponsors_post_box', __('Sponsors', 'event_espresso'), 'espresso_sponsors_post_box', $screen_id, 'side');
			add_meta_box('event_espresso_mailchimp_settings_metabox', __('Mail Chimp Integration Settings', 'event_espresso'), 'event_espresso_mailchimp_settings_metabox', $screen_id, 'normal');
			break;
		case 'toplevel_page_events':
			add_meta_box('espresso_event_editor_mailchimp', __('MailChimp List Integration', 'event_espresso'), 'MailChimpView::event_list_selection', 'toplevel_page_events', 'side', 'default');
			break;
	}
}

add_action('admin_head', 'espresso_register_mailchimp_meta_boxes', 80);

function event_espresso_mailchimp_settings_metabox() {
	?>
	<div style="padding: 10px;">
	<?php
	if (isset($_REQUEST["update_mailchimp_settings_post"])) {
		$process = MailChimpController::update_mailchimp_settings();
		if (MailChimpController::mailchimp_is_error($process)) {
			MailChimpView::configuration($process);
		} else {
			MailChimpView::configuration(null, 1);
		}
	} else {
		MailChimpView::configuration();
	}
	?>
	</div>
		<?php
	}

	function event_espresso_mailchimp_settings() {
		ob_start();
		do_meta_boxes('event-espresso_page_espresso-mailchimp', 'side', null);
		$sidebar_content = ob_get_clean();
		ob_start();
		do_meta_boxes('event-espresso_page_espresso-mailchimp', 'normal', null);
		$main_post_content = ob_get_clean();
		?>
	<div id="event_reg_theme" class="wrap">
		<div id="icon-options-event" class="icon32"></div>
		<h2><?php echo _e('Manage MailChimp Integration Settings', 'event_espresso'); ?></h2>
	<?php
	if (!espresso_choose_layout($main_post_content, $sidebar_content))
		return FALSE;
	?>
	</div>
		<?php
	}