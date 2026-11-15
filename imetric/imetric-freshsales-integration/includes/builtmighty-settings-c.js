// builtmighty-settings-c.js
jQuery(document).ready(function($) {
    // Check Freshsales API Key
    if (typeof freshsalesTest === 'undefined') return;

    $.post(freshsalesTest.ajax_url, {
		action: 'test_freshsales',
		nonce: freshsalesTest.nonce
	}, function(res) {
		const $result = $('#freshsales-test-result');
		if (res.success) {
			$result.text(res.data).css('color', 'green');
		} else {
			$result.text(res.data).css('color', 'red');
		}
	});

	$('#add-distributor-name').on('click', function(e) {
		e.preventDefault();
		const val = $('#new-distributor-name').val().trim();
		if (!val) return;

		const newRow = `
			<div class=\"distributor-entry\" style=\"margin-bottom:5px;\">
				<input type=\"text\" name=\"freshsales_distributor_names[]\" value=\"${val}\" class=\"regular-text\" />
				<button type=\"button\" class=\"button remove-distributor\">Remove</button>
			</div>
		`;

		$('#distributor-names-list').append(newRow);
		$('#new-distributor-name').val('');
	});

	$('#distributor-names-list').on('click', '.remove-distributor', function(e) {
		e.preventDefault();
		$(this).closest('.distributor-entry').remove();
	});

});