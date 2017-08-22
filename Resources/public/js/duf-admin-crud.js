function DufAdminCrud() { }

DufAdminCrud.prototype.integersOnly = function(input)
{
	var number 			= input.val();
	var number_split 	= number.split('.');
	if (number_split.length > 1) {
		input.val(number_split[0]);
	}
	else {
		number_split 	= number.split(',');
		if (number_split.length > 1) {
			input.val(number_split[0]);
		}
	}
};

DufAdminCrud.prototype.getTreeView = function(entity_name)
{
	$('#gtreetable').gtreetable({
		manyroots: true,
	  	'source': function (id) {
	  		// check number of nodes
	  		var check_route = Routing.generate('duf_admin_get_tree', { entity_name: entity_name });
			$.ajax({
				url: check_route,
				method: 'post',
				data: { 'id': id },
				success: function(json) {
	        		if (json.nodes.length == 0) {
	        			$('.create-first-category').show();
	        		}
	        		else {
	        			$('.create-first-category').remove();
	        		}
				},
			});

	  		return {
	  			type: 'GET',
	  			url: Routing.generate('duf_admin_get_tree', { entity_name: entity_name }),
	        	data: { 'id': id },
	        	dataType: 'json',
	        	error: function(XMLHttpRequest) {
	        		console.log(XMLHttpRequest.status+': '+XMLHttpRequest.responseText);
	        	},
	      	}
	    },
		'onSave':function (oNode) {
			var action 		= !oNode.isSaved() ? 'create' : 'update';
			var node_id 	= !oNode.isSaved() ? '0' : oNode.getId();
			var save_route 	= Routing.generate('duf_admin_tree_save', { entity_name: entity_name, action: action, node_id: node_id });

			return {
				type: 'POST',
		      	url: save_route,
		      	data: {
		        	parent: oNode.getParent(),
		        	name: oNode.getName(),
		        	position: oNode.getInsertPosition(),
		        	related: oNode.getRelatedNodeId()
		      	},
		      	dataType: 'json',
		      	error: function(XMLHttpRequest) {
		        	console.log(XMLHttpRequest.status+': '+XMLHttpRequest.responseText);
		      	}
		    };
		},
		'onDelete':function (oNode) {
			var node_id 		= oNode.getId();
			var delete_route 	= Routing.generate('duf_admin_tree_remove', { entity_name: entity_name, node_id: node_id });

			return {
				type: 'POST',
				url: delete_route,
				dataType: 'json',
				error: function(XMLHttpRequest) {
					console.log(XMLHttpRequest.status+': '+XMLHttpRequest.responseText);
				}
			};
		},  
	});
};

DufAdminCrud.prototype.exportData = function(button)
{
	var entity_name 	= button.data('entity-name');
	var format 			= button.data('format');
	var items 			= new Array();

	$('#duf-admin-index-table tbody td:nth-child(1)').each(function () {
		var column_html 	= $(this).html();
		column_html  		= column_html.replace(/\s/g,'');

		items.push(column_html);
	});

	// 1. ajax call to create file
	var route = Routing.generate('duf_admin_export_generate', { format: format, entity_name: entity_name });
	$.ajax({
        url : route,
        type: "POST",
        data : 'items=' + items,
        success:function(export_id)  {
        	// 2. redirect to download url
        	var export_route = Routing.generate('duf_admin_export_download', {id: export_id});
        	window.location.href = export_route;
        },
        error: function(error)  {
            console.log(error);
        }
    });
}

DufAdminCrud.prototype.changeLangage = function(choice)
{
	var field_name 				= choice.attr('data-field-name');
	var selected_lang 			= choice.attr('data-lang');
	var selected_lang_li		= $('#duf-admin-translatable-text-' + field_name + ' li.duf-admin-translatable-text.choice[data-lang="' + selected_lang + '"]');
	var selected_lang_label 	= selected_lang_li.find('a').html();
	var button 					= $('#duf-admin-translatable-text-' + field_name + ' .duf-admin-translatable-text.selection-container');
	var previous_lang 			= button.attr('data-lang');

	// change selection button label and data-lang
	button.html(selected_lang_label);
	button.attr('data-lang', selected_lang);

	$('#duf-admin-translatable-text-' + field_name + ' .duf-admin-translatable-text.translate-content').each(function() {
		if ($(this).attr('data-lang') == selected_lang) {
			$(this).removeClass('hidden');
			$(this).addClass('visible');
		}
		else {
			$(this).removeClass('visible');
			$(this).addClass('hidden');
		}
	});

	$('#duf-admin-translatable-text-' + field_name + ' .duf-admin-translatable-text.choice').each(function() {
		// hide selected lang option
		if ($(this).attr('data-lang') == selected_lang) {
			$(this).removeClass('visible');
			$(this).addClass('hidden');
		}

		// show previously selected lang option
		if ($(this).attr('data-lang') == previous_lang) {
			$(this).removeClass('hidden');
			$(this).addClass('visible');
		}
	});
}

