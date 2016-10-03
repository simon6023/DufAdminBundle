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