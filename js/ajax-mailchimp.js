jQuery( 'document' ).ready(function( $ ){
    
    if( $( '#mc_listid :selected' ) ){
        var selected = $( '#mc_listid :selected' ).val();
        var eventid = $( 'input[name="event_id"]' ).val();
        display_mailchimp_group( selected, eventid );
    }
    
    $( '#mc_listid' ).change( function(){
         var list = $( this ).val();
         var eventid = $( 'input[name="event_id"]' ).val();
         display_mailchimp_group( list );
    } );
    
    function display_mailchimp_group( list, eventid ){
        $.post(EEGlobals.ajaxurl,{
			'action' : 'change-group',
			'mailchimp_list_id' : list,
            'event_id' : eventid
		}, function(response) {
            $('#mailchimp-groups').html(response);
            $('#mailchimp-groups').show();
		}, 'text' );
    }
});