$(document).on('input', '.duf-admin-numbers.integer', function() {
	window.dufAdminCrud.integersOnly($(this));
});

$(document).on('click', '.duf-admin-translatable-text.choice', function() {
	window.dufAdminCrud.changeLangage($(this));
});

$(document).on('click', '.duf-admin-translate-textarea-button', function() {

});

$(document).on('click', '.save-firt-category', function() {
	var entity_name 	= $(this).data('entity-name');
	var save_route 		= Routing.generate('duf_admin_tree_save', { entity_name: entity_name, action: 'create', node_id: null });
	var category_title 	= $('input[name="first-category"]').val();

	$.ajax({
		url: save_route,
		method: 'post',
      	data: {
        	parent: '0',
        	name: category_title,
        	position: '0',
        	related: '0'
      	},
		success: function() {
			location.reload();
		},
	});
});

$(document).on('mouseenter', '.table.gtreetable tr.node', function() {
	var parent_id 			= $(this).data('parent');
	var row 				= $(this).find('td');

	var parent 				= $(this).prev();
	var parent_parent_id 	= parent.data('parent');

	var next 				= $(this).next();
	var next_parent_id 		= next.data('parent');

	if (parent_id !== '0' && parent_id !== 0) {
		if (parent_parent_id !== '0' && parent_parent_id !== 0) {
			row.append('<i class="fa fa-arrow-up gtree-arrow" aria-hidden="true"></i>');
		}

		if (next_parent_id !== '0' && next_parent_id !== 0) {
			row.append('<i class="fa fa-arrow-down gtree-arrow" aria-hidden="true"></i>');
		}
	}
});

$(document).on('mouseleave', '.table.gtreetable tr.node', function() {
	var row 		= $(this).find('td');
	var arrows  	= row.find('.gtree-arrow');
	$.each(arrows, function() {
		$(this).remove();
	});
});

$(document).on('click', '.table.gtreetable .gtree-arrow', function() {
	var direction 	= 'up';
	if ($(this).hasClass('fa-arrow-down')) {
		direction = 'down';
	}

	var row 		= $(this).parent().parent();
	var entity_name = $('#gtreetable').data('entity-name');
	var node_id 	= $(this).parent().parent().data('id');
	var move_route 	= Routing.generate('duf_admin_tree_move', { entity_name: entity_name, node_id: node_id, direction: direction });

	$.ajax({
		url: move_route,
		method: 'post',
		success: function(json) {
			if (direction == 'up') {
				row.insertBefore(row.prev());
			}
			else if (direction == 'down') {
				row.insertAfter(row.next());
			}
		},
	});
});

$(document).on('click', '.node-action-5', function() {
	// redirect to CRUD
	var node_id 	= $(this).parent().parent().parent().parent().parent().data('id');
	var edit_route 	= $('#edit-route').val();
	edit_route 		= edit_route.replace('/***', '/' + node_id);

	window.location.href = edit_route;
});

$(document).on('click', '.export-link', function(e) {
	e.preventDefault();
	window.dufAdminCrud.exportData($(this));
});

$('.day-picker.all').on('switchChange.bootstrapSwitch', function(e, state) {
	$('.day-picker.day').each(function() {
		if (state !== $(this).prop('checked')) {
			$(this).bootstrapSwitch('toggleState');
		}
	});
});

$('.hour-picker.all').on('switchChange.bootstrapSwitch', function(e, state) {
	$('.hour-picker.hour').each(function() {
		if (state !== $(this).prop('checked')) {
			$(this).bootstrapSwitch('toggleState');
		}
	});
});

$('.minute-picker.all').on('switchChange.bootstrapSwitch', function(e, state) {
	$('.minute-picker.minute').each(function() {
		if (state !== $(this).prop('checked')) {
			$(this).bootstrapSwitch('toggleState');
		}
	});
});