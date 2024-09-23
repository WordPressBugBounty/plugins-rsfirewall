// Keep monitoring the AJAX requests for the AJAX logins (Ex: AJAX Login and Registration modal popup)
jQuery(document).ajaxComplete(function( event, xhr, settings ) {
    var is_action_found = false;
    var accepted_ajax_actions = [
        'lrm_action=login'
    ];

    for(var i=0; i < accepted_ajax_actions.length; i++) {
        if (settings.data.indexOf(accepted_ajax_actions[i])) {
            is_action_found = true;
            break;
        }
    }

    if (is_action_found) {
        var response = RSFirewall.parseJSON(xhr.responseText);
        // if the login action is not ok we need to show the previous fields again and delete the ones we have created
        if (!response.success) {
            // if tfa present call the rebuild function
            if (typeof RSFirewall.FrontEndTFA.rebuild_initial_form == 'function') {
                RSFirewall.FrontEndTFA.rebuild_initial_form();
            }

            // lets check if the response has generated a recaptcha field and if so lets built it, only if not previously build
            if (typeof response.data.rsf_captcha != 'undefined' &&  jQuery('div.lrm-integrations--login').last().find('.g-recaptcha').length == 0) {
                // add to the DOM the proper script for the reCaptcha
                jQuery.getScript( 'https://www.google.com/recaptcha/api.js').done(function(script, textStatus) {
                    jQuery('div.lrm-integrations--login').last().append(response.data.rsf_captcha);
                });

            }
        }
    }
});