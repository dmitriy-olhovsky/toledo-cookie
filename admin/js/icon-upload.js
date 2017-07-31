jQuery(document).ready(function($){
    // Image upload
    $('#tc_upload_logo_button').on('click', function(evt) {
        evt.preventDefault();

        var image = wp.media({
            title: 'Upload Image',
            multiple: false
        }).open()
            .on('select', function(e){
                // This will return the selected image from the Media Uploader, the result is an object
                var uploaded_image = image.state().get('selection').first();
                var image_json = uploaded_image.toJSON();

                // Let's assign the url value to the input field
                $('#tc-image-preview').attr('src', image_json.url);
                $('#tc_upload_logo').val(image_json.id);

            });
    });

    // Color picker
    $('.tc-color-picker').wpColorPicker();
});