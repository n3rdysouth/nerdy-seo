/**
 * AI Meta Description Generator
 */
(function($) {
    'use strict';

    var currentPostId = null;
    var currentField = null;

    $(document).ready(function() {
        // Open modal when AI button is clicked in inline edit (post list)
        $(document).on('click', '.nerdy-seo-ai-inline-btn', function(e) {
            e.preventDefault();

            // Get post ID from button's data attribute
            var postId = $(this).data('post-id');

            // Determine which field we're editing (title or description)
            var $wrapper = $(this).closest('.nerdy-seo-inline-edit-wrapper');
            var field = $wrapper.data('field');

            if (postId && field) {
                currentPostId = postId;
                currentField = field;
                openModal();
            }
        });

        // Open modal when AI button is clicked in meta box (post editor)
        $(document).on('click', '.nerdy-seo-ai-meta-btn', function(e) {
            e.preventDefault();

            // Get post ID and field from button's data attributes
            var postId = $(this).data('post-id');
            var field = $(this).data('field');

            if (postId && field) {
                currentPostId = postId;
                currentField = field;
                openModal();
            }
        });

        // Close modal
        $(document).on('click', '.nerdy-seo-modal-close, .nerdy-seo-modal-overlay', function() {
            closeModal();
        });

        // Generate button click
        $('#nerdy-seo-ai-generate-btn').on('click', function() {
            generateMetaDescriptions();
        });

        // Select suggestion
        $(document).on('click', '.nerdy-seo-suggestion-item', function() {
            var description = $(this).data('description');
            applyMetaDescription(currentPostId, description);
        });

        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#nerdy-seo-ai-modal').is(':visible')) {
                closeModal();
            }
        });
    });

    function openModal() {
        $('#nerdy-seo-ai-modal').fadeIn(200);
        $('body').addClass('nerdy-seo-modal-open');

        // Update modal title based on field type
        var fieldName = currentField === 'title' ? 'SEO Title' : 'Meta Description';
        $('#nerdy-seo-ai-modal h2').html('<span class="dashicons dashicons-superhero"></span> Generate ' + fieldName + ' with AI');

        // Reset form
        $('.nerdy-seo-ai-form').show();
        $('.nerdy-seo-ai-loading').hide();
        $('.nerdy-seo-ai-results').hide();
        $('#nerdy-seo-ai-keywords').val('');
        $('#nerdy-seo-ai-tone').val('professional');
    }

    function closeModal() {
        $('#nerdy-seo-ai-modal').fadeOut(200);
        $('body').removeClass('nerdy-seo-modal-open');
        currentPostId = null;
        currentField = null;
    }

    function generateMetaDescriptions() {
        var tone = $('#nerdy-seo-ai-tone').val();
        var keywords = $('#nerdy-seo-ai-keywords').val();

        // Update loading message
        var fieldName = currentField === 'title' ? 'SEO titles' : 'meta descriptions';
        $('.nerdy-seo-ai-loading p').text('Generating ' + fieldName + '...');

        // Show loading
        $('.nerdy-seo-ai-form').hide();
        $('.nerdy-seo-ai-results').hide();
        $('.nerdy-seo-ai-loading').show();

        $.ajax({
            url: nerdySeoAI.ajaxUrl,
            type: 'POST',
            data: {
                action: 'nerdy_seo_generate_meta_description',
                nonce: nerdySeoAI.nonce,
                post_id: currentPostId,
                field: currentField,
                tone: tone,
                focus_keywords: keywords
            },
            success: function(response) {
                $('.nerdy-seo-ai-loading').hide();

                if (response.success) {
                    displaySuggestions(response.data.suggestions);
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    $('.nerdy-seo-ai-form').show();
                }
            },
            error: function(xhr, status, error) {
                $('.nerdy-seo-ai-loading').hide();
                $('.nerdy-seo-ai-form').show();
                alert('Error: ' + error);
            }
        });
    }

    function displaySuggestions(suggestions) {
        var $list = $('.nerdy-seo-suggestions-list');
        $list.empty();

        // Update results title
        var fieldName = currentField === 'title' ? 'SEO Title' : 'Meta Description';
        $('.nerdy-seo-ai-results h3').text('Select a ' + fieldName);

        suggestions.forEach(function(suggestion, index) {
            var charCount = suggestion.length;
            var isOptimal, statusClass;

            if (currentField === 'title') {
                isOptimal = charCount >= 50 && charCount <= 60;
                statusClass = isOptimal ? 'optimal' : (charCount < 50 ? 'short' : 'long');
            } else {
                isOptimal = charCount >= 150 && charCount <= 160;
                statusClass = isOptimal ? 'optimal' : (charCount < 150 ? 'short' : 'long');
            }

            var $item = $('<div class="nerdy-seo-suggestion-item" data-description="' + escapeHtml(suggestion) + '">' +
                '<div class="nerdy-seo-suggestion-number">' + (index + 1) + '</div>' +
                '<div class="nerdy-seo-suggestion-content">' +
                    '<div class="nerdy-seo-suggestion-text">' + escapeHtml(suggestion) + '</div>' +
                    '<div class="nerdy-seo-suggestion-meta">' +
                        '<span class="nerdy-seo-char-count ' + statusClass + '">' +
                            charCount + ' characters' +
                            (isOptimal ? ' <span class="dashicons dashicons-yes-alt"></span>' : '') +
                        '</span>' +
                    '</div>' +
                '</div>' +
                '<button class="button button-primary nerdy-seo-use-suggestion">' +
                    '<span class="dashicons dashicons-saved"></span> Use This' +
                '</button>' +
            '</div>');

            $list.append($item);
        });

        $('.nerdy-seo-ai-results').show();
    }

    function applyMetaDescription(postId, description) {
        // Try to find inline edit wrapper (post list page)
        var $wrapper = $('.nerdy-seo-inline-edit-wrapper[data-post-id="' + postId + '"][data-field="' + currentField + '"]');
        var $input = $wrapper.find('.nerdy-seo-inline-input, .nerdy-seo-inline-textarea');

        // If not found, try to find meta box field (post editor page)
        if (!$input.length) {
            $input = $('#nerdy_seo_' + currentField);
        }

        if ($input.length) {
            // Populate the input/textarea with the selected text
            $input.val(description);

            // Trigger character count update
            $input.trigger('input');

            // Close the modal
            closeModal();

            // Show brief success indicator
            if ($wrapper.length) {
                $wrapper.css('background', '#d4edda');
                setTimeout(function() {
                    $wrapper.css('background', '');
                }, 1000);
            } else {
                // For meta box, briefly highlight the field
                $input.css('border-color', '#46b450').css('box-shadow', '0 0 3px rgba(70, 180, 80, 0.5)');
                setTimeout(function() {
                    $input.css('border-color', '').css('box-shadow', '');
                }, 1000);
            }
        } else {
            alert('Could not find the field. Please try again.');
        }
    }

    function showNotice(message, type) {
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wp-header-end').after($notice);

        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})(jQuery);
