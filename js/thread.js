var sp_tickers = new Array();


// triggered by mouse movement, keypress etc.
// send an ajax notification that we're active in this thread
function sp_tick( action ) {
	return;
	action = action || 'idle';
	var now = new Date();
	var last_tick = sp_tickers[ action ];
	if ( !last_tick ) last_tick = 0;

	var delta = now.getTime() - last_tick;

	// 30 seconds since the last ping?
	if ( delta > 30000 ) {
		sp_tickers[ action ] = now.getTime();
		var thread_id = $('#threadstatus input[name=thread_id]').val();

		// trigger a heartbeat event; plugins can bind('heartbeat') to receive it
		$.getJSON('heartbeat.php', { thread_id: thread_id, delta: delta, action: action }, function(r) { $('.sp-heartbeat').trigger('heartbeat', [r]); } );
	}

}

$(document).ready( function() {

	// disallow clicking the enter button to submit a reply form accidentally
	$('input,select').keypress( function(event) {
		return event.keyCode != 13;
	} );

  $('.notify-button').click( function() { $('.notify-users').toggle(); } );
  $('.inlinereply').click( function() { $('.reply').removeClass('replying'); $('#' + this.name ).toggle(); $('#' + this.name + ' .reply').addClass('replying').eq(0).focus(); } );
  $('p').click( function() { $('.lastclicked').attr( 'value', this.parentNode.id ); } );
  $('input.notify-checkbox').click( function() { if ( $('input.notify-checkbox:checked').length > 0 ) $('.notify-toggle').show(); else $('.notify-toggle').hide(); } );
  	$('select.predefined_message').change( function() {
  		if ( $(this).val() > 0 ) {
  			$.getJSON('ajax-predefined.php', { id: $(this).val(), user_id: $('.user_id').val(), thread_id: $('input[name=thread_id]').val() }, function(data) {
  				$('textarea.replying').replaceSelection( $('<div/>').text(data.message).html() + '\n\n' );
  				$('.title.replying').val( data.title );
  				$('.tag.replying').val( data.tag );
  			} );
  		}
  	} );
  
  $('.message-toggle').click( function() { $(this).parents('.message').find('.mainpart').toggle(); } );
  // the 'read' activity signals that the user is scrolling or mousing around the page, ie they seem to be actively reading the thread
  $(document).mousemove( function(e) { sp_tick( 'read' ); } );
  $(document).keyup( function(e ) { sp_tick( 'read' ); } );
  // 'reply' means they're writing a reply
  $('textarea.reply').focus( function(e) { sp_tick( 'reply' ); } );
  $('textarea.reply').keyup( function(e) { sp_tick( 'reply' ); } );
  
  // check validity of email addresses
  $('input[name="to_email"], input[name="cc"], input[name="bcc"]').blur( function(e) {
    var field = this.name;
    var error_span = $(this).parent().children('span#email_error');
    var contents = $('input[name="' + field + '"]').val();  
    var emailArray = (contents.indexOf(',') > -1) ? contents.split(',') : contents.split();
  
    var re = /\S+@\S+\.\S+/;
    
    if ( !contents || contents.length === 0 ) {
      error_span.text("");
      
      return;
    }
  
    var validity = true;
    $.each(emailArray, function() {
      validity = re.test(this);  
  
      if ( !validity ) {
        return false;
      }
    });
    
    if ( !validity ) {
      error_span.text("Email address entered is invalid."); 
    } else {
      error_span.text(""); 
    }
  });
  
  // disable send button if fields empty
  //var submit = $('input[name="send"], input[name="sendtickle"], input[name="sendclose"]');
  //submit.attr('disabled','disabled');
  
  if ( $(location).attr('href').indexOf("thread-new.php") >= 0 ) {
    $('input[name="to_email"], input[name="subject"], textarea[name="message"]').blur( function(e) {
      var empty = false;
  
      if ( !$('input[name="to_email"]').val() || !$('input[name="subject"]').val() || !$('textarea[name="message"]').val() ) {
        empty = true;
      }
      
      if (empty) {
        //submit.attr('disabled','disabled');
      } else {
        //submit.removeAttr('disabled');
      }
    });
  } else if ( $(location).attr('href').indexOf("thread.php") >= 0 ) {
    $('input[name="to_email"], textarea[name="message_reply"]').blur( function(e) {
      var empty = false;
  
      if ( !$('input[name="to_email"]').val() || !$('textarea[name="message_reply"]').val() ) {
        empty = true;
      }
      
      if (empty) {
        //submit.attr('disabled','disabled');
      } else {
        //submit.removeAttr('disabled');
      }
    });
  }
  
  // default 'idle' means they have the page open but don't seem to be doing anything
  setInterval( sp_tick, 60000 );
  // initial tick event
  sp_tick();
  
	// Grab the Tags update process so that we can update
	// without having to reload the whole page
	var $tags_form = $( '#newtags' );
	var $tags_submit = $tags_form.find( 'input:submit' );
	$tags_submit.click( function() {
		var $tags_field = $tags_form.find( 'input[name="tags"]' );

		// grab the form data
		var thread_id = $tags_form.find( 'input[name="thread_id"]' ).val();
		var tags = $tags_field.val();

		$.ajax( {
			url: 'thread-tags.php',
			type: 'post',
			dataType: 'json',
			data: { thread_id: thread_id, tags: tags, is_ajax: 1 },
			beforeSend: function() {
				// disable the form elements,
				// prevent user from multiple submits, etc.
				$tags_submit.attr( "disabled", "disabled" );
				$tags_field.attr( "disabled", "disabled" );

				// colorize the tag form a bit
				$tags_form.addClass( 'submit-tag-update' );
			},
			success: function ( resp ) {
				if ( resp.is_error ) {
					alert( 'Failed to save tags.' );
				}
			},
			complete: function() {
				$tags_submit.removeAttr( "disabled" );
				$tags_field.removeAttr( "disabled" );

				$tags_form.removeClass( 'submit-tag-update' );

				// take the focus off of the tag form field
				$tags_field.blur();
			}
		} );

		// prevent the default form submit button action
		return false;
	} );

	// Grab the status update process so we can update
	// without having to reload the whole page
	var $status_form = $( '#statusform' );
	var $status_submit = $status_form.find( 'button' );
	$status_submit.click( function() {
		var $status_field = $status_form.find( 'input[name="status"]' );

		// grab the form data
		var thread_id = $status_form.find( 'input[name="thread_id"]' ).val();
		var status = $status_field.val();

		$.ajax( {
			url: 'thread-status.php',
			type: 'post',
			dataType: 'json',
			data: { thread_id: thread_id, status: status, is_ajax: 1 },
			beforeSend: function() {
				$status_submit.attr( "disabled", "disabled" );
				$status_form.addClass( 'submit-status-update' );
			},
			success: function( resp ) {

				if ( resp.is_error ) {
					alert( 'Failed to update status.' );
				}

				if ( resp.redirect_url ) {
					// On close redirect to user preferred location (inbox, or next ticket)
					window.location.href = resp.redirect_url;
				} else {
					// Reopen ticket
					$status_field.val( status );
					if ( status == 'open' ) {
						$status_submit.html( 'Close' );
						$status_field.val( 'closed' );
					}
				}
			},
			complete: function() {
				$status_submit.removeAttr( "disabled" );
				$status_form.removeClass( 'submit-status-update' );

				$status_submit.blur();
			}
		} );

		// prevent the default form submit button action
		return false;
	} );
	// autoscroll textarea's to bottom
	var ta = $('.n1 textarea'),
		v = ta.val();

	ta.focus().val('').val(v);
	// BCC CC link show
	$('.cc-toggle').click(function () {
		$('.cc-toggle').hide();
		$('.cc-field').show();
	});
});


var _validFileExtensions = [ '.gif', '.pdf', '.jpg', '.jpeg', '.png', '.xls', '.doc', '.html', '.zip' ];

function validate_upload( oForm ) {

	var sFileName = $( '#attachment' ).val();
	if ( sFileName.length > 0 ) {
		var blnValid = false;
		for (var j = 0; j < _validFileExtensions.length; j++) {
			var sCurExtension = _validFileExtensions[j];
			if (sFileName.substr(sFileName.length - sCurExtension.length, sCurExtension.length).toLowerCase() == sCurExtension.toLowerCase()) {
				blnValid = true;
				break;
			}
		}

		if ( !blnValid ) {
			alert( "Sorry, " + sFileName + " is an invalid attachment, allowed extensions are: " + _validFileExtensions.join(", ") );
			$( '#attachment' ).val( '' );
			return false;
		}
	}
	return true;
}