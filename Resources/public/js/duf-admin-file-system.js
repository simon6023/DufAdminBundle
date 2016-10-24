function DufAdminFileSystem() { }

DufAdminFileSystem.prototype.loadMoreFiles = function(filetype, page, container)
{
	page = parseInt(page);

	if (!window.ajax_processing) {
		window.ajax_processing = true;

		var route 	= Routing.generate('duf_admin_get_file_list', { filetype: filetype, page: page });
		$.ajax({
			url: route,
			type: 'POST',
			data: 'select_file_parent_id=' + window.select_file_parent_id + '&select_file_parent_entity_class=' + window.select_file_parent_class + '&select_file_parent_property=' + window.select_file_parent_property,
			success: function(html) {
				window.ajax_processing = false;

				if (page == 1) {
					$(container).html(html);
				}
				else {
					$(container).append(html);
				}

				// update page number
				var next_page = page + 1;
				$('.duf-admin-load-more.file-system').find('button').attr('data-page', next_page);
	    	},
	    	error: function(data) {
	    		console.log(data);
	    	}
		});
	}
};

DufAdminFileSystem.prototype.deleteFile = function(file_id)
{
	var route 	= Routing.generate('duf_admin_delete_file', { file_id: file_id });
	$.ajax({
		url: route,
		type: 'POST',
		success: function(html) {
			// remove image from list
			$('#file-box-' + file_id).remove();
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
};

DufAdminFileSystem.prototype.renderModal = function(link)
{
	// show overlay
	$('#duf-admin-overlay').fadeIn();

	var form_data 			= new FormData();
	form_data.append('modal_title', link.data('modal-title'));
	form_data.append('parent_entity_class', link.data('parent-entity-class'));
	form_data.append('parent_entity_id', link.data('parent-entity-id'));
	form_data.append('parent_property', link.data('parent-property'));
	form_data.append('filetype', link.data('filetype'));

	var modal_name = 'file-metadata';
	if (typeof(link.data('modal-name')) !== 'undefined') {
		modal_name = link.data('modal-name');
	}

	if (modal_name == 'edit-image') {
		form_data.append('file_id', link.data('file-id'));
	}

	// open metadata modal
	var route 	= Routing.generate('duf_admin_render_modal', { name: modal_name });
	$.ajax({
		url: route,
		type: 'POST',
		data: form_data,
		cache: false,
		processData: false,
		contentType: false,
		success: function(html) {
			$('body').append(html);
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
};

DufAdminFileSystem.prototype.closeModal = function()
{
	// hide overlay
	$('#duf-admin-overlay').fadeOut();

	$('.duf-admin-modal').each(function() {
		$(this).remove();
	});
};

DufAdminFileSystem.prototype.saveImage = function(button)
{
	var file_id 		= button.data('file-id');
	var save_route 		= Routing.generate('duf_admin_save_edit_image', { file_id: file_id });
	var image_data 		= JSON.stringify($('#image-to-crop').cropper('getData'), null, 2);
	var form_data 		= new FormData();

	form_data.append('image_data', image_data);
	form_data.append('parent_entity', button.data('parent-entity'));
	form_data.append('parent_entity_id', button.data('parent-entity-id'));

	$.ajax({
		url: save_route,
		type: 'POST',
		data: form_data,
		cache: false,
		processData: false,
		contentType: false,
		success: function(new_filepath) {
			// close modal
			window.dufAdminFileSystem.closeModal();

			// set new thumbnail
			$('#preview-image-' + file_id).attr('src', new_filepath + '?timestamp=' + new Date().getTime());
    	},
    	error: function(data) {
    		console.log('error');
    		console.log(data);
    	}
	});
}

DufAdminFileSystem.prototype.setSelectedFileForEntity = function(selected_file_id, parent_entity_property)
{
	// append selected file to form
	var parent_select 			= $('#duf-admin-select-file-' + parent_entity_property);

	if (!parent_select.hasClass('multiple')) {
		parent_select.html('<option value="' + selected_file_id + '" selected>' + selected_file_id + '</option>');
	}
	else {
		parent_select.append('<option value="' + selected_file_id + '" selected>' + selected_file_id + '</option>');
	}

	// set file preview
	window.dufAdminFileSystem.setFilePreview(selected_file_id, parent_entity_property);

	// close modal
	window.dufAdminFileSystem.closeModal();
}

DufAdminFileSystem.prototype.setFilePreview = function(file_id, parent_entity_property)
{
	var route 	= Routing.generate('duf_admin_get_file', { file_id: file_id });
	$.ajax({
		url: route,
		type: 'POST',
		success: function(json) {
			var preview_html = window.dufAdminFileSystem.getPreviewHtml(json, parent_entity_property);
			$('#files-container-' + parent_entity_property).append(preview_html);
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
}

DufAdminFileSystem.prototype.getPreviewHtml = function(file_json, parent_entity_property)
{
	var html 		= '<div class="duf-admin-file-preview" id="file-preview-' + file_json.id + '">';
	html 			= html + '<div class="duf-admin-remove-selected-file" data-file-id="' + file_json.id + '" data-entity-property="' + parent_entity_property + '"><i class="fa fa-times" aria-hidden="true"></i> Delete</div>';
	html 			= html + '<img src="/' + file_json.path.replace('../web/', '/') + '/' + file_json.filename + '">';
	html 			= html + '</div>';

	return html;
}

DufAdminFileSystem.prototype.removeSelectedFile = function(button)
{
	var selected_files_container = $('#duf-admin-select-file-' + button.data('entity-property'));
		
	// reset select
	if (!selected_files_container.hasClass('multiple')) {
		selected_files_container.html('');
	}
	else {
		var option_to_remove = selected_files_container.find('option[value="' + button.data('file-id') + '"]');
		option_to_remove.remove();
	}

	// remove preview
	$('#file-preview-' + button.data('file-id')).remove();
}

$(document).on('click', '#show-file-input', function(e) {
	$('.duf-admin-upload-input-container').slideDown();
});

$(document).on('click', '.duf-admin-upload-input-container .close.fileinput-remove', function(e) {
	$('.duf-admin-upload-input-container').slideUp();
});

$(document).on('mouseenter', '.duf-admin-file-gallery', function(e) {
	$(this).find('.image-container').css('opacity', '0.5');
});

$(document).on('mouseleave', '.duf-admin-file-gallery', function(e) {
	$(this).find('.image-container').css('opacity', '1');
});

$(document).on('click', '.duf-admin-load-more.file-system .btn.btn-primary.btn-lg', function(e) {
	window.dufAdminFileSystem.loadMoreFiles($(this).data('filetype'), $(this).attr('data-page'), '.box-body.duf-admin-file-list-container');
});

$(document).on('click', '.duf-admin-remove-file', function(e) {
	window.dufAdminFileSystem.deleteFile($(this).data('file-id'));
});

$(document).on('click', '.duf-admin-render-modal', function(e) {
	e.preventDefault();
	window.dufAdminFileSystem.renderModal($(this));
});

$(document).on('click', '#duf-admin-overlay', function(e) {
	window.dufAdminFileSystem.closeModal();
});

$(document).on('click', '.duf-admin-modal .close', function(e) {
	window.dufAdminFileSystem.closeModal();
});

$(document).on('click', '.duf-admin-modal .modal-footer .btn.btn-default', function(e) {
	window.dufAdminFileSystem.closeModal();
});

$(document).on('click', '.render-file-select-modal, .duf-admin-add-multiple-file', function(e) {
	e.preventDefault();
	window.dufAdminFileSystem.renderModal($(this));
});

$(document).on('click', '.duf-admin-modal.modal#select-file .duf-admin-file-gallery', function(e) {
	var selected_file_id 		= $(this).find('.duf-admin-select-file').data('file-id');
	var parent_entity_property 	= $(this).find('.duf-admin-select-file').data('parent-entity-property');
	window.dufAdminFileSystem.setSelectedFileForEntity(selected_file_id, parent_entity_property);
});

$(document).on('click', '.duf-admin-remove-selected-file', function() {
	window.dufAdminFileSystem.removeSelectedFile($(this));
});

$(document).on('click', '.duf-admin-crop-selected-file', function() {
	window.dufAdminFileSystem.renderModal($(this));
});

$(document).on('click', '#edit-image #save-image', function() {
	window.dufAdminFileSystem.saveImage($(this));
});

$(document).on('click', '.sort-duf-admin-multiple-files-gallery', function(e) {
	e.preventDefault();

	var gallery_container = $(this).parent().find('.duf-admin-selected-files-container');

	if (!gallery_container.hasClass('being-sorted')) {
		$(this).html('Done');

		gallery_container.addClass('being-sorted');
		gallery_container.sortable();
		gallery_container.disableSelection();
		gallery_container.find('.duf-admin-file-preview').addClass('sortable');
	}
	else {
		$(this).html('<i class="fa fa-sort" aria-hidden="true"></i> Sort');

		gallery_container.sortable('destroy');
		gallery_container.removeClass('being-sorted');
		gallery_container.find('.duf-admin-file-preview').removeClass('sortable');

		// sort select input
		var gallery_select 	= $(this).parent().find('.duf-admin-hidden-select.multiple');
		var options 		= gallery_select.find('option');
		var previews 		= $(this).parent().find('.duf-admin-file-preview');
		var new_options 	= new Array();

		$.each(previews, function(i, item) {
			var id 	= item.id;
			id 	 	= id.replace('file-preview-', '');

			new_options.push(id);
		});

		// empty list
		gallery_select.html('');

		$.each(new_options, function(i, item) {
			var option_html = '<option value="' + item + '" selected>' + item + '</option>';
			gallery_select.append(option_html);
		});
	}
});