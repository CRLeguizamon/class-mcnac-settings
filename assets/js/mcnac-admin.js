jQuery(document).ready(function ($) {
    var mediaUploader;

    $('#mcnac_upload_logo_btn').click(function (e) {
        e.preventDefault();

        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Chat Logo',
            button: {
                text: 'Choose Image'
            },
            multiple: false
        });

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#chat_logo').val(attachment.url);
            $('#mcnac_logo_preview').attr('src', attachment.url).show();
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    // Remove image button
    $('#mcnac_remove_logo_btn').click(function (e) {
        e.preventDefault();
        $('#chat_logo').val('');
        $('#mcnac_logo_preview').hide();
    });
});
