function DufAdminEmbedForm() { }

DufAdminEmbedForm.prototype.saveEmbedData = function(form)
{
	var embed_form_route 	= Routing.generate('duf_admin_entity_form_request', { path: form.data('route-path') });
	var form_data 			= new FormData(form[0]);
	var form_name 			= form.attr('name');
	var token 				= $('#duf_admin_generic_duf_admin_form_token').val()

	form_data.append('form_token', token);
	form_data.append('form_name', form_name);
	form_data.append('form_entity_name', form.data('form-entity-name'));
	form_data.append('parent_entity_id', form.data('parent-entity-id'));

	var parent_entity_class = $('form[name="duf_admin_create_form"]').data('parent-entity-class');
	if (typeof(parent_entity_class) == 'undefined') {
		parent_entity_class = $('#modal-embed-form').data('parent-entity-class');
	}

	if (typeof(parent_entity_class) !== 'undefined') {
		form_data.append('parent_entity_class', parent_entity_class);
	}

	console.log(parent_entity_class);

	$.ajax({
		url: embed_form_route,
		type: 'POST',
		data: form_data,
		cache: false,
		processData: false,
		contentType: false,
		success: function(json) {
			window.dufAdminJS.reloadResultsList(form_name, token);
			window.dufAdminJS.resetFormInputs(form);
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
};

DufAdminEmbedForm.prototype.reloadResultsList = function(form_name, token)
{
	var form 				= $('form[name="' + form_name + '"]')
	var form_label 			= form.data('form-embed-label');
	var form_class 			= form.data('form-embed-class');
	var form_entity_name 	= form.data('form-embed-entity-name');
	var is_new 				= form.data('is-new');

	if (is_new == true || is_new == '1' || is_new == 1) {
		var reload_route = Routing.generate('duf_admin_get_embed_form_by_token',
												{
													form_embed_label: form_label,
													form_embed_class: form_class,
													form_embed_entity_name: form_entity_name,
													token: token
												}
											);
	}
	else {
		var parent_entity_id 		= form.data('parent-entity-id');
		var parent_entity_class 	= $('form[name="duf_admin_create_form"]').data('parent-entity-class');
		if (typeof(parent_entity_class) == 'undefined') {
			parent_entity_class 	= form.data('parent-entity-class');
		}

		var reload_route = Routing.generate('duf_admin_get_embed_form_by_parent_entity_id',
												{
													form_embed_label: form_label,
													form_embed_class: form_class,
													form_embed_entity_name: form_entity_name,
													parent_entity_id: parent_entity_id,
													parent_entity_class: parent_entity_class
												}
											);
	}

	$.ajax({
		url: reload_route,
		type: 'POST',
		success: function(html) {
			$('#embed-form-container-' + form_name).html(html);
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
};

DufAdminEmbedForm.prototype.deleteEmbedEntity = function(embed_entity_class, embed_entity_id)
{
	var delete_route = Routing.generate('duf_admin_delete_embed_entity', { embed_entity_class: embed_entity_class, embed_entity_id: embed_entity_id });
	$.ajax({
		url: delete_route,
		type: 'POST',
		success: function(html) {
			$('#embed-entity-' + embed_entity_id).remove();
    	},
    	error: function(data) {
    		console.log(data);
    	}
	});
};

DufAdminEmbedForm.prototype.resetFormInputs = function(form)
{

}

$(document).on('submit', '.duf-admin-embed-form', function(e) {
	e.preventDefault();
	window.dufAdminJS.saveEmbedData($(this));
});

$(document).on('click', '.duf-admin-delete-embed', function(e) {
	e.preventDefault();
	window.dufAdminJS.deleteEmbedEntity($(this).data('embed-entity-class'), $(this).data('embed-entity-id'));
});