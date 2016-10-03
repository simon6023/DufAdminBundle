function DufAdminMessaging() { }

DufAdminMessaging.prototype.replyToMessage = function()
{
	var form 		= $('form[name="reply-message"]')[0];
	var route 		= Routing.generate('duf_admin_messaging_reply', { conversation_id: $('#reply-message').data('conversation-id') });
	var form_data 	= new FormData(form);

	$.ajax({
		url: route,
		data: form_data,
		contentType: false,
		processData: false,
		method: 'post',
		success: function(data) {
			// update message list
			window.dufAdminMessaging.updateMessageList(data.message_id);
		},
		error: function(data) {
			console.log(data);
		},
	});
};

DufAdminMessaging.prototype.updateMessageList = function(message_id)
{
	var route 	= Routing.generate('duf_admin_messaging_get_message', { message_id: message_id });

	$.ajax({
		url: route,
		method: 'post',
		success: function(html) {
			$('#messages-list-container').append(html);
			window.dufAdminMessaging.clearReplyForm();
		},
		error: function(data) {
			console.log(data);
		},
	});
};

DufAdminMessaging.prototype.getReplyForm = function(button)
{
	var conversation_id 	= button.data('conversation-id');
	var message_id 			= button.data('message-id');
	var type 				= button.data('type');
	var route 				= Routing.generate('duf_admin_messaging_get_reply_form', { conversation_id: conversation_id, message_id: message_id, type: type });

	window.dufAdminMessaging.clearReplyForm();

	$.ajax({
		url: route,
		method: 'post',
		success: function(html) {
			$('#reply_form_container').html(html);
			window.dufAdminMessaging.setSelectedUsers();
		},
		error: function(data) {
			console.log(data);
		},
	});
};

DufAdminMessaging.prototype.clearReplyForm = function()
{
	$('#reply_form_container').html('');
};

DufAdminMessaging.prototype.setSelectedUsers = function()
{
	var users_select = $('select[name="message[users][]"]');

	users_select.find('option').each(function() {
		if ($(this).val() !== '0') {
			$(this).attr('selected', 'selected');
		}
		else {
			$(this).remove();
		}
	});

	users_select.select2();
};

DufAdminMessaging.prototype.deleteConversation = function(conversation_id)
{
	var route = Routing.generate('duf_admin_messaging_delete_conversation', { conversation_id: conversation_id });

	$.ajax({
		url: route,
		method: 'post',
		success: function(html) {
			$('#conversation-row-' + conversation_id).fadeOut();
		},
		error: function(data) {
			console.log(data);
		},
	});
};

DufAdminMessaging.prototype.saveDraft = function(draft_id)
{
	var form 		= $('form[name="message"]')[0];
	var route 		= Routing.generate('duf_admin_messaging_save_draft');
	var form_data 	= new FormData(form);
	var content 	= CKEDITOR.instances.editor_content.getData();

	form_data.append('content_text', content);
	form_data.append('draft_id', draft_id);

	$.ajax({
		url: route,
		data: form_data,
		contentType: false,
		processData: false,
		method: 'post',
		success: function(data) {
			// redirect to draft index
			window.location.href = Routing.generate('duf_admin_messaging_draft_index');
		},
		error: function(data) {
			console.log(data);
		},
	});
}

$(document).on('click', '.reply-button', function() {
	window.dufAdminMessaging.getReplyForm($(this));
});

$(document).on('submit', '#reply-message', function(e) {
	e.preventDefault();
	window.dufAdminMessaging.replyToMessage();
});

$(document).on('click', '.delete-conversations', function(e) {
	e.preventDefault();

	bootbox.setDefaults({
		locale: "en",
	});
    bootbox.confirm($(this).data('confirm-message'), function(result) {
        if (result == true) {
        	// get conversations ids
        	var conversations_inputs = $('input[name="select-conversations[]"]');

        	conversations_inputs.each(function() {
        		if ($(this).is(':checked')) {
        			window.dufAdminMessaging.deleteConversation($(this).val());
        		}
        	});
        }
    });
});

$(document).on('click', '.select-all-conversations', function() {
	// get action
	var select_action = $(this).data('action');

	var conversations_inputs = $('input[name="select-conversations[]"]');
	conversations_inputs.each(function() {
		if (select_action == 'select') {
			$(this).prop('checked', true);
		}
		else if (select_action == 'unselect') {
			$(this).prop('checked', false);
		}
	});

	// update select_action
	if (select_action == 'select') {
		$(this).data('action', 'unselect');
	}
	else if (select_action == 'unselect') {
		$(this).data('action', 'select');
	}
});

$(document).on('click', '.duf-messaging-save-draft', function() {
	var draft_id 				= 0;
	var hidden_draft_id_value 	= $('#draft_id').val();

	if (typeof(hidden_draft_id_value) !== 'undefined') {
		draft_id = hidden_draft_id_value;
	}

	window.dufAdminMessaging.saveDraft(draft_id);
});