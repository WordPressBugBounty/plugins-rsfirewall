/*
 * @package    RSFirewall!
 * @copyright  (c) 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
/**
 * Function to download the original file from the remote server
 * and overwrite the one stored locally.
 *
 * @type {{download: RSFirewall.diffs.download}}
 */

/**
 * A small check, we don't need to overwrite RSFirewall objects if they do exist
 */
if (typeof RSFirewall != 'object') {
    var RSFirewall = {};
}

RSFirewall.diffs = {
    /**
     * Function that downloads the original file from the wordpress repository.
     *
     * @param $local                PATH TO FILE
     * @param $hid                  HASH INDEX (to remove it from the table)
     * @param $download_button      BUTTON OBJECT
     * @returns {boolean}
     *
     */
    _download: function ($local, $hid, $download_button) {
        var $data = {
                'action': 'rsfirewall_diff_download_file',
                'file': $local
            },
            arguments = {
                hashid: $hid,
                button: $download_button
            };

        /**
         * Send the request through AJAX
         */
        RSFirewall.diffs.proxy($data, this.downloadCallback, arguments);
    },
    download: function ($local, $hid, $download_button, $is_missing) {
        if (typeof $download_button == 'undefined') {
            $download_button = false;
        }
        if (typeof $is_missing == 'undefined') {
            $is_missing = false;
        }
        var $message = typeof rsfirewall_check_locale != 'undefined' ? rsfirewall_check_locale.confirm_overwrite : rsfirewall_diff_locale.confirm_overwrite;

        // if the file is missing don't ask for override confirmation
        if ($is_missing) {
            $message = typeof rsfirewall_check_locale != 'undefined' ? rsfirewall_check_locale.confirm_add : rsfirewall_diff_locale.confirm_add;
        }

        vex.dialog.confirm({
            message: $message,
            callback: function (value) {
                if (value) {
                    RSFirewall.diffs._download($local, $hid, $download_button);
                }
            }
        });
    },
    /**
     * Upon completion, run this callback.
     *
     * @param json
     * @param args
     */
    downloadCallback: function (json, args) {
        var element = jQuery('#' + args.hashid);
        var current_view = args.hashid.indexOf('dash') >= 0 ? 'dashboard' : 'system_check';
        var message='';
        if (json.status) {
            message += '<div class="alert alert-success">' + json.message + '</div>';

        } else {
            message += '<div class="alert alert-danger">' + json.message + '</div>';
        }

        // add the message to the correct element in the System Check view
        if (element.length && current_view == 'system_check') {
            element.find('.rs_fix_response').empty().append(message);
        }

        // add the message to the correct element in the Dashboard view
        if (element.length && current_view == 'dashboard') {
            element.slideUp('fast').remove();
            // remove the entire box if there are no more quick actions left
            if (jQuery('.rs-box > .quick-action').length == 0) {
                jQuery('.quick-actions-box').parent().remove();
            }
        }

        if (args.button) {
            jQuery(args.button).parents('.rsfirewall-replace-original').empty().append(message);
        }
    },

    /**
     * Proxy function to call the ajax requests. upon completion it will fire a callback function
     *
     * @param $data
     * @param callback
     * @param args
     */
    proxy: function ($data, callback, args) {
        jQuery.ajax({
            converters: {
                "text json": RSFirewall.parseJSON
            },
            dataType: 'json',
            type: 'POST',
            url: ajaxurl,
            data: $data,
            complete: function (json) {
                var response = RSFirewall.parseJSON(json.responseText);
                callback(response, args);
            }
        });
    }
};