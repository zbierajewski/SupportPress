$(document).ready(function(){
	// show/hide text tip contents
	$('input.tiptext').addClass('has-tiptext');
	$('input.tiptext').focus( function() { if (!this.cleared) { this.cleared=true; this.tip=this.value; $(this).val('').removeClass('has-tiptext'); } } );
	$('input.tiptext').blur( function() { if (!this.value) { this.cleared=false; $(this).val(this.tip).addClass('has-tiptext'); } } );

	// remove tip contents from inputs when form is submitted
	$('form').submit( function() { $('input.has-tiptext').val(''); } );

	$('.whenselected').toggle( $('.mcheck:checked').size() > 0 );
	$('.mcheck').change( function() { $('.whenselected').toggle( $('.mcheck:checked').size() > 0 ); } );

	$('.enablewhenselected').attr( 'disabled', $('.mcheck:checked').size() > 0 ? false : 'disabled' );
	$('.mcheck').change( function() { $('.enablewhenselected').attr( 'disabled', $('.mcheck:checked').size() > 0 ? false : 'disabled' ); } );

	// load the real gravatars
	var gravatar_size = 24;
	var default_gravatar = encodeURI('https://secure.gravatar.com/avatar/ad516503a11cd5ca435acc9bb6523536?s=' + gravatar_size);
	$('.row-avatar').each(function(i) {
		var grav_hash		= $(this).attr('data-gravatar-hash');
		$(this).attr('src', 'https://secure.gravatar.com/avatar/' + grav_hash
			+ '?s=' + gravatar_size + '&d=' + default_gravatar);
	});

	// row highlighting and list view functions
	$('.mcheck').change( function() {
		if( this.checked )  {
			$('#tr' + this.value + '').addClass('highlight');
		} else {
			$('#tr' + this.value + '').removeClass('highlight');
		}
	} );

	$('.mcheck').each( function() {
		if( this.checked ) $('#tr' + this.value + '').toggleClass('highlight');
	} );

	$('#checkall').click( function() { $('.mcheck').each( function(){
	if ( this.checked ) {
		$('#' + this.id).removeAttr( 'checked' );
	} else { $('#' + this.id).attr( 'checked', 'checked' ); }
	$('#' + this.id).change();
	}) } );

});