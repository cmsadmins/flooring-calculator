( function () {
	var inlineEdit = window.inlineEditPost && window.inlineEditPost.edit;

	if ( ! inlineEdit ) {
		return;
	}

	window.inlineEditPost.edit = function ( id ) {
		var result = inlineEdit.apply( this, arguments );
		var postId = typeof id === 'object' ? this.getId( id ) : id;

		if ( ! postId ) {
			return result;
		}

		var row  = document.getElementById( 'post-' + postId );
		var form = document.getElementById( 'edit-' + postId );

		if ( ! row || ! form ) {
			return result;
		}

		var type  = row.querySelector( '.caqc-col-type' );
		var field = form.querySelector( 'select[name="_caqc_type"]' );

		if ( type && field ) {
			field.value = type.getAttribute( 'data-type' ) || '';
		}

		return result;
	};
}() );
