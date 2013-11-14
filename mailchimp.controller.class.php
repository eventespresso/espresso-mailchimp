<?php
class MailChimpController {

	function __construct( ) {
		do_action( 'event_espress_mailchimp_controller_init', $this );
	}
	/**
     * Processes the MailChimp API Key request found at Event Espresso -> MailChimp Integration
     * No parameters are required.  
     *
     * @return array on error.  Typical errors include: invalid API Key, or No API Key provided.  On Success, MailChimp Integration settings are updated
     * with the new API key.
     */
	function update_mailchimp_settings( ) {
		//configuration variable in options - event_mailchimp_settings
		//check that the API key was provided.  If not, return with error a14ef9f5c44342442a3a18f89b46149e-us2
		$apikey = trim( $_REQUEST["mailchimp_api_key"] );
		if ( $apikey == "" ) {
			return array( "An API Key is Required." );
		}
		//test the API to make certain it is valid
		$api = new MCAPI( $apikey, 1 );
		$api->lists( );
		
		if ( $api->errorCode != "" ) {
			return array( "An error occurred while attempting to connect to the MailChimp server.  
				Error: $api->errorMessage" );
		}
		//now that we know the API key is valid, enter it into the system for future use.
		$mailchimp_api_settings['apikey'] = $apikey;
		do_action( 'event_espresso_mailchimp_update_mailchimp_settings', $api, $mailchimp_api_settings );
		update_option( 'event_mailchimp_settings', $mailchimp_api_settings );
	}
	
	/**
     * Requests a valid MailChimp API key.  Looks up the event_mailchimp_settings apiKey option, then tests the provided API key against
     * the MailChimp Servers.
     *
     * No parameters are required.
     *
     * @return string containing the API key on success.  On Error, return array with the corresponding error message.
     * 
     */
	function get_valid_mailchimp_key( ) {
		//check to make sure this is not the initial configuration
		$settings = get_option( "event_mailchimp_settings" ); 
		if ( ! empty( $settings["apikey"] ) ) {
			$api = new MCAPI( $settings["apikey"], 1 );
			$api->lists( );
			//if the current key is no longer valid, reset the key to null and return an error to the user, requesting a new key.  Otherwise
			//return the current key.
			if ( $api->errorCode !== "" ) {
				update_option( "event_mailchimp_settings", array( "apikey","" ) );
				return array( "The API Key previously used ({$settings["apikey"]}) is no longer valid.  
					Please enter a new API key." );
			} else {
				return $settings["apikey"];
			}
			//if the key is currently null, then this is the initial configuration, return nothing.
		}
	}
	
	/**
     * Retrieves the MailChimp API key with the get_valid_mailchimp_key function above, then retrieves the MailChimp lists associated with the retrieved Key.
     *
     * No parameters are required.
     *
     * @return string containing a select box with all MailChimp Lists associated with the API key.  
     * If the API key is no longer valid, return nothing.  This avoids an empty MailChimp List Integration option within the Add / Edit Event dialogs.
     * 
     */
	function get_lists( ) {
		global $wpdb;
		$key = MailChimpController::get_valid_mailchimp_key( );
		$listSelection = null;
		$currentMailChimpID = null;
		if ( ! is_array( $key ) && ! empty( $key ) ) {
			//if the user is editing an existing event, get the previously selected MailChimp List ID
			if ( $_REQUEST["action"] == "edit" && $_REQUEST["page"] == "events" ) {
				$sql = apply_filters( 'event_espresso_mailchimp_get_existing_list_event', 
					$wpdb->prepare( "SELECT mailchimp_list_id FROM " . EVENTS_MAILCHIMP_EVENT_REL_TABLE ." 
						WHERE event_id=%d", $_REQUEST["event_id"] 
					) 
				);
				$MailChimpListID = $wpdb->get_var( $sql );
			}

			$api = new MCAPI( $key, 1 );
			$lists = $api->lists( );

			$listSelection = "<label for='mailchimp-lists'>Select an available list " . apply_filters('espresso_help', 'mailchimp-list-integration') . "</label><select id='mailchimp-lists' name='mailchimp_list_id'>";
			$listSelection .= "<option value='0'>Do not send to MailChimp</option>";
			
			foreach( $lists["data"] as $listVars ) {
				$selected = ( $listVars["id"] == $MailChimpListID ) ? " selected" : "";
				$listSelection .= "<option value='{$listVars["id"]}'$selected>{$listVars["name"]}</option>";
			}
			$listSelection .= "</select>";
		}
		return $listSelection;
	}
	
	public function get_groups( $listid = false ) {
        global $wpdb;
        MailChimpController::ensure_group_row_table();
        $echoit = ( $listid ) ? false : true;
        $listid = ( $listid ) ? $listid : $_POST['mailchimp_list_id'];
		if ( $listid === 0 )
			wp_die( false );
		$settings = get_option( "event_mailchimp_settings" ); 
		$key = $settings["apikey"];
		
		$groupSelection = null;
		$currentMailChimpID = null;
		if ( ! is_array( $key ) && ! empty( $key ) ) {
			if ( isset( $_REQUEST['event_id'] ) ):
				$sql = apply_filters( 'event_espresso_mailchimp_get_existing_group_event', 
						$wpdb->prepare( "SELECT mailchimp_group_id FROM " . EVENTS_MAILCHIMP_EVENT_REL_TABLE ." 
							WHERE event_id=%d", $_REQUEST["event_id"] 
						) 
					);
	        	$MailChimpGroupID = $wpdb->get_var( $sql );   
			else:
            	$MailChimpGroupID = false;
            endif;
			$api = new MCAPI( $key, 1 );
			$groups = $api->listInterestGroupings( $listid );

			if( $groups && count( $groups > 0 ) ):
				$groupSelection="<select name='mailchimp_group_id'>";
				$groupSelection.="<option value='0'>Do not send to MailChimp group</option>";
				foreach ( $groups as $group ) {
					$groupSelection .= '<optgroup label="'.$group['name'].'">';
					foreach ( $group['groups'] as $listVars ) {
						$groupid = $listVars["bit"] . '-' . $group["id"] .'-' . base64_encode( $listVars['name'] );
						$selected = ( $groupid == $MailChimpGroupID ) ? " selected='selected'":"";
						$groupSelection .= "<option value='$groupid'$selected>{$listVars["name"]}</option>";
					}
					$groupSelection .= '</optgroup>';
				}
				$groupSelection.="</select>";
			endif;
		}

		if ( $echoit ) {
            echo $groupSelection;
            wp_die( false );
        } else {
            return $groupSelection;
        }
    }
	
	/**
     * Looks up the MailChimp List ID by the Event Espresso Event ID, within the events_mailchimp_event_rel table.  
     *
     * @param string $event_id
     *
     * @return string containing the MailChimp List ID associated with the corresponding event ID.  
     * if no MailChimp List ID is associated with the event ID, return boolean false.
     * 
     */
	function get_mailchimp_list_id_by_event( $event_id ) {
		global $wpdb;
		$sql = apply_filters( 'event_espresso_mailchimp_get_list_id_by_event_sql', 
			$wpdb->prepare( "SELECT mailchimp_list_id, mailchimp_group_id FROM " . EVENTS_MAILCHIMP_EVENT_REL_TABLE ." 
				WHERE event_id=%d", $event_id 
			) 
		);
		$relationship = $wpdb->get_row( $sql, ARRAY_A );
		
		return ( ! empty( $relationship ) ) ? $relationship : false;
		 
	}
	
	/**
     * Adds an Event ID / MailChimp List ID relationshiop within the events_mailchimp_event_rel table
     *
     * @param string $event_id
     * 
     */
	function add_event_list_rel( $event_id ) {
		global $wpdb;
		$sql= apply_filters( 
			'event_espresso_mailchimp_add_event_list_rel_insert_array', 
			array(
				"event_id" => $event_id,
				"mailchimp_list_id" => $_REQUEST["mailchimp_list_id"],
				"mailchimp_group_id" => $_REQUEST["mailchimp_group_id"]
			)
		);
		$wpdb->insert( EVENTS_MAILCHIMP_EVENT_REL_TABLE, $sql );
	}
	
	/**
     * updates an Event ID / MailChimp List ID relationship within the events_mailchimp_event_rel table.  If no relationshp currently exists
     * A new relationship is created.
     *
     * @param string $event_id
     * 
     */
	function update_event_list_rel( $event_id ) {
		global $wpdb;
		MailChimpController::ensure_group_row_table();
		do_action( 'event_espresso_mailchimp_update_event_list_rel', $event_id, $_REQUEST );
		//first, make sure a list relationship exists within the system.
		//if a relationship exists, update it.  Otherwise, create the relationship anew.
		$sql = $wpdb->prepare( "SELECT event_id FROM " . EVENTS_MAILCHIMP_EVENT_REL_TABLE . "
			WHERE event_id = %d", $event_id );
		$currentListRelationship = $wpdb->get_row( $sql, ARRAY_A );
		if ( ! empty( $currentListRelationship ) ) {
			$data = apply_filters( 
				'event_espresso_mailchimp_update_event_list_rel_data',
				array("mailchimp_list_id" => $_REQUEST["mailchimp_list_id"],
				"mailchimp_group_id" => $_REQUEST["mailchimp_group_id"] )
			);
			$where = apply_filters( 
				'event_espresso_mailchimp_update_event_list_rel_where',
				array( "event_id" => $event_id )
			);
			$wpdb->update( EVENTS_MAILCHIMP_EVENT_REL_TABLE, $data, $where );
		}else{
			MailChimpController::add_event_list_rel( $event_id );
		}
	}
	
    /**
     * Subscribes new attendees to the MailChimp List associated with the corresponding Event.  Upon successful subscription, adds an attendee_id to event_id
     * relationship for possible backward integration.
     *
     * @param string $event_id, Event Espresso event id
     * @param string $attendee_id, Event Espresso new Attendee ID
     * @param string $attendee_fname Event Espresso new Attendee First Name
     * @param string $attendee_lname Event Espresso new Attendee Last Name
     * @param string $attendee_email Event Espresso new Attendee Email Address
     * 
     */
	function list_subscribe( $event_id, $attendee_id, $attendee_fname, $attendee_lname, $attendee_email ) {
		global $wpdb;
		$mailChimpListID = MailChimpController::get_mailchimp_list_id_by_event( $event_id );
		//check to make sure the list ID is valid and available
		if ( $mailChimpListID ) {
			$mailChimpKey=MailChimpController::get_valid_mailchimp_key( );
			//make certain the key is still valid with the MailChimp Servers
			if ( ! is_array( $mailChimpKey ) && ! empty( $mailChimpKey ) ) {
				$api = new MCAPI( $mailChimpKey );
				apply_filters( 'event_espresso_mailchimp_list_subscribe_merge_vars', 
					$merge_vars = array( 
						"FNAME" => $attendee_fname,
						"LNAME" => $attendee_lname 
					),
					$event_id,
					$mailChimpListID,
					$api 
				);
				
				$groups_data = explode( '-', $mailChimpListID['mailchimp_group_id'] );
				if( 1 < count( $groups_data ) ) {
					$merge_vars["GROUPINGS"] = array(
			            array( "id" => $groups_data[1], "groups" => base64_decode( $groups_data[2] ) )
			        );
				}
				
				//subscribe the attendee to the selected MailChimp list
				$api->listSubscribe( $mailChimpListID['mailchimp_list_id'], $attendee_email, $merge_vars );
				do_action( 
					'event_espresso_mailchimp_list_subscribe',
					$event_id, 
					$attendee_id, 
					$mailChimpListID, 
					$api 
				);

				// if the user exists subscribe will fail, add group to existing subscription
				if ( $api->errorCode = '214' && 1 < count( $groups_data ) ) {
					$merge_vars["GROUPINGS"] = array(
			       				array( 
								"id" => $groups_data[1],
								"groups" => base64_decode( $groups_data[2] )
								)
							);
					$api->listUpdateMember( $mailChimpListID['mailchimp_list_id'], $attendee_email, $merge_vars, 'html', false );
					
				}
			
				if ( $api->errorCode == "" ) {
					//now create an attendee / mailchimp list relationshp for future backward integration
					$sql = apply_filters( 'event_espresso_mailchimp_list_subscribe_insert', 
						array( 
							"event_id" => $event_id,
							"attendee_id" => $attendee_id,
							"mailchimp_list_id" => $mailChimpListID['mailchimp_list_id']
						)
					);
					$wpdb->insert( EVENTS_MAILCHIMP_ATTENDEE_REL_TABLE, $sql );
				}
			}
		}
	}
	
   /**
	* Basic function designed to determine if a process is in error. The MailChimp integration routines will provide an array if the process is in error
	*
	* @param mixed $process
	*
	* @return boolean true if $process is an array.  boolean false if $process is not an array.
	*/
	function mailchimp_is_error( $process ) {
		if ( is_array( $process ) ) 
			return true;
		return false;
	}

	protected function ensure_group_row_table(){
		if( get_option( 'ee-mailchimp-group_id_set' ) )
			return;
		global $wpdb;
		
		$sql = "SHOW COLUMNS FROM {$wpdb->prefix}events_mailchimp_event_rel LIKE 'mailchimp_group_id';";
		$test = $wpdb->query($sql);
		if (empty($test)) {
			$wpdb->query( "ALTER TABLE {$wpdb->prefix}events_mailchimp_event_rel ADD column mailchimp_group_id VARCHAR(255) NULL DEFAULT NULL AFTER event_id" );
			update_option( 'ee-mailchimp-group_id_set', true );
		}else{
			update_option( 'ee-mailchimp-group_id_set', true );
		}
	}
}
