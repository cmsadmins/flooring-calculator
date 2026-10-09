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
		var waste = form.querySelector( 'input[name="_caqc_waste"]' );

		if ( type && field ) {
			field.value = type.getAttribute( 'data-type' ) || '';
		}

		if ( type && waste ) {
			waste.value = type.getAttribute( 'data-waste' ) || '';
		}

		return result;
	};
}() );
