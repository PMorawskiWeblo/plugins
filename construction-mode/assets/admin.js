/**
 * Admin JavaScript for Construction Mode
 */
(function ($) {
  'use strict';

  $(document).ready(function () {
    // Initialize color picker
    if ($('.cm-color-picker').length) {
      $('.cm-color-picker').wpColorPicker();
    }

    // Media uploader for logo
    var logoFrame;
    var $logoPreview = $('#cm_logo_preview');
    var $logoId = $('#cm_logo_id');
    var $uploadButton = $('#cm_upload_logo');
    var $removeButton = $('#cm_remove_logo');

    $uploadButton.on('click', function (e) {
      e.preventDefault();

      // If the media frame already exists, reopen it
      if (logoFrame) {
        logoFrame.open();
        return;
      }

      // Create the media frame
      logoFrame = wp.media({
        title: cmAdmin.strings.upload_logo,
        button: {
          text: cmAdmin.strings.upload_logo,
        },
        multiple: false,
        library: {
          type: 'image',
        },
      });

      // When an image is selected, run a callback
      logoFrame.on('select', function () {
        var attachment = logoFrame.state().get('selection').first().toJSON();
        $logoId.val(attachment.id);
        $logoPreview.html(
          '<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 150px; height: auto; display: block;">'
        );
        if ($removeButton.length === 0) {
          $uploadButton.after(
            '<button type="button" class="button" id="cm_remove_logo" style="margin-left: 10px;">' +
              cmAdmin.strings.remove_logo +
              '</button>'
          );
          $removeButton = $('#cm_remove_logo');
        }
      });

      // Open the media frame
      logoFrame.open();
    });

    // Remove logo
    $(document).on('click', '#cm_remove_logo', function (e) {
      e.preventDefault();
      $logoId.val('');
      $logoPreview.html('');
      $(this).remove();
    });

    // Update example URL when secret param or value changes
    function updateExampleUrl() {
      var secretParam = $('#cm_secret_param').val() || 'cm_preview';
      var secretValue = $('#cm_secret_value').val();
      var $exampleContainer = $('.cm-example-url');

      if (secretValue && secretValue.length >= 8) {
        var homeUrl = cmAdmin.homeUrl || window.location.origin;
        // Remove trailing slash if present
        homeUrl = homeUrl.replace(/\/$/, '');
        var exampleUrl = homeUrl + '/?' + encodeURIComponent(secretParam) + '=' + encodeURIComponent(secretValue);

        if ($exampleContainer.length === 0) {
          $('#cm_secret_value')
            .closest('.cm-secret-value-field')
            .find('.description')
            .last()
            .after(
              '<p class="description cm-example-url" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">' +
                '<strong>' +
                (cmAdmin.strings.example_url_label || 'Example bypass URL:') +
                '</strong><br>' +
                '<code style="word-break: break-all;"></code>' +
                '</p>'
            );
          $exampleContainer = $('.cm-example-url');
        }

        $exampleContainer.find('code').text(exampleUrl);
        $exampleContainer.show();
      } else {
        $exampleContainer.hide();
      }
    }

    // Regenerate secret value
    $('#cm_regenerate_secret').on('click', function (e) {
      e.preventDefault();
      var newSecret = generateRandomString(20);
      $('#cm_secret_value').val(newSecret);
      updateExampleUrl();
    });

    // Update example URL on input change
    $('#cm_secret_param, #cm_secret_value').on('input', function () {
      updateExampleUrl();
    });

    // Initial update
    updateExampleUrl();

    /**
     * Generate random string
     *
     * @param {number} length Length of string
     * @returns {string} Random string
     */
    function generateRandomString(length) {
      var characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
      var result = '';
      for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
      }
      return result;
    }
  });
})(jQuery);
