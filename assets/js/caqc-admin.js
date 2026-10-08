( function () {
	var inlineEdit = window.inlineEditPost && window.inlineEditPost.edit;

	if ( ! inlineEdit ) {
		return;
	}

	window.inlineEditPost.edit = function ( id ) {
		var ergebnis = inlineEdit.apply( this, arguments );
		var beitrag  = typeof id === 'object' ? this.getId( id ) : id;

		if ( ! beitrag ) {
			return ergebnis;
		}

		var zeile   = document.getElementById( 'post-' + beitrag );
		var formular = document.getElementById( 'edit-' + beitrag );

		if ( ! zeile || ! formular ) {
			return ergebnis;
		}

		var typ   = zeile.querySelector( '.caqc-col-type' );
		var feld  = formular.querySelector( 'select[name="_caqc_type"]' );

		if ( typ && feld ) {
			feld.value = typ.getAttribute( 'data-type' ) || '';
		}

		return ergebnis;
	};
}() );
