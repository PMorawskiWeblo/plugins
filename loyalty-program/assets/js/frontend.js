/**
 * Frontend JavaScript for Loyalty Program
 *
 * @package Loyalty_Program
 * @version 1.0.0
 */

(function ($) {
  'use strict';

  /**
   * SweetAlert2 Helper - Custom configuration
   */
  var SwalConfig = {
    // Default configuration
    defaultConfig: {
      color: '#000000',
      confirmButtonColor: '#b02e66',
      cancelButtonColor: '#aac096',
      denyButtonColor: '#aac096',
      buttonsStyling: true,
      customClass: {
        popup: 'loyalty-swal-popup',
        title: 'loyalty-swal-title',
        content: 'loyalty-swal-content',
        confirmButton: 'loyalty-swal-confirm',
        cancelButton: 'loyalty-swal-cancel'
      }
    },
    
    // Alert wrapper
    alert: function(message, title, icon) {
      return Swal.fire({
        title: title || '',
        text: message,
        icon: icon || 'info',
        confirmButtonText: 'OK',
        confirmButtonColor: '#b02e66',
        color: '#000000',
        buttonsStyling: true
      });
    },
    
    // Confirm wrapper
    confirm: function(message, title, icon) {
      return Swal.fire({
        title: title || '',
        text: message,
        icon: icon || 'question',
        showCancelButton: true,
        confirmButtonText: 'Tak',
        cancelButtonText: 'Anuluj',
        confirmButtonColor: '#b02e66',
        cancelButtonColor: '#aac096',
        color: '#000000',
        buttonsStyling: true
      });
    },
    
    // Success alert
    success: function(message, title) {
      return this.alert(message, title || 'Sukces', 'success');
    },
    
    // Error alert
    error: function(message, title) {
      return this.alert(message, title || 'Błąd', 'error');
    },
    
    // Warning alert
    warning: function(message, title) {
      return this.alert(message, title || 'Ostrzeżenie', 'warning');
    },
    
    // Info alert
    info: function(message, title) {
      return this.alert(message, title || 'Informacja', 'info');
    }
  };

  /**
   * Loyalty Program Frontend Object
   */
  var LoyaltyProgramFrontend = {
    /**
     * Initialize
     */
    init: function () {
      this.bindEvents();
      this.initWheel();
      this.initInlineCountdowns();
      this.initSurveyPagination();
    },

    /**
     * Bind events
     */
    bindEvents: function () {
      // Copy coupon code - both button and code element
      $(document).on('click', '.loyalty-copy-btn', this.copyCouponCode);
      $(document).on('click', '.loyalty-coupon-code-clickable', this.copyCouponCode);
      $(document).on('click', '.loyalty-copy-coupon-btn', this.copyCouponCode);

      // Join program button
      $(document).on('click', '.loyalty-join-btn', this.joinProgram);

      // Join program button reload (immediate reload after join)
      $(document).on('click', '.loyalty-join-btn-reload', this.joinProgramReload);

      // Spin wheel button
      $(document).on('click', '#loyalty-spin-btn', this.spinWheel);

      // Wheel modal buttons
      $(document).on('click', '.loyalty-open-wheel-modal-btn', this.openWheelModal);
      $(document).on('click', '.loyalty-wheel-modal-close', this.closeWheelModal);
      $(document).on('click', '.loyalty-wheel-modal-overlay', this.closeWheelModalOverlay);
      $(document).on('click', '#loyalty-spin-modal-btn', this.spinWheelModal);
      $(document).on('click', '.loyalty-wheel-modal-close-btn', this.closeWheelModal);

      // Redeem reward button
      $(document).on('click', '.loyalty-redeem-btn', this.redeemReward);

      // Add reward to cart button
      $(document).on('click', '.loyalty-add-reward-to-cart', this.addRewardToCart);

      // Save consents form
      $(document).on('submit', '#loyalty-consents-form', this.saveConsents);

      // Save birth date form
      $(document).on('submit', '#loyalty-birth-date-form', this.saveBirthDate);

      // Save account data form (birth date and phone)
      $(document).on('submit', '#loyalty-account-data-form', this.saveAccountData);

      // Save account data form for non-members
      $(document).on('submit', '#loyalty-account-data-form-non-member', this.saveAccountDataNonMember);

      // Survey/Quiz handlers
      $(document).on('click', '.rating-stars .star', this.handleRating);
      $(document).on('click', '#survey-start-btn', this.startSurvey);
      $(document).on('submit', '#loyalty-survey-form', this.submitSurvey);

      // Pagination handlers
      $(document).on('click', '.loyalty-survey-next-btn', this.surveyNextQuestion);
      $(document).on('click', '.loyalty-survey-quit-btn', this.surveyQuit);
      $(document).on(
        'change',
        '.survey-question input, .survey-question textarea, .survey-question select',
        this.checkSurveyQuestionAnswer
      );

      // Start timer on first interaction if no start button
      $(document).on(
        'change click',
        '.loyalty-survey-form input, .loyalty-survey-form textarea, .loyalty-survey-form select',
        this.startTimerOnInteraction
      );

      // Remove error message when user starts answering
      $(document).on(
        'change input click',
        '.survey-question.has-error input, .survey-question.has-error textarea',
        function () {
          var $question = $(this).closest('.survey-question');
          $question.removeClass('has-error');
          $question.find('.answer-required-error').fadeOut(300, function () {
            $(this).remove();
          });
        }
      );
    },

    /**
     * Start timer on first user interaction (if no start button)
     */
    startTimerOnInteraction: function (e) {
      var $container = $(this).closest('.loyalty-survey-container');

      // Only if has timer, no start button, and not already started
      if ($container.data('has-timer') && !$container.data('has-start-button') && !$container.data('timer-started')) {
        $container.data('timer-started', true);
        LoyaltyProgramFrontend.initSurveyTimer($container);
      }
    },

    /**
     * Copy coupon code to clipboard
     */
    copyCouponCode: function (e) {
      e.preventDefault();
      var $element = $(this);
      // Get code from either data-copy (button) or data-coupon (code element)
      var code = $element.data('copy') || $element.data('coupon') || $element.text().trim();

      // Use modern Clipboard API if available, fallback to execCommand
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(code).then(
          function () {
            // Success - show feedback
            if ($element.hasClass('loyalty-coupon-code-clickable')) {
              // For code element, show temporary message
              var originalText = $element.text();
              $element.addClass('copied').text(loyaltyProgramFrontend.i18n.copied || 'Copied!');
              setTimeout(function () {
                $element.removeClass('copied').text(originalText);
              }, 2000);
            } else {
              // For button, show icon change
              var originalHTML = $element.html();
              $element
                .addClass('copied')
                .html(
                  '<span class="dashicons dashicons-yes"></span> ' + (loyaltyProgramFrontend.i18n.copied || 'Copied!')
                );
              setTimeout(function () {
                $element.removeClass('copied').html(originalHTML);
              }, 2000);
            }
          },
          function () {
            // Fallback to execCommand if clipboard API fails
            fallbackCopy(code, $element);
          }
        );
      } else {
        // Fallback to execCommand for older browsers
        fallbackCopy(code, $element);
      }

      function fallbackCopy(text, $el) {
        // Create temporary input
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();

        // Visual feedback
        if ($el.hasClass('loyalty-coupon-code-clickable')) {
          // For code element, show temporary message
          var originalText = $el.text();
          $el.addClass('copied').text(loyaltyProgramFrontend.i18n.copied || 'Copied!');
          setTimeout(function () {
            $el.removeClass('copied').text(originalText);
          }, 2000);
        } else {
          // For button, show icon change
          var originalHTML = $el.html();
          $el
            .addClass('copied')
            .html('<span class="dashicons dashicons-yes"></span> ' + (loyaltyProgramFrontend.i18n.copied || 'Copied!'));
          setTimeout(function () {
            $el.removeClass('copied').html(originalHTML);
          }, 2000);
        }
      }
    },

    /**
     * Join loyalty program
     */
    joinProgram: function (e) {
      e.preventDefault();
      var $button = $(this);
      var userId = $button.data('user-id');
      var $status = $('.loyalty-join-status');

      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.joining);
      $status.html('');

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_join_frontend',
          nonce: loyaltyProgramFrontend.nonce,
          user_id: userId,
        },
        function (response) {
          if (response.success) {
            $status.html('<div class="loyalty-success-message">' + response.data.message + '</div>');

            // Reload page after 2 seconds to show updated status
            setTimeout(function () {
              location.reload();
            }, 2000);
          } else {
            $status.html(
              '<div class="loyalty-error-message">' +
                (response.data.message || loyaltyProgramFrontend.i18n.error_occurred) +
                '</div>'
            );
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.join_now);
          }
        }
      ).fail(function () {
        $status.html('<div class="loyalty-error-message">' + loyaltyProgramFrontend.i18n.connection_error + '</div>');
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.join_now);
      });
    },

    /**
     * Join loyalty program with immediate reload
     */
    joinProgramReload: function (e) {
      e.preventDefault();
      var $button = $(this);
      var $status = $button.closest('.loyalty-join-button-reload-wrapper').find('.loyalty-join-status-reload');

      // Check if user is not logged in
      if ($button.data('not-logged-in')) {
        var registrationUrl = $button.data('registration-url');
        if (registrationUrl) {
          // URL already contains redirect parameter from PHP
          window.location.href = registrationUrl;
        } else {
          // Fallback to login page if no registration URL
          var currentUrl = window.location.href;
          var loginBaseUrl = loyaltyProgramFrontend.ajax_url.replace('admin-ajax.php', 'wp-login.php');
          var loginUrl =
            loginBaseUrl +
            (loginBaseUrl.indexOf('?') > -1 ? '&' : '?') +
            'redirect_to=' +
            encodeURIComponent(currentUrl);
          window.location.href = loginUrl;
        }
        return;
      }

      var userId = $button.data('user-id');
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.joining);
      $status.html('');

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_join_frontend',
          nonce: loyaltyProgramFrontend.nonce,
          user_id: userId,
        },
        function (response) {
          if (response.success) {
            // Reload immediately after successful join
            location.reload();
          } else {
            $status.html(
              '<div class="loyalty-error-message">' +
                (response.data.message || loyaltyProgramFrontend.i18n.error_occurred) +
                '</div>'
            );
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.join);
          }
        }
      ).fail(function () {
        $status.html('<div class="loyalty-error-message">' + loyaltyProgramFrontend.i18n.connection_error + '</div>');
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.join);
      });
    },

    /**
     * Initialize wheel of fortune
     */
    initWheel: function () {
      var canvas = document.getElementById('loyalty-wheel-canvas');
      if (!canvas || typeof loyaltyWheelPrizes === 'undefined') {
        return;
      }

      LoyaltyProgramFrontend.drawWheel(canvas, loyaltyWheelPrizes, 0);
    },

    /**
     * Get text color (black or white) based on background brightness
     */
    getTextColorForBackground: function (hexColor) {
      // Convert hex to RGB
      var hex = hexColor.replace('#', '');
      var r = parseInt(hex.substr(0, 2), 16);
      var g = parseInt(hex.substr(2, 2), 16);
      var b = parseInt(hex.substr(4, 2), 16);

      // Calculate brightness (YIQ formula)
      var brightness = (r * 299 + g * 587 + b * 114) / 1000;

      // Return black for light backgrounds, white for dark backgrounds
      return brightness > 128 ? '#000000' : '#ffffff';
    },

    /**
     * Draw the wheel
     */
    drawWheel: function (canvas, prizes, rotation) {
      var ctx = canvas.getContext('2d');
      var centerX = canvas.width / 2;
      var centerY = canvas.height / 2;
      var radius = Math.min(centerX, centerY) - 31; // Promień segmentów - zmniejsz wartość aby powiększyć segmenty
      var numPrizes = prizes.length;
      var anglePerPrize = (2 * Math.PI) / numPrizes;

      // Fallback colors if prize doesn't have a color defined
      var fallbackColors = ['#2271b1', '#d63638', '#00a32a', '#f0b849', '#8c65af', '#135e96', '#b32d2e', '#046b2c'];

      // Clear canvas (transparent background - wheel.png is CSS background)
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Save context for rotation
      ctx.save();
      ctx.translate(centerX, centerY);
      ctx.rotate((rotation * Math.PI) / 180);
      ctx.translate(-centerX, -centerY);

      // Draw wheel segments with colors from configuration
      for (var i = 0; i < numPrizes; i++) {
        var angle = i * anglePerPrize - Math.PI / 2;
        // Use prize color if available, otherwise use fallback color
        var color = prizes[i].color || fallbackColors[i % fallbackColors.length];

        // Draw segment - solid color (not transparent)
        ctx.beginPath();
        ctx.fillStyle = color;
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, angle, angle + anglePerPrize);
        ctx.lineTo(centerX, centerY);
        ctx.fill();

        // Draw text - Prize name in separate visual area
        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(angle + anglePerPrize / 2);
        ctx.textAlign = 'right';

        // Teksty zawsze białe (bez automatycznego doboru koloru)
        ctx.fillStyle = '#FFFFFF';

        // NAZWA NAGRODY - Raleway Bold 16px
        ctx.font = '700 16px Raleway, Arial, sans-serif';
        var nameText = prizes[i].name;
        var textDistance = 190; // 75px od centrum
        ctx.fillText(nameText, textDistance, -8); // Bez obramowania (strokeText)

        // PUNKTY - liczba (Raleway Bold 16px z letter-spacing) + "pkt" (Raleway Regular 12px)
        var pointsNumber = '+' + prizes[i].points;

        // Oblicz całkowitą szerokość tekstu punktów z letter-spacing
        ctx.font = '700 16px Raleway, Arial, sans-serif';
        var letterSpacing = 0.8; // zmniejszone o 10%
        var totalWidth = 0;
        for (var j = 0; j < pointsNumber.length; j++) {
          totalWidth += ctx.measureText(pointsNumber[j]).width + letterSpacing;
        }

        // Dodaj szerokość " pkt"
        ctx.font = '400 12px Raleway, Arial, sans-serif';
        var pktWidth = ctx.measureText(' pkt').width;
        totalWidth += pktWidth;

        // Rysuj od prawej do lewej (bo textAlign = 'right')
        // Zaczynamy od pozycji textDistance i cofamy się o totalWidth
        var startX = textDistance - totalWidth;

        // Rysuj liczbę punktów (bold, 16px) z letter-spacing
        ctx.font = '700 16px Raleway, Arial, sans-serif';
        ctx.textAlign = 'left'; // Zmień na left dla poprawnej kolejności
        var xPos = startX;
        for (var j = 0; j < pointsNumber.length; j++) {
          ctx.fillText(pointsNumber[j], xPos, 12);
          xPos += ctx.measureText(pointsNumber[j]).width + letterSpacing;
        }

        // Rysuj " pkt" (regular, 12px)
        ctx.font = '400 12px Raleway, Arial, sans-serif';
        ctx.fillText(' pkt', xPos, 12);

        ctx.textAlign = 'right'; // Przywróć na right

        ctx.restore();
      }

      // Restore context
      ctx.restore();
    },

    /**
     * Spin the wheel
     */
    spinWheel: function (e) {
      e.preventDefault();
      var $button = $(this);

      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.spinning);

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_spin_wheel',
          nonce: loyaltyProgramFrontend.nonce,
        },
        function (response) {
          if (response.success) {
            // Debug log
            if (typeof console !== 'undefined' && console.log) {
              console.log(
                'WHEEL SPIN SUCCESS - Prize Index:',
                response.data.prize_index,
                '| Prize:',
                response.data.prize_name,
                '| Points:',
                response.data.points,
                '| Total Prizes:',
                response.data.total_prizes
              );
            }

            // Animate wheel
            LoyaltyProgramFrontend.animateWheel(response.data.prize_index, function () {
              // Show result
              var resultHtml = '<div class="loyalty-wheel-result-box">';
              resultHtml += '<h3>' + loyaltyProgramFrontend.i18n.congratulations + '</h3>';
              resultHtml += '<div class="prize-name">' + response.data.prize_name + '</div>';
              resultHtml +=
                '<div class="prize-points">+' +
                response.data.points +
                ' <span>' +
                (loyaltyProgramFrontend.i18n.points || 'points') +
                '</span></div>';
              resultHtml += '<p>' + response.data.message + '</p>';
              resultHtml += '</div>';

              $('#loyalty-wheel-result').html(resultHtml);

              // Reload after 5 seconds
              setTimeout(function () {
                location.reload();
              }, 5000);
            });
          } else {
            SwalConfig.error(response.data.message || loyaltyProgramFrontend.i18n.error_occurred);
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.spin_wheel);
          }
        }
      ).fail(function () {
        SwalConfig.error(loyaltyProgramFrontend.i18n.connection_error);
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.spin_wheel);
      });
    },

    /**
     * Animate wheel spinning
     */
    animateWheel: function (winningIndex, callback) {
      var canvas = document.getElementById('loyalty-wheel-canvas');
      if (!canvas) return;

      var numPrizes = loyaltyWheelPrizes.length;
      var degreesPerPrize = 360 / numPrizes;
      // Fix: Changed + to - for correct alignment with pointer
      var targetDegree = 360 - winningIndex * degreesPerPrize - degreesPerPrize / 2;
      var totalRotation = 360 * 5 + targetDegree; // 5 full rotations + target
      var duration = 4000; // 4 seconds
      var startTime = Date.now();

      // Debug logging
      if (typeof console !== 'undefined' && console.log) {
        console.log(
          'WHEEL ANIMATION - Winning Index:',
          winningIndex,
          '| Degrees per prize:',
          degreesPerPrize,
          '| Target degree:',
          targetDegree,
          '| Total prizes:',
          numPrizes
        );
      }

      function easeOut(t) {
        return 1 - Math.pow(1 - t, 3);
      }

      function animate() {
        var now = Date.now();
        var elapsed = now - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var eased = easeOut(progress);
        var currentRotation = totalRotation * eased;

        // Redraw wheel with rotation
        LoyaltyProgramFrontend.drawWheel(canvas, loyaltyWheelPrizes, currentRotation);

        if (progress < 1) {
          requestAnimationFrame(animate);
        } else {
          // Animation complete
          setTimeout(callback, 500);
        }
      }

      animate();
    },

    /**
     * Open wheel modal
     */
    openWheelModal: function (e) {
      e.preventDefault();
      var $trigger = $(this).closest('.loyalty-wheel-modal-trigger');
      var canSpin = $trigger.data('can-spin') === 1;
      var nextSpin = $trigger.data('next-spin');

      // Create modal overlay if it doesn't exist
      if ($('#loyalty-wheel-modal-overlay').length === 0) {
        $('body').append('<div id="loyalty-wheel-modal-overlay" class="loyalty-wheel-modal-overlay"></div>');
      }

      var $overlay = $('#loyalty-wheel-modal-overlay');
      var modalHtml = '<div class="loyalty-wheel-modal">';
      modalHtml += '<div class="loyalty-wheel-modal-header">';

      // Dynamiczny tytuł modala
      if (canSpin) {
        modalHtml += '<h2 class="loyalty-wheel-modal-title">' + loyaltyProgramFrontend.i18n.wheel_modal_title + '</h2>';
      } else {
        modalHtml += '<h2 class="loyalty-wheel-modal-title">' + loyaltyProgramFrontend.i18n.next_chance_in + '</h2>';
      }

      modalHtml += '<button type="button" class="loyalty-wheel-modal-close p-0">X</button>';
      modalHtml += '</div>';
      modalHtml += '<div class="loyalty-wheel-modal-body">';

      if (canSpin) {
        // Show wheel

        modalHtml += '<div class="loyalty-wheel-wrapper">';
        modalHtml += '<div class="loyalty-wheel-pointer-arrow"></div>';
        modalHtml += '<canvas id="loyalty-wheel-modal-canvas" width="483" height="483"></canvas>';
        modalHtml += '</div>';
        modalHtml += '<div class="loyalty-wheel-controls">';
        modalHtml +=
          '<button type="button" id="loyalty-spin-modal-btn" class="loyalty-spin-button">' +
          loyaltyProgramFrontend.i18n.spin_wheel +
          '</button>';
        modalHtml += '</div>';
      } else {
        // Show countdown (bez dodatkowego h3, bo tytuł jest już w headerze)
        modalHtml += '<div class="loyalty-wheel-modal-countdown">';
        modalHtml += '<div class="loyalty-countdown-timer" data-next-spin="' + nextSpin + '">';
        modalHtml += '<div class="loyalty-countdown-item">';
        modalHtml += '<span class="loyalty-countdown-number" data-type="hours">00</span>';
        modalHtml += '<span class="loyalty-countdown-label">' + loyaltyProgramFrontend.i18n.hours + '</span>';
        modalHtml += '</div>';
        modalHtml += '<div class="loyalty-countdown-item">';
        modalHtml += '<span class="loyalty-countdown-number" data-type="minutes">00</span>';
        modalHtml += '<span class="loyalty-countdown-label">' + loyaltyProgramFrontend.i18n.minutes + '</span>';
        modalHtml += '</div>';
        modalHtml += '<div class="loyalty-countdown-item">';
        modalHtml += '<span class="loyalty-countdown-number" data-type="seconds">00</span>';
        modalHtml += '<span class="loyalty-countdown-label">' + loyaltyProgramFrontend.i18n.seconds + '</span>';
        modalHtml += '</div>';
        modalHtml += '</div>';
        modalHtml += '</div>';
      }

      modalHtml += '</div>';
      modalHtml += '</div>';

      $overlay.html(modalHtml);
      $overlay.addClass('active');
      $('body').css('overflow', 'hidden');

      // Initialize wheel if can spin
      if (canSpin && typeof loyaltyWheelModalPrizes !== 'undefined') {
        var canvas = document.getElementById('loyalty-wheel-modal-canvas');
        if (canvas) {
          LoyaltyProgramFrontend.drawWheel(canvas, loyaltyWheelModalPrizes, 0);
        }
      } else {
        // Start countdown
        LoyaltyProgramFrontend.startCountdown();
      }
    },

    /**
     * Close wheel modal
     */
    closeWheelModal: function (e) {
      e.preventDefault();
      var $overlay = $('#loyalty-wheel-modal-overlay');
      var $modalBody = $('.loyalty-wheel-modal-body');

      // Check if result is shown (after spinning)
      if ($modalBody.data('result-shown')) {
        // Reload page if result is shown
        location.reload();
        return;
      }

      $overlay.removeClass('active');
      $('body').css('overflow', '');

      // Clear countdown interval if exists
      if (LoyaltyProgramFrontend.countdownInterval) {
        clearInterval(LoyaltyProgramFrontend.countdownInterval);
      }

      // Reset result-shown flag
      $modalBody.removeData('result-shown');

      setTimeout(function () {
        $overlay.html('');
      }, 300);
    },

    /**
     * Close modal when clicking overlay
     */
    closeWheelModalOverlay: function (e) {
      if ($(e.target).hasClass('loyalty-wheel-modal-overlay')) {
        LoyaltyProgramFrontend.closeWheelModal(e);
      }
    },

    /**
     * Initialize inline countdowns (on page load)
     */
    initInlineCountdowns: function () {
      var $inlineTimers = $('.loyalty-countdown-inline');
      if ($inlineTimers.length === 0) return;

      $inlineTimers.each(function () {
        var $timer = $(this);
        var nextSpin = parseInt($timer.data('next-spin'));

        function updateCountdown() {
          var now = Math.floor(Date.now() / 1000);
          var diff = nextSpin - now;

          if (diff <= 0) {
            // Reload page when time is up
            location.reload();
            return;
          }

          var hours = Math.floor(diff / 3600);
          var minutes = Math.floor((diff % 3600) / 60);
          var seconds = diff % 60;

          $timer.find('[data-type="hours"]').text(String(hours).padStart(2, '0'));
          $timer.find('[data-type="minutes"]').text(String(minutes).padStart(2, '0'));
          $timer.find('[data-type="seconds"]').text(String(seconds).padStart(2, '0'));
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
      });
    },

    /**
     * Start countdown timer (for modal)
     */
    startCountdown: function () {
      var $timer = $('.loyalty-countdown-timer').not('.loyalty-countdown-inline');
      if ($timer.length === 0) return;

      var nextSpin = parseInt($timer.data('next-spin'));

      function updateCountdown() {
        var now = Math.floor(Date.now() / 1000);
        var diff = nextSpin - now;

        if (diff <= 0) {
          // Reload page when time is up
          location.reload();
          return;
        }

        var hours = Math.floor(diff / 3600);
        var minutes = Math.floor((diff % 3600) / 60);
        var seconds = diff % 60;

        $timer.find('[data-type="hours"]').text(String(hours).padStart(2, '0'));
        $timer.find('[data-type="minutes"]').text(String(minutes).padStart(2, '0'));
        $timer.find('[data-type="seconds"]').text(String(seconds).padStart(2, '0'));
      }

      updateCountdown();
      LoyaltyProgramFrontend.countdownInterval = setInterval(updateCountdown, 1000);
    },

    /**
     * Spin wheel in modal
     */
    spinWheelModal: function (e) {
      e.preventDefault();
      var $button = $(this);

      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.spinning);

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_spin_wheel',
          nonce: loyaltyProgramFrontend.nonce,
        },
        function (response) {
          if (response.success) {
            // Animate wheel
            LoyaltyProgramFrontend.animateWheelModal(response.data.prize_index, function () {
              // Show result in modal
              var resultHtml = '<div class="loyalty-wheel-modal-result">';
              resultHtml += '<h3>' + loyaltyProgramFrontend.i18n.congratulations + '</h3>';
              resultHtml += '<div class="prize-name">' + response.data.prize_name + '</div>';
              resultHtml +=
                '<div class="prize-points">+' +
                response.data.points +
                ' ' +
                (loyaltyProgramFrontend.i18n.pkt || 'pkt') +
                '</div>';
              resultHtml +=
                '<button type="button" class="loyalty-wheel-modal-close-btn">' +
                loyaltyProgramFrontend.i18n.close +
                '</button>';
              resultHtml += '</div>';

              $('.loyalty-wheel-modal-body').html(resultHtml);

              // Mark that result is shown (for close button handler)
              $('.loyalty-wheel-modal-body').data('result-shown', true);

              // Show message for 2 seconds, then reload page
              setTimeout(function () {
                location.reload();
              }, 2000);
            });
          } else {
            SwalConfig.error(response.data.message || loyaltyProgramFrontend.i18n.error_occurred);
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.spin_wheel);
          }
        }
      ).fail(function () {
        SwalConfig.error(loyaltyProgramFrontend.i18n.connection_error);
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.spin_wheel);
      });
    },

    /**
     * Animate wheel in modal
     */
    animateWheelModal: function (winningIndex, callback) {
      var canvas = document.getElementById('loyalty-wheel-modal-canvas');
      if (!canvas) return;

      var numPrizes = loyaltyWheelModalPrizes.length;
      var degreesPerPrize = 360 / numPrizes;
      var targetDegree = 360 - winningIndex * degreesPerPrize - degreesPerPrize / 2;
      var totalRotation = 360 * 5 + targetDegree; // 5 full rotations + target
      var duration = 4000; // 4 seconds
      var startTime = Date.now();

      function easeOut(t) {
        return 1 - Math.pow(1 - t, 3);
      }

      function animate() {
        var now = Date.now();
        var elapsed = now - startTime;
        var progress = Math.min(elapsed / duration, 1);
        var eased = easeOut(progress);
        var currentRotation = totalRotation * eased;

        // Redraw wheel with rotation
        LoyaltyProgramFrontend.drawWheel(canvas, loyaltyWheelModalPrizes, currentRotation);

        if (progress < 1) {
          requestAnimationFrame(animate);
        } else {
          // Animation complete
          setTimeout(callback, 500);
        }
      }

      animate();
    },

    /**
     * Redeem reward
     */
    redeemReward: function (e) {
      e.preventDefault();
      var $button = $(this);
      var rewardIndex = $button.data('reward-index');
      var rewardType = $button.data('reward-type') || 'product';
      
      SwalConfig.confirm(loyaltyProgramFrontend.i18n.redeem_confirm).then(function(result) {
        if (!result.isConfirmed) {
          return;
        }
        
        // Continue with redemption
        $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.redeeming);
        
        $.post(
          loyaltyProgramFrontend.ajax_url,
          {
            action: 'loyalty_program_redeem_reward',
            nonce: loyaltyProgramFrontend.nonce,
            reward_index: rewardIndex,
            reward_type: rewardType
          },
          function (response) {
            if (response.success) {
              SwalConfig.success(response.data.message || loyaltyProgramFrontend.i18n.redeem_success).then(function() {
                location.reload();
              });
            } else {
              SwalConfig.error(response.data.message || loyaltyProgramFrontend.i18n.error_occurred);
              $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.redeem);
            }
          }
        ).fail(function () {
          SwalConfig.error(loyaltyProgramFrontend.i18n.connection_error);
          $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.redeem);
        });
      });
    },

    /**
     * Add reward to cart
     */
    addRewardToCart: function (e) {
      e.preventDefault();
      var $button = $(this);
      var uniqueRewardId = $button.data('unique-reward-id');

      console.log('Adding reward to cart:', uniqueRewardId);
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.adding);

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_add_reward_to_cart',
          nonce: loyaltyProgramFrontend.nonce,
          unique_reward_id: uniqueRewardId, // Send unique_reward_id, server validates everything else
        },
        function (response) {
          console.log('Add to cart response:', response);
          if (response.success) {
            // Show success message and reload to update UI
            SwalConfig.success(response.data.message).then(function() {
              location.reload();
            });
          } else {
            SwalConfig.error(response.data.message || loyaltyProgramFrontend.i18n.error_occurred);
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.add_to_cart);
          }
        }
      ).fail(function () {
        SwalConfig.error(loyaltyProgramFrontend.i18n.connection_error);
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.add_to_cart);
      });
    },

    /**
     * Save user consents (SMS and Newsletter)
     */
    saveConsents: function (e) {
      e.preventDefault();
      var $form = $(this);
      var $button = $('#save-consents-btn');
      // Try to find status element - can be either #consents-save-status or #consents-message
      var $status = $('#consents-save-status').length ? $('#consents-save-status') : $('#consents-message');

      // Get checkbox values
      var smsConsent = $('#loyalty_sms_consent').is(':checked') ? 'yes' : 'no';
      var newsletterConsent = $('#loyalty_newsletter_consent').is(':checked') ? 'yes' : 'no';

      // Get nonce from form - try both possible names
      var nonce = $form.find('input[name="nonce"]').val() || $form.find('input[name="loyalty_consents_nonce"]').val();

      // Disable button
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.saving || 'Saving...');
      $status.html('').removeClass('loyalty-success-message loyalty-error-message');

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_save_consents',
          nonce: nonce,
          sms_consent: smsConsent,
          newsletter_consent: newsletterConsent,
        },
        function (response) {
          if (response.success) {
            $status
              .html('<div class="loyalty-success-message">' + response.data.message + '</div>')
              .addClass('loyalty-success-message');

            // Remove success message after 5 seconds
            setTimeout(function () {
              $status.fadeOut(function () {
                $status.html('').removeClass('loyalty-success-message').show();
              });
            }, 5000);
          } else {
            $status
              .html(
                '<div class="loyalty-error-message">' +
                  (response.data.message ||
                    loyaltyProgramFrontend.i18n.error_saving_preferences ||
                    'An error occurred while saving your preferences.') +
                  '</div>'
              )
              .addClass('loyalty-error-message');
          }
          // Try to get original button text or use default
          var buttonText = $button.data('original-text') || loyaltyProgramFrontend.i18n.save_changes || 'Save changes';
          $button.prop('disabled', false).text(buttonText);
        }
      ).fail(function () {
        $status
          .html(
            '<div class="loyalty-error-message">' +
              (loyaltyProgramFrontend.i18n.connection_error || 'Connection error. Please try again.') +
              '</div>'
          )
          .addClass('loyalty-error-message');
        var buttonText = $button.data('original-text') || 'Save changes';
        $button.prop('disabled', false).text(buttonText);
      });
    },

    /**
     * Save birth date and earn points
     */
    saveBirthDate: function (e) {
      e.preventDefault();
      var $form = $(this);
      var $button = $('#save-birth-date-btn');
      var $status = $('#birth-date-save-status');

      // Get birth date value
      var birthDate = $('#loyalty_birth_date_input').val();

      if (!birthDate) {
        $status.html(
          '<div class="loyalty-error-message">' +
            (loyaltyProgramFrontend.i18n.please_select_birth_date || 'Please select your birth date.') +
            '</div>'
        );
        return;
      }

      // Get nonce from form
      var nonce = $form.find('input[name="loyalty_birth_date_nonce"]').val();

      // Disable button
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.saving || 'Saving...');
      $status.html('');

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_save_birth_date',
          nonce: nonce,
          birth_date: birthDate,
        },
        function (response) {
          if (response.success) {
            $status.html('<div class="loyalty-success-message">' + response.data.message + '</div>');

            // Reload page after 2 seconds to show completion view
            if (response.data.reload) {
              setTimeout(function () {
                location.reload();
              }, 2000);
            }
          } else {
            $status.html(
              '<div class="loyalty-error-message">' +
                (response.data.message ||
                  loyaltyProgramFrontend.i18n.error_saving_birth_date ||
                  'An error occurred while saving your birth date.') +
                '</div>'
            );
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.save_birth_date || 'Save Birth Date');
          }
        }
      ).fail(function () {
        $status.html('<div class="loyalty-error-message">' + 'Connection error. Please try again.' + '</div>');
        $button.prop('disabled', false).text('Save Birth Date');
      });
    },

    /**
     * Save account data (birth date and phone) via AJAX
     */
    saveAccountData: function (e) {
      e.preventDefault();
      var $form = $(this);
      var $button = $('#save-account-data-btn');
      var $message = $('#account-data-message');

      // Get form values
      var birthDate = $('#loyalty_program_birth_date').val() || '';
      var phone = $('#billing_phone').val() || '';

      // Get nonce from form
      var nonce = $form.find('input[name="nonce"]').val();

      // Disable button
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.saving || 'Saving...');
      $message.html('').removeClass('loyalty-success-message loyalty-error-message');

      var formData = {
        action: 'loyalty_program_save_account_data',
        nonce: nonce,
      };

      if (birthDate) {
        formData.loyalty_program_birth_date = birthDate;
      }

      if (phone) {
        formData.billing_phone = phone;
      }

      $.post(loyaltyProgramFrontend.ajax_url, formData, function (response) {
        if (response.success) {
          $message
            .html('<div class="loyalty-success-message">' + response.data.message + '</div>')
            .addClass('loyalty-success-message');

          // Remove success message after 5 seconds
          setTimeout(function () {
            $message.fadeOut(function () {
              $message.html('').removeClass('loyalty-success-message').show();
            });
          }, 5000);
        } else {
          $message
            .html(
              '<div class="loyalty-error-message">' +
                (response.data.message ||
                  loyaltyProgramFrontend.i18n.error_saving_data ||
                  'An error occurred while saving your data.') +
                '</div>'
            )
            .addClass('loyalty-error-message');
        }
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.save || 'Save');
      }).fail(function () {
        $message
          .html(
            '<div class="loyalty-error-message">' +
              (loyaltyProgramFrontend.i18n.connection_error || 'Connection error. Please try again.') +
              '</div>'
          )
          .addClass('loyalty-error-message');
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.save || 'Save');
      });
    },

    /**
     * Save account data (birth date, phone, consents) for non-members via AJAX
     */
    saveAccountDataNonMember: function (e) {
      e.preventDefault();
      var $form = $(this);
      var $button = $('#save-account-data-btn-non-member');
      var $message = $('#account-data-message-non-member');

      // Get form values
      var birthDate = $('#loyalty_program_birth_date_non_member').val() || '';
      var phone = $('#billing_phone_non_member').val() || '';
      var joinConsent = $('#loyalty_join_consent_non_member').is(':checked') ? 'yes' : 'no';
      var newsletterConsent = $('#loyalty_newsletter_consent_non_member').is(':checked') ? 'yes' : 'no';
      var smsConsent = $('#loyalty_sms_consent_non_member').is(':checked') ? 'yes' : 'no';

      // Validate required join consent
      if (joinConsent !== 'yes') {
        $message
          .html(
            '<div class="loyalty-error-message">' +
              (loyaltyProgramFrontend.i18n.join_consent_required ||
                'You must consent to joining the loyalty program.') +
              '</div>'
          )
          .addClass('loyalty-error-message');
        return;
      }

      // Get nonce from form
      var nonce = $form.find('input[name="nonce"]').val();

      // Disable button
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.saving || 'Saving...');
      $message.html('').removeClass('loyalty-success-message loyalty-error-message');

      var formData = {
        action: 'loyalty_program_save_account_data_non_member',
        nonce: nonce,
        join_consent: joinConsent,
        newsletter_consent: newsletterConsent,
        sms_consent: smsConsent,
      };

      if (birthDate) {
        formData.loyalty_program_birth_date = birthDate;
      }

      if (phone) {
        formData.billing_phone = phone;
      }

      $.post(loyaltyProgramFrontend.ajax_url, formData, function (response) {
        if (response.success) {
          $message
            .html('<div class="loyalty-success-message">' + response.data.message + '</div>')
            .addClass('loyalty-success-message');

          // If user joined the program, reload page to show member form
          if (response.data.user_joined) {
            setTimeout(function () {
              location.reload();
            }, 2000);
          } else {
            // Remove success message after 5 seconds
            setTimeout(function () {
              $message.fadeOut(function () {
                $message.html('').removeClass('loyalty-success-message').show();
              });
            }, 5000);
          }
        } else {
          $message
            .html(
              '<div class="loyalty-error-message">' +
                (response.data.message ||
                  loyaltyProgramFrontend.i18n.error_saving_data ||
                  'An error occurred while saving your data.') +
                '</div>'
            )
            .addClass('loyalty-error-message');
        }
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.save_changes || 'Save changes');
      }).fail(function () {
        $message
          .html(
            '<div class="loyalty-error-message">' +
              (loyaltyProgramFrontend.i18n.connection_error || 'Connection error. Please try again.') +
              '</div>'
          )
          .addClass('loyalty-error-message');
        $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.save_changes || 'Save changes');
      });
    },

    /**
     * Handle rating stars click
     */
    handleRating: function () {
      var $star = $(this);
      var value = $star.data('value');
      var $container = $star.closest('.rating-stars');
      var $question = $star.closest('.survey-question');
      var $surveyContainer = $question.closest('.loyalty-survey-container');

      // Update hidden input
      $container.find('input[type="hidden"]').val(value);

      // Update visual state
      $container.find('.star').each(function (index) {
        var $starIcon = $(this).find('.star-icon');
        if (index < value) {
          $(this).addClass('active');
          $starIcon.find('path').attr('fill', '#f59e0b').attr('stroke', '#f59e0b');
        } else {
          $(this).removeClass('active');
          $starIcon.find('path').attr('fill', '#dcdcde').attr('stroke', '#dcdcde');
        }
      });

      // Remove error state if exists
      $question.removeClass('has-error');
      $question.find('.answer-required-error').fadeOut(300, function () {
        $(this).remove();
      });

      // Update pagination buttons if pagination is enabled
      if ($surveyContainer.length && $surveyContainer.data('pagination') == 1) {
        LoyaltyProgramFrontend.updateSurveyPaginationButtons($surveyContainer);
      }
    },

    /**
     * Start survey (show form and timer)
     */
    startSurvey: function (e) {
      e.preventDefault();
      var $button = $(this);
      var $container = $button.closest('.loyalty-survey-container');
      var $form = $container.find('.loyalty-survey-form');
      var $timer = $container.find('.survey-timer');

      // Add class to body
      $('body').addClass('show_survey');

      // Hide start button
      $button.closest('.survey-start-wrapper').fadeOut(300, function () {
        // Show form
        $form.fadeIn(300, function () {
          // Set section height after form is visible
          var containerHeight = $container.outerHeight();
          var $section = $container.closest('section');
          if ($section.length) {
            $section.css('height', containerHeight + 40 + 'px');
          }
        });

        // Show and start timer if enabled
        if ($container.data('has-timer')) {
          $timer.fadeIn(300);
          LoyaltyProgramFrontend.initSurveyTimer($container);
        }
      });
    },

    /**
     * Initialize survey timer
     */
    initSurveyTimer: function ($container) {
      if (!$container) {
        $container = $('.loyalty-survey-container');
      }

      var $timer = $container.find('.survey-timer');
      if (!$timer.length) return;

      var $timeDisplay = $timer.find('.time-remaining');
      var timeText = $timeDisplay.text();
      var parts = timeText.split(':');
      var totalSeconds = parseInt(parts[0]) * 60 + parseInt(parts[1]);

      var $form = $container.find('.loyalty-survey-form');
      var $submitBtn = $container.find('.loyalty-submit-survey-btn');

      var countdown = setInterval(function () {
        totalSeconds--;

        if (totalSeconds <= 0) {
          clearInterval(countdown);
          $timeDisplay.text('0:00');
          $submitBtn.prop('disabled', true);
          SwalConfig.warning(loyaltyProgramFrontend.i18n.time_is_up || 'Time is up! Survey has been auto-submitted.');

          // Mark as auto-submit to skip validation
          $form.data('auto-submit', true);
          $form.submit();
          return;
        }

        var minutes = Math.floor(totalSeconds / 60);
        var seconds = totalSeconds % 60;
        $timeDisplay.text(minutes + ':' + (seconds < 10 ? '0' : '') + seconds);

        // Warning at 1 minute
        if (totalSeconds === 60) {
          $timer.css('background', '#fde68a');
        }

        // Critical at 30 seconds
        if (totalSeconds === 30) {
          $timer.css('background', '#fecaca');
          $timer.find('.dashicons').css('color', '#dc2626');
          $timeDisplay.parent().css('color', '#dc2626');
        }
      }, 1000);
    },

    /**
     * Submit survey/quiz
     */
    submitSurvey: function (e) {
      e.preventDefault();
      var $form = $(this);
      var $container = $form.closest('.loyalty-survey-container');
      var $button = $container.find('.loyalty-submit-survey-btn');
      var $result = $container.find('#survey-result');

      // Check if this is auto-submit (from timer expiry)
      var isAutoSubmit = $form.data('auto-submit');

      // Validate required questions ONLY if NOT auto-submit
      if (!isAutoSubmit) {
        var firstInvalid = null;
        var hasErrors = false;

        // Remove previous error messages
        $('.answer-required-error').remove();

        $container.find('.survey-question[data-required="1"]').each(function () {
          var $question = $(this);
          var questionIndex = $question.data('question-index');
          var answerType = $question.find('.question-answers').data('answer-type');
          var isAnswered = false;

          // Check if question is answered based on type
          switch (answerType) {
            case 'radio':
              isAnswered = $question.find('input[type="radio"]:checked').length > 0;
              break;
            case 'checkbox':
              isAnswered = $question.find('input[type="checkbox"]:checked').length > 0;
              break;
            case 'text':
            case 'number':
              isAnswered = !!$question.find('input[type="text"], input[type="number"]').val().trim();
              break;
            case 'textarea':
              isAnswered = !!$question.find('textarea').val().trim();
              break;
            case 'rating':
              isAnswered = !!$question.find('input[name="answers[' + questionIndex + ']"]').val();
              break;
          }

          if (!isAnswered) {
            hasErrors = true;
            $question.addClass('has-error');

            // Add error message
            var errorMsg = $('<div class="answer-required-error"></div>').text(
              loyaltyProgramFrontend.i18n.answer_required
            );
            $question.find('.question-answers').after(errorMsg);

            // Store first invalid question
            if (!firstInvalid) {
              firstInvalid = $question;
            }
          } else {
            $question.removeClass('has-error');
          }
        });

        // If there are errors, scroll to first invalid and stop
        if (hasErrors) {
          $('html, body').animate(
            {
              scrollTop: firstInvalid.offset().top - 100,
            },
            500
          );
          return false;
        }
      } // End validation block

      // Collect form data
      var formData = $form.serializeArray();
      var surveyId = $form.find('input[name="survey_id"]').val();
      var nonce = $form.find('input[name="survey_nonce"]').val();

      // Clear auto-submit flag for future submissions
      $form.data('auto-submit', false);

      // Disable button
      $button.prop('disabled', true).text(loyaltyProgramFrontend.i18n.submitting || 'Submitting...');
      $result.html('');

      // Show spinner
      var $spinner = $('<div class="loyalty-survey-spinner"><div class="spinner"></div></div>');
      $('body').append($spinner);
      $spinner.css('display', 'flex').fadeIn(200);

      $.post(
        loyaltyProgramFrontend.ajax_url,
        {
          action: 'loyalty_program_submit_survey',
          survey_id: surveyId,
          nonce: nonce,
          answers: $form.serialize(),
        },
        function (response) {
          // Hide spinner
          $('.loyalty-survey-spinner').fadeOut(200, function () {
            $(this).remove();
          });

          if (response.success) {
            // Build success message
            var successHtml = '<div class="loyalty-success-message">' + response.data.message;

            // Show quiz result if available
            if (response.data.score_percentage !== undefined) {
              successHtml += '<div class="quiz-result-display">';
              successHtml += '<h4>' + (loyaltyProgramFrontend.i18n.your_result || 'Your Result') + '</h4>';
              successHtml += '<div class="result-percentage">' + response.data.score_percentage + '%</div>';
              successHtml +=
                '<p>' +
                response.data.correct_answers +
                ' ' +
                (loyaltyProgramFrontend.i18n.out_of || 'out of') +
                ' ' +
                response.data.total_questions +
                ' ' +
                (loyaltyProgramFrontend.i18n.correct || 'correct') +
                '</p>';
              successHtml += '</div>';
            }

            // Show points earned
            if (response.data.points_earned) {
              successHtml +=
                '<div class="points-earned"><strong>' +
                (loyaltyProgramFrontend.i18n.points_earned || 'Points earned:') +
                ' ' +
                response.data.points_earned +
                '</strong></div>';
            } else if (response.data.points_earned === 0 && response.data.minimum_not_reached) {
              successHtml +=
                '<div class="points-info">' +
                (loyaltyProgramFrontend.i18n.you_needed || 'You needed') +
                ' ' +
                response.data.min_percentage +
                (loyaltyProgramFrontend.i18n.percent_to_earn_points || '% to earn points.') +
                '</div>';
            }

            successHtml += '</div>';
            $result.html(successHtml);

            // Hide form
            $form.fadeOut();

            // Redirect if configured
            if (response.data.redirect_url) {
              setTimeout(function () {
                window.location.href = response.data.redirect_url;
              }, 2000);
            } else {
              // Reload to show thank you message
              setTimeout(function () {
                location.reload();
              }, 2000);
            }
          } else {
            $result.html(
              '<div class="loyalty-error-message">' + (response.data.message || 'An error occurred.') + '</div>'
            );
            $button.prop('disabled', false).text(loyaltyProgramFrontend.i18n.submit || 'Submit');
          }
        }
      ).fail(function () {
        // Hide spinner on error
        $('.loyalty-survey-spinner').fadeOut(200, function () {
          $(this).remove();
        });
        $result.html('<div class="loyalty-error-message">' + 'Connection error. Please try again.' + '</div>');
        $button.prop('disabled', false).text('Submit');
      });
    },

    /**
     * Initialize survey pagination
     */
    initSurveyPagination: function () {
      var $containers = $('.loyalty-survey-container[data-pagination="1"]');
      if ($containers.length === 0) return;

      $containers.each(function () {
        var $container = $(this);
        var $questions = $container.find('.survey-question');

        if ($questions.length <= 1) return; // No pagination needed for single question

        // Hide all questions except first
        $questions.hide();
        $questions.eq(0).show();

        // Initialize current page
        $container.data('current-page', 0);

        // Update button state for first question
        LoyaltyProgramFrontend.updateSurveyPaginationButtons($container);

        // Set section height if form is visible and pagination enabled
        var $form = $container.find('.loyalty-survey-form');
        if ($form.is(':visible')) {
          // Add show_survey class to body
          $('body').addClass('show_survey');

          setTimeout(function () {
            var containerHeight = $container.outerHeight();
            var $section = $container.closest('section');
            if ($section.length) {
              $section.css('height', containerHeight + 40 + 'px');
            }
          }, 100); // Small delay to ensure DOM is ready
        }
      });
    },

    /**
     * Check if current question is answered (for pagination)
     */
    checkSurveyQuestionAnswer: function () {
      var $question = $(this).closest('.survey-question');
      var $container = $question.closest('.loyalty-survey-container');

      if ($container.data('pagination') != 1) return;

      LoyaltyProgramFrontend.updateSurveyPaginationButtons($container);
    },

    /**
     * Update pagination buttons state
     */
    updateSurveyPaginationButtons: function ($container) {
      var currentPage = $container.data('current-page') || 0;
      var $questions = $container.find('.survey-question');
      var totalPages = $questions.length;
      var $currentQuestion = $questions.eq(currentPage);
      var $nextBtn = $container.find('.loyalty-survey-next-btn');
      var $submitBtn = $container.find('.loyalty-submit-survey-btn');

      // Update counter
      $container.find('.current-question-num').text(currentPage + 1);

      // Check if current question is answered
      var isAnswered = LoyaltyProgramFrontend.isQuestionAnswered($currentQuestion);
      var isRequired = $currentQuestion.data('required') == 1;

      // Enable/disable next button based on required + answered
      if (isRequired) {
        $nextBtn.prop('disabled', !isAnswered);
      } else {
        $nextBtn.prop('disabled', false);
      }

      // Show appropriate button (Next or Submit)
      if (currentPage === totalPages - 1) {
        // Last question - show submit button
        $nextBtn.hide();
        $submitBtn.show();

        // Enable/disable submit button
        if (isRequired) {
          $submitBtn.prop('disabled', !isAnswered);
        } else {
          $submitBtn.prop('disabled', false);
        }
      } else {
        // Not last question - show next button
        $nextBtn.show();
        $submitBtn.hide();
      }
    },

    /**
     * Check if question is answered
     */
    isQuestionAnswered: function ($question) {
      var answerType = $question.find('.question-answers').data('answer-type');
      var isAnswered = false;

      switch (answerType) {
        case 'radio':
          isAnswered = $question.find('input[type="radio"]:checked').length > 0;
          break;
        case 'checkbox':
          isAnswered = $question.find('input[type="checkbox"]:checked').length > 0;
          break;
        case 'text':
        case 'number':
          isAnswered = !!$question.find('input[type="text"], input[type="number"]').val().trim();
          break;
        case 'textarea':
          isAnswered = !!$question.find('textarea').val().trim();
          break;
        case 'rating':
          var questionIndex = $question.data('question-index');
          var $ratingInput = $question.find('input[name="answers[' + questionIndex + ']"]');
          isAnswered = !!$ratingInput.val() && $ratingInput.val() !== '';
          break;
      }

      return isAnswered;
    },

    /**
     * Go to next question
     */
    surveyNextQuestion: function (e) {
      e.preventDefault();
      var $container = $(this).closest('.loyalty-survey-container');
      var currentPage = $container.data('current-page') || 0;
      var $questions = $container.find('.survey-question');
      var totalPages = $questions.length;

      if (currentPage < totalPages - 1) {
        // Hide current question
        $questions.eq(currentPage).fadeOut(300, function () {
          // Show next question
          currentPage++;
          $container.data('current-page', currentPage);
          $questions.eq(currentPage).fadeIn(300, function () {
            // Update section height after question is visible
            var containerHeight = $container.outerHeight();
            var $section = $container.closest('section');
            if ($section.length) {
              $section.css('height', containerHeight + 40 + 'px');
            }
          });

          // Update buttons
          LoyaltyProgramFrontend.updateSurveyPaginationButtons($container);

          // Scroll to top of survey
          $('html, body').animate(
            {
              scrollTop: $container.offset().top - 100,
            },
            300
          );
        });
      }
    },

    /**
     * Quit survey
     */
    surveyQuit: function (e) {
      e.preventDefault();

      // Remove show_survey class from body
      $('body').removeClass('show_survey');

      // Hide survey form
      var $container = $(this).closest('.loyalty-survey-container');
      var $form = $container.find('.loyalty-survey-form');

      // Remove section height override
      var $section = $container.closest('section');
      if ($section.length) {
        $section.css('height', '');
      }

      $form.fadeOut(300, function () {
        // Reset to first question
        var $questions = $container.find('.survey-question');
        $questions.hide();
        $questions.eq(0).show();
        $container.data('current-page', 0);

        // Show start button if configured
        if ($container.data('has-start-button') == 1) {
          $container.find('.survey-start-wrapper').fadeIn(300);
        }

        // Clear all answers
        $form.find('input[type="radio"], input[type="checkbox"]').prop('checked', false);
        $form.find('input[type="text"], input[type="number"], textarea').val('');
        $form.find('.rating-stars .star').removeClass('active');
        $form.find('.rating-stars .star .star-icon path').attr('fill', '#dcdcde').attr('stroke', '#dcdcde');
        $form.find('.rating-stars input[type="hidden"]').val('');
      });
    },
  };

  /**
   * Document ready
   */
  $(document).ready(function () {
    if (typeof loyaltyProgramFrontend !== 'undefined') {
      LoyaltyProgramFrontend.init();
    }

    /**
     * Attendance Action Click Handler
     */
    $(document).on('click', '.loyalty-attendance-button.clickable, .loyalty-attendance-text.clickable', function () {
      var $container = $(this).closest('.loyalty-attendance-action');
      var actionId = $container.data('action-id');
      var canClick = $container.data('can-click');
      var points = $container.data('points');
      var $button = $(this);
      var $response = $container.find('.action-response-message');

      if (!canClick) {
        return;
      }

      // Disable button immediately
      $button.removeClass('clickable').addClass('disabled').prop('disabled', true);
      $response.removeClass('success error').hide();

      // Show loading
      var originalContent = $button.html();
      $button.html(
        '<span class="dashicons dashicons-update spinning"></span> ' + loyaltyProgramFrontend.i18n.processing
      );

      // AJAX request
      $.ajax({
        url: loyaltyProgramFrontend.ajax_url,
        type: 'POST',
        data: {
          action: 'loyalty_attendance_click',
          nonce: loyaltyProgramFrontend.nonce,
          action_id: actionId,
        },
        success: function (response) {
          if (response.success) {
            // Success - show message
            $response
              .addClass('success')
              .html('✓ ' + response.data.message)
              .fadeIn();

            // Update container status
            $container.removeClass('active').addClass('completed');
            $container.find('.action-status-message').text(loyaltyProgramFrontend.i18n.attendance_completed);

            // Keep button disabled with success state
            $button.html(originalContent);

            // Hide success message after 3 seconds
            setTimeout(function () {
              $response.fadeOut(300, function () {
                $response.removeClass('success').hide();
              });
            }, 3000);
          } else {
            // Error
            $response
              .addClass('error')
              .html('✗ ' + (response.data.message || loyaltyProgramFrontend.i18n.error_general))
              .fadeIn();

            // Re-enable button on error
            $button.addClass('clickable').removeClass('disabled').prop('disabled', false);
            $button.html(originalContent);
          }
        },
        error: function () {
          $response
            .addClass('error')
            .html('✗ ' + loyaltyProgramFrontend.i18n.error_general)
            .fadeIn();

          $button.addClass('clickable').removeClass('disabled').prop('disabled', false);
          $button.html(originalContent);
        },
      });
    });

    // Add spinning animation for loading icon
    $(
      '<style>.spinning { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>'
    ).appendTo('head');

    /**
     * Join Modal Button Handler
     */
    $(document).on('click', '.loyalty-join-modal-btn', function (e) {
      e.preventDefault();
      var $button = $(this);
      // Use attr() instead of data() to get string value, not parsed number
      var isLoggedInAttr = $button.attr('data-is-logged-in');
      var isLoggedIn = isLoggedInAttr === '1';

      console.log('Loyalty Program: Button clicked, isLoggedIn attr:', isLoggedInAttr, 'isLoggedIn:', isLoggedIn);

      if (isLoggedIn) {
        var $modal = $('#loyalty-join-modal-logged');
        console.log('Loyalty Program: Looking for #loyalty-join-modal-logged, found:', $modal.length);
        if ($modal.length === 0) {
          console.error('Loyalty Program: Modal #loyalty-join-modal-logged not found in DOM');
          return;
        }
        // Remove inline style and show modal
        $modal.removeAttr('style').show();
        console.log('Loyalty Program: Modal #loyalty-join-modal-logged shown');
      } else {
        var $modal = $('#loyalty-join-modal-register');
        console.log('Loyalty Program: Looking for #loyalty-join-modal-register, found:', $modal.length);
        if ($modal.length === 0) {
          console.error('Loyalty Program: Modal #loyalty-join-modal-register not found in DOM');
          return;
        }
        // Remove inline style and show modal
        $modal.removeAttr('style').show();
        console.log('Loyalty Program: Modal #loyalty-join-modal-register shown');
      }
    });

    /**
     * Close Modal Handler
     */
    $(document).on('click', '.loyalty-join-modal-close', function () {
      $(this).closest('.loyalty-join-modal').hide();
    });

    /**
     * Close Modal on Outside Click
     */
    $(document).on('click', '.loyalty-join-modal', function (e) {
      if ($(e.target).hasClass('loyalty-join-modal')) {
        $(this).hide();
      }
    });

    /**
     * Handle Logged In Form Submission
     */
    $(document).on('submit', '.loyalty-join-form', function (e) {
      e.preventDefault();
      var $form = $(this);
      var $message = $form.find('.loyalty-form-message');
      var $submit = $form.find('button[type="submit"]');
      var originalText = $submit.text();

      $message.hide().removeClass('success error');
      $submit.prop('disabled', true).text(loyaltyProgramFrontend.i18n.processing || 'Processing...');

      $.ajax({
        url: loyaltyProgramFrontend.ajax_url,
        type: 'POST',
        data: {
          action: 'loyalty_join_program',
          nonce: loyaltyProgramFrontend.nonce,
          consent: $form.find('input[name="consent"]').is(':checked') ? 'yes' : 'no',
        },
        success: function (response) {
          if (response.success) {
            $message.addClass('success').text(response.data.message).show();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            $message
              .addClass('error')
              .text(response.data.message || loyaltyProgramFrontend.i18n.error_occurred || 'An error occurred.')
              .show();
            $submit.prop('disabled', false).text(originalText);
          }
        },
        error: function () {
          $message
            .addClass('error')
            .text(loyaltyProgramFrontend.i18n.connection_error || 'An error occurred. Please try again.')
            .show();
          $submit.prop('disabled', false).text(originalText);
        },
      });
    });

    /**
     * Handle Register Form Submission
     */
    $(document).on('submit', '.loyalty-join-form-register', function (e) {
      e.preventDefault();
      var $form = $(this);
      var $message = $form.find('.loyalty-form-message');
      var $submit = $form.find('button[type="submit"]');
      var originalText = $submit.text();

      $message.hide().removeClass('success error');
      $submit.prop('disabled', true).text(loyaltyProgramFrontend.i18n.processing || 'Processing...');

      var formData = {
        action: 'loyalty_register_and_join',
        nonce: loyaltyProgramFrontend.nonce,
        first_name: $form.find('input[name="first_name"]').val(),
        email: $form.find('input[name="email"]').val(),
        phone: $form.find('input[name="phone"]').val(),
        birth_date: $form.find('input[name="birth_date"]').val() || '',
        newsletter_consent: $form.find('input[name="newsletter_consent"]').is(':checked') ? 'yes' : 'no',
        sms_consent: $form.find('input[name="sms_consent"]').is(':checked') ? 'yes' : 'no',
        terms_consent: $form.find('input[name="terms_consent"]').is(':checked') ? 'yes' : 'no',
      };

      // Add custom consents
      $form.find('input[name^="custom_consent_"]').each(function () {
        var name = $(this).attr('name');
        formData[name] = $(this).is(':checked') ? 'yes' : 'no';
      });

      $.ajax({
        url: loyaltyProgramFrontend.ajax_url,
        type: 'POST',
        data: formData,
        success: function (response) {
          if (response.success) {
            $message.addClass('success').text(response.data.message).show();
            setTimeout(function () {
              location.reload();
            }, 1500);
          } else {
            $message
              .addClass('error')
              .text(response.data.message || loyaltyProgramFrontend.i18n.error_occurred || 'An error occurred.')
              .show();
            $submit.prop('disabled', false).text(originalText);
          }
        },
        error: function () {
          $message
            .addClass('error')
            .text(loyaltyProgramFrontend.i18n.connection_error || 'An error occurred. Please try again.')
            .show();
          $submit.prop('disabled', false).text(originalText);
        },
      });
    });
  });
})(jQuery);
