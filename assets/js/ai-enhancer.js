jQuery(document).ready(function ($) {
	const button = $('<button type="button" class="button button-primary" style="margin-left:10px;">' + AIEnhancer.button_label + '</button>');
	$('#titlewrap').after(button);

	button.on('click', function () {
		const content = tinyMCE.activeEditor ? tinyMCE.activeEditor.getContent({ format: 'text' }) : $('#content').val();

		if (!content) {
			alert('本文が空です');
			return;
		}

		button.prop('disabled', true).text('AI加筆中…');

		$.post(AIEnhancer.ajax_url, {
			action: 'ai_enhance_content',
			content: content,
			nonce: AIEnhancer.nonce
		}, function (response) {
			button.prop('disabled', false).text(AIEnhancer.button_label);
			if (response.success) {
				const enhanced = response.data;
				if (tinyMCE.activeEditor) {
					tinyMCE.activeEditor.setContent(enhanced);
				} else {
					$('#content').val(enhanced);
				}
				alert('AI加筆が完了しました。');
			} else {
				alert('加筆に失敗しました：' + response.data);
			}
		});
	});
});
