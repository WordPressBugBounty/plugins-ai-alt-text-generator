jQuery(document).ready(function ($) {
    // Function to insert the button
    function insertAltTextButton() {
        if (!$('#generate-alt-text-btn').length) {
            $('.attachment-alt-text, .alt-text').append(
                '<br></b><p class="alt-generate-alt-text-wrapper" style="display:inline-block;width:100%;">' +
                '<input type="button" id="generate-alt-text-btn" class="button" value="Generate Alt Text">' +
                '<span class="spinner"></span>' +
                '<span class="error-message" style="color: red; margin-left: 10px;"></span>' +
                '<span class="success-message" style="color: green; margin-left: 10px;"></span>' +
                '</p><br><br>'
            );
        }
    }

    // Mutation observer to detect when media details are opened
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                insertAltTextButton();
            }
        });
    });

    // Start observing
    observer.observe(document.body, { childList: true, subtree: true });

    // Handle button click
    $(document).on('click', '#generate-alt-text-btn', function (e) {
        e.preventDefault();

        function showError($wrapper, message) {
            $wrapper.find('.error-message').text(message).show();
            setTimeout(function () {
                $wrapper.find('.error-message').fadeOut();
            }, 5000);
        }

        var $button = $(this);
        var $wrapper = $button.closest('.alt-generate-alt-text-wrapper');
        var attachmentId = null;

        console.log('AATG Debug: Starting attachment ID detection...');

        // 1. Media Library Modal (most reliable)
        attachmentId = $button.closest('.attachment-details').find('input[data-setting="id"]').val();
        if (attachmentId) {
            console.log('AATG Debug: Found ID in Media Modal:', attachmentId);
        }

        // 2. Media Library Grid/List View
        if (!attachmentId) {
            attachmentId = $button.closest('.attachment').data('id');
            if (attachmentId) {
                console.log('AATG Debug: Found ID in Media Grid/List:', attachmentId);
            }
        }

        // 3. Gutenberg Editor (most complex)
        if (!attachmentId) {
            console.log('AATG Debug: Trying Gutenberg editor detection...');
            var $selectedBlock = $('.wp-block.is-selected');
            console.log('AATG Debug: Selected block found:', $selectedBlock.length > 0);

            if ($selectedBlock.length) {
                var $img = $selectedBlock.find('img');
                console.log('AATG Debug: Image inside block found:', $img.length > 0);

                // Find image ID from class name
                var imgClass = $img.attr('class');
                console.log('AATG Debug: Image class:', imgClass);
                if (imgClass) {
                    var match = imgClass.match(/wp-image-(\d+)/);
                    if (match && match[1]) {
                        attachmentId = match[1];
                        console.log('AATG Debug: Found ID from class name:', attachmentId);
                    }
                }

                // Fallback: Check for data-id attribute on image
                if (!attachmentId && $img.data('id')) {
                    attachmentId = $img.data('id');
                    console.log('AATG Debug: Found ID from data-id attribute:', attachmentId);
                }
            }
        }

        // 4. Classic Editor
        if (!attachmentId) {
            if ($('#post_ID').length) {
                attachmentId = $('#post_ID').val();
                console.log('AATG Debug: Found ID in Classic Editor:', attachmentId);
            }
        }

        console.log('AATG Debug: Final attachmentId before AJAX:', attachmentId);

        if (!attachmentId) {
            showError($wrapper, 'Could not find image ID. Please select an image.');
            return;
        }

        $wrapper.find('span.spinner').addClass('is-active');
        $wrapper.find('.error-message').text('').hide();
        $button.prop('disabled', true);

        $.ajax({
            url: aiAltTextGenerator.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_alt_text',
                nonce: aiAltTextGenerator.nonce,
                post_id: attachmentId
            },
            success: function (response) {
                if (response.success && response.data) {
                    var altText = response.data;

                    // Update alt text field in media library modal
                    var $altTextarea = $button.closest('.attachment-details').find('textarea[name$="[alt]"], textarea[id^="attachment-details-alt-text"]');
                    if ($altTextarea.length) {
                        $altTextarea.val(altText);
                    } else {
                        // Fallback for different structures
                        $('textarea[name="_wp_attachment_image_alt"], .alt-text textarea').val(altText);
                    }

                    // Update alt text in Gutenberg editor
                    if ($selectedBlock && $selectedBlock.length) {
                        $selectedBlock.find('img').attr('alt', altText);
                        var inputEvent = new Event('input', { bubbles: true });
                        $selectedBlock.find('img')[0].dispatchEvent(inputEvent);
                    }

                    showSuccess($wrapper, 'Alt text generated successfully');
                } else {
                    showError($wrapper, response.data || 'Failed to generate alt text');
                }
            },
            error: function (xhr, status, error) {
                var message = 'Server error occurred';
                try {
                    var response = JSON.parse(xhr.responseText);
                    message = response.data || message;
                } catch (e) { }
                showError($wrapper, message);
            },
            complete: function () {
                $wrapper.find('span.spinner').removeClass('is-active');
                $button.prop('disabled', false);
            }
        });
    });

    function showSuccess($wrapper, message) {
        var $successMessage = $wrapper.find('.success-message');
        if (!$successMessage.length) {
            $wrapper.append('<span class="success-message" style="color: green; margin-left: 10px; width: auto;"></span>');
            $successMessage = $wrapper.find('.success-message');
        }
        $successMessage.text(message).show();
        setTimeout(function () {
            $successMessage.fadeOut();
        }, 5000);
    }
});
