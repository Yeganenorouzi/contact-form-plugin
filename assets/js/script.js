jQuery(document).ready(function($) {
    const form = $('#contact-form');
    const messageDiv = $('.form-message');

    function showMessage(message, type) {
        messageDiv.removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();

        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 100
        }, 500);

        // Hide message after 5 seconds
        setTimeout(() => {
            messageDiv.fadeOut();
        }, 5000);
    }

    form.on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'submit_contact_form');
        formData.append('nonce', contactFormAjax.nonce);

        // Disable submit button
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true);

        $.ajax({
            url: contactFormAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    form[0].reset();
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage(contactFormAjax.messages.error, 'error');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
}); 