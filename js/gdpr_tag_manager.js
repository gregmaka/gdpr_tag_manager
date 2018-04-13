(function ($) {
  Drupal.behaviors.moduleeuconsent = {
    attach: function (context, settings) {
      $(document).scroll(function () {
        var scroll_len = drupalSettings.pop_up_scroll;
        var scrollBottom = $(window).scrollTop() + $(window).height();
        scrollBottom > scroll_len ? $('.cc-window').fadeOut() : $('.cc-window').fadeIn();
      });
      var msg = drupalSettings.pop_up_msg
          pp_href = drupalSettings.privacy_policy_link
          gtm_container = drupalSettings.gtm_container
          needs_cookie = drupalSettings.cookie_activate
          cookie_time = drupalSettings.cookie_duration
          show_popup_us = drupalSettings.show_popup_us
          button_color = drupalSettings.button_color
          background_color = drupalSettings.background_color
          link_text = drupalSettings.link_text
          pop_up_position = drupalSettings.pop_up_position;

      $.ajax({
        url: "/ajax/continent", success: function (result) {
          console.log("This users region is : ",result['c_code']);
          if ( result['c_code'] == 'NA') {
            console.log('User is in North America, running scripts');
          } else {
            console.log('NOT RUNNING TRACKING SCRIPTS');
          }
          if (result['c_code'] != 'NA' || show_popup_us == 0) {
            window.cookieconsent.initialise({
              palette: {
                popup: {
                  background: background_color,
                },
                button: {
                  background: button_color,
                }
              },
              position: pop_up_position,
              content: {
                message: msg,
                dismiss: 'Close',
                link: link_text,
                href: pp_href
              }
            });
          }
          if ((result['c_code'] == 'NA' || result['c_code'] == 'undefined') && needs_cookie === 1) {
            Cookies.set('not_eu', 'true', { expires: parseInt(cookie_time) });
          }
          dataLayer = [{
            'gdpr': result['c_code']
          }];
          (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
          new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
          j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
          'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
          })(window,document,'script','dataLayer',gtm_container);
        }
      });
    }
  };
}(jQuery));
