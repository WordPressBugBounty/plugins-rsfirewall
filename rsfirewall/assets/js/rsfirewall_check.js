/*
 * @package    RSFirewall!
 * @copyright  (c) 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */

(function ($) {
    'use strict';

    /**
     * We initiate scripts
     */
    $(document).ready(function () {
        /**
         * We need to calculate how much does a STEP worth
         */
        RSFirewall.Grade.calcPerItem();
        /**
         * Add functionality to the start check button
         */
        $('#start-check').click(function () {
            jQuery(this).fadeOut('fast');
            jQuery('.loading-holder .dashicons-update').addClass('rsfirewall-animated');
            jQuery('.loading-holder').show();
            jQuery('.scanning-message h3').text(rsfirewall_check_locale.scanning_in_progress);
            jQuery('.scanning-message').show();
            jQuery('#system-check-container').show();
            RSFirewall.System.Check.startCheck();
        });

        /**
         * Add functionality to the dropdown elements
         */
        RSFirewall.Utils.Dropdowns($('.system-check-step'));

        /**
         * Certain steps require tabular data instead of simple explanation
         * #signatures_check
         */
        jQuery('#signatures_check').find('p').replaceWith('<button class="rsfirewall-fix-action" style="margin-bottom:10px" onclick="RSFirewall.System.Fix.ignoreFiles()">' + rsfirewall_check_locale.ignore_button + '</button> <br />' +
            '<div class="alert alert-info">'+ rsfirewall_check_locale.ignore_files_warning +'</div>'+
            '<table class="widefat"><thead>' +
            '<tr><th>#</th><th>' + rsfirewall_check_locale.file + '</th><th>' + rsfirewall_check_locale.reason + '</th><th>' + rsfirewall_check_locale.match + '</th><th>' + rsfirewall_check_locale.link + '</th></tr>' +
            '</thead></table>');

        /**
         * #hashes_check
         */
        jQuery('#hashes_check').find('p').replaceWith('<button class="rsfirewall-fix-action" style="margin-bottom:10px" id="rsfirewall_ignore_hashes_button" onclick="RSFirewall.System.Fix.ignoreHashes()">' + rsfirewall_check_locale.ignore_hashes + '</button> <br />' +
            '<div class="alert alert-info">'+ rsfirewall_check_locale.accept_hash_changes +'</div>'+
            '<table class="widefat"><thead>' +
            '<tr><th>#</th><th>' + rsfirewall_check_locale.file + '</th><th>' + rsfirewall_check_locale.reason + '</th><th style="text-align:center">' + rsfirewall_check_locale.actions + '</th></tr>' +
            '</thead></table>'
        );

        /**
         * #folder and file permissions check
         */
        jQuery('#folder_permissions_check').find('p').replaceWith(
            '<button class="rsfirewall-fix-action" id="rsfirewall_ignore_folders_permission_button" onclick="RSFirewall.System.Fix.fixFolderPermissions()">' + rsfirewall_check_locale.attempt_fix + '</button> <br />' +
            '<table class="widefat"><thead>' +
            '<tr><th>#</th><th>' + rsfirewall_check_locale.folder + '</th>' +
            '<th>' + rsfirewall_check_locale.expected_permission + '</th>' +
            '<th>' + rsfirewall_check_locale.found_permission + '</th></tr>' +
            '</thead></table>'
        );
        jQuery('#file_permissions_check').find('p').replaceWith(
            '<button class="rsfirewall-fix-action" id="rsfirewall_ignore_files_permission_button" onclick="RSFirewall.System.Fix.fixFilesPermissions()">' + rsfirewall_check_locale.attempt_fix + '</button> <br />' +
            '<table class="widefat"><thead>' +
            '<tr><th>#</th><th>' + rsfirewall_check_locale.folder + '</th>' +
            '<th>' + rsfirewall_check_locale.expected_permission + '</th>' +
            '<th>' + rsfirewall_check_locale.found_permission + '</th></tr>' +
            '</thead></table>'
        );

		window.onbeforeunload = function() {
			try {
				if (jQuery('.scanning-message:visible').length > 0) {
					return rsfirewall_check_locale.scanning_still;
				}
			} catch (err) {}
		}
    });

    /**
     * We listen to the PHP Directive Step Checks and in case one of them is false, we need to display ADDITIONAL INFORMATION
     */
    $(document).on('check_allow_url_include check_open_basedir check_disable_functions check_safe_mode check_register_globals', function ($event, $helper) {
        if (!$helper.data.result) {
            $('[data-step="' + $helper.step + '"]').siblings('div.rsfirewall-hidden').slideDown('fast');
        }
    });

})(jQuery);


/**
 * A small check, we don't need to overwrite RSFirewall objects if they do exist
 */
if (typeof RSFirewall != 'object') {
    var RSFirewall = {};
}

RSFirewall.Grade = {
    total: null,
    subtracted: null,
    importance: null,
    calculatedItems: {},
    calcPerItem: function () {
        var TotalNumberOfItems = parseInt(this.importance.high.length) + parseInt(this.importance.medium.length) + parseInt(this.importance.low.length),
            equalValue = 100 / TotalNumberOfItems,
            baseValue = equalValue - ( (equalValue * 50) / 100 ),
            subtracted = equalValue - baseValue,
            vals = {},
            i = 0;

        jQuery.each(this.importance.low, function (index, value) {
            vals[value] = equalValue - subtracted;
        });

        jQuery.each(this.importance.medium, function (index, value) {
            vals[value] = equalValue;
        });

        jQuery.each(this.importance.high, function (index, value) {
            vals[value] = equalValue + subtracted;
        });


        jQuery.each(vals, function (index, value) {
            i += value;
        });

        jQuery.extend(this.calculatedItems, vals);
        this.total = i;
        this.subtracted = i;
    },
    subtractItem: function (item) {
        var $val;

        if (this.subtracted < this.total) {
            $val = (this.subtracted - this.calculatedItems[item]);
        } else {
            $val = (this.total - this.calculatedItems[item]);
        }

        this.subtracted = Math.ceil($val);
    },

    grade: function(){
        var grade = this.subtracted;
        grade = grade / this.total;
        if (grade < 1) {
            grade = grade.toFixed(2);
            grade = grade.split('.');
            grade = parseInt(grade[1]);
        } else {
            grade = 100;
        }

        // let's save the grade to the database
        this.save(grade);

        return grade;
    },

    save: function (grade) {
        jQuery.ajax({
            type: 'POST',
            url : ajaxurl,
            data: {
                'action': 'rsfirewall_check_save_grade',
                'security': rsfirewall_check_security.save_grade_nonce,
                'grade': grade
            }
        });
    }
};


/**
 * Helper function to parse jsons. (AVOID SOME ERRORS THAT MIGHT OCCUR)
 * @param data
 */
RSFirewall.parseJSON = function (data) {
    if (typeof data != 'object') {
        var match = data.match(/{.*}/);
        return jQuery.parseJSON(match[0]);
    }
    return jQuery.parseJSON(data);
};

/**
 * RSFirewall! does have a functionality to delay the execution of step checks (for slower servers)
 * @type {{}}
 */
RSFirewall.requestTimeOut = {};
RSFirewall.requestTimeOut.Seconds = 0;
RSFirewall.requestTimeOut.Milliseconds = function () {
    return parseFloat(RSFirewall.requestTimeOut.Seconds) * 1000;
};



RSFirewall.ignore = {
    remove: function ($id) {
        if (!confirm(rsfirewall_check_locale.remove_from_database)) {
            return false;
        }

        var sendData = {
            'action': 'rsfirewall_ignored_remove',
            'security':   rsfirewall_check_security.remove_ignored_none,
            'ignored_file_id': $id
        };
        jQuery.ajax({
            converters: {
                "text json": RSFirewall.parseJSON
            },
            type    : 'POST',
            url     : ajaxurl,
            dataType  : 'JSON',
            data      : sendData,
            beforeSend: function () {
                $button = jQuery('#remove_ignored' + $id);
                $button.attr('disabled', 'true');
                $button.html('<span class="dashicons dashicons-update rsfirewall-animated"></span> ' + rsfirewall_check_locale.processing);
            },
            complete   : function (json) {
                var $object = RSFirewall.parseJSON(json.responseText);

                $button = jQuery('#remove_ignored' + $id);

                if ($object.success == true) {
                    $button.html('<span class="dashicons dashicons-yes"></span> ' + rsfirewall_check_locale.success);
                    $button.parents('tr').hide('fast');

                } else {
                    $button.removeClass('btn-processing').addClass('btn-failed');
                    $button.html('<span class="dashicons dashicons-dismiss"></span> ' + rsfirewall_check_locale.failed);
                    alert($object.data.message);
                }
            }
        })
    }
};

/**
 * Helper function to animate the progress bar found on the top of the container
 *
 * @param selector
 * @param total
 * @param step
 */
RSFirewall.animateProgress = function (selector, total, step) {
    var percent = (parseFloat((step / total).toFixed(2)) * 100);
    percent = Math.round(percent);
    if (percent > 100) {
        percent = 100;
    }
    jQuery('#' + selector).css('width', percent + '%');
    jQuery('#' + selector).html(percent + '%');
};

RSFirewall.System = {};

RSFirewall.System.Check = {
    /**
     * The steps array is popuplated here: /views/check.php
     *
     * Firewall will go through all the steps defined in the array ( re-checking if they need more AJAX requests ) until it reaches the end
     */
    steps: [],
    /**
     * While doing the core file integrity check, we need to set this variable true/false to hide/show the (VIEW IGNORED FILES) button
     */
    ignored: false,
    /**
     * Define the current step here
     */
    currentStep: null,
    /**
     * In order to calculate the grade correctly - when repeating the same step for additional AJAX requests the total would subtract every time the result was false.
     */
    currentStepPassed: true,
    /**
     * The function that actually runs the check
     *
     * @param index
     * @param more_data
     */
    stepCheck: function (index, more_data) {
        /**
         * usual checks to avoid errors
         */
        if (typeof(this.steps[index]) == 'undefined') {
            if (typeof this.stopCheck == 'function') {
                this.stopCheck();
            }
            return;
        }

        /**
         * grab the current step and the grand total
         */
        var currentStep = this.steps[index],
            total = this.steps.length;

        /**
         * set the objects' scope step
         */
        this.currentStep = currentStep;

        /**
         * WordPress needs to handle AJAX by its own. The helper function instantiantes
         * the RSFirewall_System_Check class using the public static function get_instance.
         *
         * Afterwards, it calls the function defined in the data.function object and uses the args passed as a key=>val pair.
         * e.g. 'args': {old_user: $admin_login, new_user: $input.val()},
         *
         * @type {{class: string, action: string, function: *, args: object, plugin: string}}
         */
        var $data = {
            'action': 'rsfirewall_check_' + currentStep,
            'security': rsfirewall_check_security.step_check_nonce,
            'args': {}
        };

        /**
         * In case the step requires parameters to be sent through AJAX, we send them through the $data.args object
         */
        if (more_data) {
            $data.args = more_data;
        }

        /**
         * Catch any internal errors if they are thrown
         */
        var internal_error = '';

        /**
         * Initiate the AJAX request
         */
        jQuery.ajax({
            converters: {
                "text json": RSFirewall.parseJSON
            },
            dataType: 'json',
            type: 'POST',
            url: ajaxurl,
            data: $data,
            beforeSend: function() {
                var container = jQuery('[data-step="' + currentStep + '"]');
                var short_message = container.find('.short-message-holder');
                var skip_steps = ['hashes_check', 'signatures_check', 'folder_permissions_check', 'file_permissions_check'];
                if (skip_steps.indexOf(currentStep) == -1) {
                    short_message.html('<span class="dashicons dashicons-update rsfirewall-animated"></span>');
                }

                container.parents('.bordered-sub-box').show();
                container.show();
            },
            complete: function (json, textStatus) {
                var error_detected = (textStatus == 'error' || textStatus == 'timeout' || textStatus == 'parsererror');

                this.currentStepPassed = true;
                /**
                 * $object is sent from PHP as $object.success (true or false) depending IF the AJAX request was succesfull
                 * and $object.data.result, $object.data.message, $object.data.details
                 */

                if (!error_detected) {
                    var $object = RSFirewall.parseJSON(json.responseText);
                } else  {
                    var $object = {
                        success : false,
                        data: {message : internal_error}
                    }
                }

                /**
                 * define some variables we will need
                 * var container - the row that holds the information displayed from the ajax result
                 *     short_message - the right column of the "table"
                 *     long_message - the part that is hidden by default. it holds the object.data.details (if found)
                 *     button - we only display it if long_message is used
                 *     helper - a helper object, that we send when we trigger the current step event
                 */
                var container = jQuery('[data-step="' + currentStep + '"]'),
                    short_message = container.find('.short-message-holder'),
                    long_message = jQuery('#' + currentStep).find('p'),
                    button = container.find('.system-check-dropdown-btn'),
                    helper = {
                        step: currentStep,
                        data: $object.data
                    };

                /**
                 * If the request was succesfull, we run some code
                 */
                if ($object.success) {

                    /**
                     * determine if the check was OK/NOK and add the class accordingly.
                     * If the check didn't pass, we need to subtract the value of it from the
                     * overal score.
                     */
                    if ($object.data.result) {
                        short_message.addClass('rsfirewall-ok');
                    } else {
                        this.currentStepPassed = false;
                        short_message.addClass('rsfirewall-not-ok');
                    }

                    /**
                     * Change the short message ( column right of the table )
                     */
                    short_message.html($object.data.message);

                    /**
                     * In case we sent details through the object, we display it and show the dropdown button
                     */
                    if ($object.data.details) {
                        long_message.html($object.data.details);
                        button.show();
                    }

                    /**
                     * Trigger Events, and send the helper object
                     */
                    jQuery(document).trigger(currentStep, helper);


                    if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container)) {
                        return;
                    }

                } else {
                    /**
                     * In case some exceptions errors are thrown
                     */
                    this.currentStepPassed = false;
                    short_message.addClass('rsfirewall-not-ok');

                    /**
                     * Change the short message ( column right of the table )
                     */
                    short_message.html($object.data.message);
                }

                /**
                 * In case we need to wait between requests ( option from the Configuration )
                 */
                if (RSFirewall.requestTimeOut.Seconds != 0) {
                    setTimeout(function () {
                        RSFirewall.System.Check.stepCheck(index + 1)
                    }, RSFirewall.requestTimeOut.Milliseconds());
                }
                else {
                    RSFirewall.System.Check.stepCheck(index + 1);
                }

                if (!this.currentStepPassed) {
                    RSFirewall.Grade.subtractItem(currentStep);
                }
                /**
                 * Every time we pass a step, we update the progress bar (from the top of the container)
                 */
                RSFirewall.animateProgress('system-check-progress', total, index + 1);

                /**
                 * At the end of the system check, we calculate the grade
                 * based on the importance of the steps e.g. (admin password will have a higher importance than sef links)
                 */
                if (index + 1 == total) {
                    var $value = RSFirewall.Grade.grade();

                    /**
                     * Run the javascript library for knobs
                     */
                    jQuery('.loading-holder').hide();
                    jQuery('.scanning-message').hide();
                    jQuery('.scan-results-holder').show();
                    jQuery('.rsfirewall-knob-score').val($value).trigger('change');
                }
            },
            error: function( jqXHR, textStatus, errorThrown) {
                internal_error = errorThrown;
            }
        });

    },
    stopCheck: function () {
        // overwritten
    },
    /**
     * The startCheck function is simply a PROXY for the stepCheck function, starting with the first array index of steps
     */
    startCheck: function () {
        RSFirewall.System.Check.stepCheck(0);
    },
    /**
     * Used for functions that require more than one AJAX request to pass a certain step.
     *
     * @param step
     * @param json
     * @param container
     * @returns {boolean}
     */
    parseCheckDetails: function (step, json, container) {
        var stepIndex = this.steps.indexOf(this.currentStep),
            short_message = container.find('.short-message-holder'),
            long_message = jQuery('#' + this.currentStep).find('table'),
            button = container.find('.system-check-dropdown-btn'),
            contents;
        switch (step) {
        /**
         * In case any hashes are different from what we have in the CSV files, we start adding rows to the table.
         * we create a checkbox input element, and give it some data attribute to be easier to manipulate it afterwards
         */
            case 'hashes_check':
                if (json.data.files.length > 0) {
                    button.show();
                    RSFirewall.System.Templates.renderTemplate('hashesCheck', json, long_message);
                }
                /**
                 * In case we have any ignored files, we change this.ignored to true in order to display the button
                 */
                if (json.data.ignored_files.length > 0) {
                    this.ignored = true;
                }

                /**
                 * in case the AJAX request still returns json.data.fstart, we need to run the step again
                 * we haven't reached the end of the file so do another ajax call
                 */
                if (typeof json.data.fstart !== 'undefined') {
                    if (RSFirewall.requestTimeOut.Seconds != 0) {
                        setTimeout(function () {
                            RSFirewall.System.Check.stepCheck(stepIndex, {'fstart': json.data.fstart})
                        }, RSFirewall.requestTimeOut.Milliseconds());
                    }
                    else {
                        RSFirewall.System.Check.stepCheck(stepIndex, {'fstart': json.data.fstart});
                    }
                    /**
                     *  returning true means that this step hasn't finished and we don't need to go to the next step
                     *
                     *  REFERENCE BELOW:
                     *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                     */
                    return true;
                } else {
                    /**
                     * We need to change the short_message_holder based on the result of the checks.
                     *
                     * Everytime when the step fails, it creates a new TR in the table. We can count them
                     * and determine if it's ok or not (LENGTH > 1 ~ because we have the first row with headers).
                     * @type {string}
                     */
                    contents = '';
                    var extra = (typeof json.data.message != 'undefined' ? '<br/>' + json.data.message : '');
                    if (jQuery('#' + this.currentStep).find('tr').length > 1) {
                        contents += rsfirewall_check_locale.core_files_not_ok + extra;
                    } else if (json.data.version == null) {
                        contents += rsfirewall_check_locale.no_csv_file + extra;
                    } else {
                        contents += rsfirewall_check_locale.core_files_ok;
                    }

                    /**
                     * In case we have ignored some files, we add this button
                     */
                    if (this.ignored) {
                        contents += ' <button class="rsfirewall-fix-action" data-action="ignored_view_content" data-toggle="rsmodal" data-target="#rsmodal" data-title="'+rsfirewall_check_locale.modal_title_ignored_files+'" data-viewcontent="1" data-usefooter="1" data-showclose="1" data-size="large">' + rsfirewall_check_locale.view_ignored_files + '</button>';
                    }

                    short_message.html(contents);
                    /**
                     *  returning false means that this step has finished and we need to go to the next step
                     *
                     *  REFERENCE BELOW:
                     *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                     */
                    return false;
                }
            break;
        /**
         * The signature check will test all the files found in your wordpress installation for known malware.
         * It works similar to the hashes_check.
         */
            case 'signatures_check':
                if (json.data.next_file) {
                    var next_file = json.data.next_file,
                        next_file_stripped = json.data.next_file_stripped;
                    /**
                     * Add table rows
                     */
                    if (json.data.files.length > 0) {
                        button.show();
                        RSFirewall.System.Templates.renderTemplate('signaturesCheck', json, long_message);
                    }

                    /**
                     * Update the progress of the check
                     */
                    short_message.html(rsfirewall_check_locale.building_file_structure + ' <code>' + next_file_stripped + '</code>');

                    /**
                     * Rerun step as long as we have a next.file
                     */
                    if (RSFirewall.requestTimeOut.Seconds != 0) {
                        setTimeout(function () {
                            RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file})
                        }, RSFirewall.requestTimeOut.Milliseconds());
                    }
                    else {
                        RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_file});
                    }

                    /**
                     *  returning true means that this step hasn't finished and we don't need to go to the next step
                     *
                     *  REFERENCE BELOW:
                     *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                     */
                    return true;
                } else {
                    if (json.data.stop) {
                        if (typeof json.data.files != 'undefined' && json.data.files.length > 0) {
                            button.show();
                            RSFirewall.System.Templates.renderTemplate('signaturesCheck', json, long_message);
                        }

                        /**
                         * We need to change the short_message_holder based on the result of the checks.
                         *
                         * Everytime when the step fails, it creates a new TR in the table. We can count them
                         * and determine if it's ok or not (LENGTH > 1 ~ because we have the first row with headers).
                         * @type {string}
                         */

                        if (jQuery('#' + this.currentStep).find('tr').length > 1) {
                            short_message.html(rsfirewall_check_locale.malware_found)
                        } else {
                            short_message.html(rsfirewall_check_locale.malware_not_found);
                        }
                        /**
                         *  returning false means that this step has finished and we need to go to the next step
                         *
                         *  REFERENCE BELOW:
                         *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                         */
                        return false;
                    }
                }
                break;
            case 'folder_permissions_check':
                if (json.data.next_folder) {
                    var next_folder = json.data.next_folder,
                        next_folder_stripped = json.data.next_folder_stripped;
                    /**
                     * Add table rows
                     */
                    if (json.data.folders.length > 0) {
                        button.show();
                        RSFirewall.System.Templates.renderTemplate('folderPermissions', json, long_message);
                    }

                    /**
                     * Update the progress of the check
                     */
                    short_message.html(rsfirewall_check_locale.building_file_structure + ' <code>' + next_folder_stripped + '</code>');

                    /**
                     * Rerun step as long as we have a next.file
                     */
                    if (RSFirewall.requestTimeOut.Seconds != 0) {
                        setTimeout(function () {
                            RSFirewall.System.Check.stepCheck(stepIndex, {'folder': next_folder})
                        }, RSFirewall.requestTimeOut.Milliseconds());
                    }
                    else {
                        RSFirewall.System.Check.stepCheck(stepIndex, {'folder': next_folder});
                    }

                    /**
                     *  returning true means that this step hasn't finished and we don't need to go to the next step
                     *
                     *  REFERENCE BELOW:
                     *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                     */
                    return true;
                } else {
                    if (json.data.stop) {
                        if (typeof json.data.files != 'undefined' && json.data.folders.length > 0) {
                            button.show();
                            RSFirewall.System.Templates.renderTemplate('folderPermissions', json, long_message);
                        }

                        if (jQuery('#' + this.currentStep).find('tr').length > 1) {
                            container.find('.short-message-holder').html(rsfirewall_check_locale.permissions_issue);
                        } else {
                            container.find('.short-message-holder').html(rsfirewall_check_locale.permissions_ok);
                        }
                        /**
                         *  returning false means that this step has finished and we need to go to the next step
                         *
                         *  REFERENCE BELOW:
                         *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                         */
                        return false;
                    }
                }
                break;
            case 'file_permissions_check':
                if (json.data.next_file) {
                    var next_perm_file = json.data.next_file,
                        next_perm_filer_stripped = json.data.next_file_stripped;
                    /**
                     * Add table rows
                     */
                    if (json.data.files.length > 0) {
                        button.show();
                        RSFirewall.System.Templates.renderTemplate('filePermissions', json, long_message);
                    }

                    /**
                     * Update the progress of the check
                     */
                    short_message.html(rsfirewall_check_locale.building_file_structure + ' <code>' + next_perm_filer_stripped + '</code>');

                    /**
                     * Rerun step as long as we have a next.file
                     */
                    if (RSFirewall.requestTimeOut.Seconds != 0) {
                        setTimeout(function () {
                            RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_perm_file})
                        }, RSFirewall.requestTimeOut.Milliseconds());
                    }
                    else {
                        RSFirewall.System.Check.stepCheck(stepIndex, {'file': next_perm_file});
                    }

                    /**
                     *  returning true means that this step hasn't finished and we don't need to go to the next step
                     *
                     *  REFERENCE BELOW:
                     *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                     */
                    return true;
                } else {
                    if (json.data.stop) {
                        if (typeof json.data.files != 'undefined' && json.data.files.length > 0) {
                            button.show();
                            RSFirewall.System.Templates.renderTemplate('filePermissions', json, long_message);
                        }

                        /**
                         * We need to change the short_message_holder based on the result of the checks.
                         *
                         * Everytime when the step fails, it creates a new TR in the table. We can count them
                         * and determine if it's ok or not (LENGTH > 1 ~ because we have the first row with headers).
                         * @type {string}
                         */

                        if (jQuery('#' + this.currentStep).find('tr').length > 1) {
                            container.find('.short-message-holder').html(rsfirewall_check_locale.permissions_issue);
                        } else {
                            container.find('.short-message-holder').html(rsfirewall_check_locale.permissions_ok);
                        }
                        /**
                         *  returning false means that this step has finished and we need to go to the next step
                         *
                         *  REFERENCE BELOW:
                         *  if (RSFirewall.System.Check.parseCheckDetails(currentStep, $object, container))
                         */
                        return false;
                    }
                }
                break;
        }
    }
};

RSFirewall.System.Fix = {
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
                callback(json, args);
            }
        });
    },
    /**
     * changes the admin username through ajax
     * @param element
     */
    changeAdminUsername: function (element) {
        var $element = jQuery(element),
            $parent = $element.parent(),
            $input = $parent.find('.rsfirewall-username-change'),
            $admin_login = $input.data('value'),
            $data = {
                'action': 'rsfirewall_fix_admin_username_fix',
                'security': rsfirewall_check_security.admin_username_fix_nonce,
                'args': {old_user: $admin_login, new_user: $input.val()}
            };

        this.proxy($data, this.changeAdminUsernameCallback, $element);
    },
    /**
     * This callback is called in the proxy function upon completion
     * @param json
     */
    changeAdminUsernameCallback: function (json, $element) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {

            // find proper row
            var $step_row = $element.parents('.system-check-step');
            $step_row = jQuery($step_row[0]);

            // check if there are multiple users shown
            var $are_multiple = $step_row.find('.rsf_check_user').length;

            if ($object.data.result) {
                if ($are_multiple <= 1) {
                    $step_row.find('.row.rsfirewall-hidden').hide('fast');
                    $step_row.find('.short-message-holder').removeClass('rsfirewall-not-ok').addClass('rsfirewall-ok').html($object.data.message);
                    $step_row.find('.system-check-dropdown-btn').hide();
                } else {
                    vex.dialog.alert($object.data.message);
                    $element.parent().hide('fast').remove();
                }
            } else {
                vex.dialog.alert($object.data.details);
            }

        } else {
            vex.dialog.alert($object.data.message);
        }
    },

    /**
     * Deletes the admin user through ajax
     * @param element
     */
    deleteAdminUser:  function (element) {
        var $element = jQuery(element),
            $user_id = $element.data('iduser'),
            $data = {
                'action': 'rsfirewall_fix_delete_admin_user',
                'security': rsfirewall_check_security.delete_admin_user_nonce,
                'args': {user_id: $user_id}
            };

        this.proxy($data, this.deleteAdminUserCallback, $element);
    },

    /**
     * This callback is called in the proxy function upon completion
     * @param json
     */
    deleteAdminUserCallback: function (json, $element) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {

            // find proper row
            var $step_row = $element.parents('.system-check-step');
            $step_row = jQuery($step_row[0]);

            // check if there are multiple users shown
            var $are_multiple = $step_row.find('.rsf_check_user').length;

            if ($are_multiple <= 1) {
                $step_row.find('.row.rsfirewall-hidden').hide('fast');
                $step_row.find('.short-message-holder').removeClass('rsfirewall-not-ok').addClass('rsfirewall-ok').html($object.data.message);
                $step_row.find('.system-check-dropdown-btn').hide();
            } else {
                vex.dialog.alert($object.data.message);
                $element.parent().hide('fast').remove();
            }
        } else {
            vex.dialog.alert($object.data.message);
        }
    },

    /**
     * Fixes PHP Configuration function (along with callback)
     * @param element
     */
    fixPhpConfiguration: function (element) {
        var $element = jQuery(element),
            $data = {
                'action': 'rsfirewall_fix_php_configuration_fix',
                'security': rsfirewall_check_security.php_configuration_fix_nonce,
                'args': {}
            };
        this.proxy($data, this.fixPhpConfigurationCallback);

    },
    /**
     * This callback is called in the proxy function upon completion
     * @param json
     */
    fixPhpConfigurationCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                vex.dialog.alert(rsfirewall_check_locale.php_ini_created);
            }
        }
    },

    /**
     * Ignores files checked in the table
     */
    ignoreFiles: function () {
        var $files = [];
        jQuery('input[name="ignore_files[]"]:checked').each(function () {
            $files.push(jQuery(this).val());
        });
        var $data = {
            'action': 'rsfirewall_fix_ignore_files',
            'security': rsfirewall_check_security.ignore_files_nonce,
            'args': {type: 'files', files: $files}
        };

        this.proxy($data, this.ignoreFilesCallback);
    },
    /**
     * This callback is called in the proxy function upon completion
     * @param json
     */
    ignoreFilesCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                vex.dialog.alert($object.data.message);
            }
        } else {
            vex.dialog.alert($object.data.message);
        }
    },

    /**
     * Ignore / Add hashes from the system check
     */
    addFoundHashes: function ($args) {
        var $data = {
            'action': 'rsfirewall_check_add_hashes',
            'security': rsfirewall_check_security.add_hashes_nonce,
            'args': {hash: $args}
        };

        this.proxy($data, this.addHashesCallback);
    },
    /**
     * This callback is called in the proxy function upon completion
     */
    addHashesCallback: function () {

    },
    /**
     * This callback is called in the proxy function upon completion
     */
    ignoreHashes: function () {
        var $hashes = [];
        jQuery('input[name="ignore_hashes[]"]:checked').each(function () {
            $hashes.push({
                version: jQuery(this).data('version'),
                hash: jQuery(this).data('hash'),
                file: jQuery(this).val(),
                type: jQuery(this).data('type')
            });
        });
        var $data = {
            'action': 'rsfirewall_fix_ignore_hashes',
            'security': rsfirewall_check_security.ignore_hashes_nonce,
            'args': {data: $hashes}
        };

        this.proxy($data, this.ignoreHashesCallback);
    },
    /**
     * This callback is called in the proxy function upon completion
     * @param json
     */
    ignoreHashesCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                vex.dialog.alert($object.data.message);
            }
        }
    },

    /**
     * Function to set the folder permissions
     */
    fixFolderPermissions: function () {
        var $folders = [];
        jQuery('input[name="ignore_folders_permission[]"]:checked').each(function () {
            $folders.push(jQuery(this).val());
        });

        var $data = {
            'action': 'rsfirewall_fix_fix_folder_permissions',
            'security': rsfirewall_check_security.fix_folder_permissions_nonce,
            'args': {folders: $folders}
        };

        this.proxy($data, this.fixFolderPermissionsCallback);
    },
    fixFolderPermissionsCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                vex.dialog.alert($object.data.message);
            }
        } else {
            vex.dialog.alert($object.data.message);
        }
    },

    /**
     * Function to set the file permissions
     */
    fixFilesPermissions: function () {
        var $files = [];
        jQuery('input[name="ignore_files_permission[]"]:checked').each(function () {
            $files.push(jQuery(this).val());
        });
        var $data = {
            'action': 'rsfirewall_fix_fix_file_permissions',
            'security': rsfirewall_check_security.fix_file_permissions_nonce,
            'args': {files: $files}
        };

        this.proxy($data, this.fixFilesPermissionsCallback);
    },
    fixFilesPermissionsCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                vex.dialog.alert($object.data.message);
            }
        } else {
            vex.dialog.alert($object.data.message);
        }
    },

    /**
     * Function to delete the post revisions
     *
     */
    deleteRevisions: function () {
        var $data = {
            'action': 'rsfirewall_fix_fix_delete_revisions',
            'security': rsfirewall_check_security.fix_delete_revisions_nonce,
        };

        this.proxy($data, this.deleteRevisionsCallback);
    },

    deleteRevisionsCallback: function (json) {
        var $object = RSFirewall.parseJSON(json.responseText);
        if ($object.success) {
            if ($object.data.result) {
                jQuery('#revisions-check').hide('fast');
                jQuery('[data-step="revisions-check"]').find('.short-message-holder').removeClass('rsfirewall-not-ok').addClass('rsfirewall-ok').html($object.data.message);
                jQuery('[data-target="#revisions-check"]').hide();
            } else {
                jQuery('[data-step="revisions-check"]').find('.short-message-holder').html($object.data.message);
            }
        } else {
            vex.dialog.alert($object.data.message);
        }
    }
};

RSFirewall.System.Templates = {
    renderTemplate: function (type, json, long_message) {
        return this[type](json, long_message);
    },
    hashesCheck: function (json, long_message) {
        for (var i = 0; i < json.data.files.length; i++) {
            /**
             * Ajax request to add the hash to the database
             */
            RSFirewall.System.Fix.addFoundHashes(json.data.files[i]);
            var j = long_message.find('tr').length;
            var hash_index = 'hash' + j;
            var contents = '<button class="rsfirewall-view-contents" data-toggle="rsmodal" data-target="#rsmodal" data-title="'+rsfirewall_check_locale.modal_title+'" data-viewcontent="1" data-usefooter="1" data-showclose="1" data-size="large" data-action="diff_view_differences" data-hid="'+hash_index+'" data-file="' + encodeURI(json.data.files[i].path) + '">' + rsfirewall_check_locale.view_contents + '</button>';

            var $html = '<tr id="hash' + j + '"><td><input type="checkbox" checked="checked" name="ignore_hashes[]" data-type="' + json.data.files[i].type + '" data-version="' + json.data.version + '" data-hash="' + json.data.files[i].hash + '" value="' + json.data.files[i].path + '" /></td>';

            var reason = '';
            var is_missing = false;
            if (json.data.files[i].type == 'wrong') {
                if (typeof json.data.files[i].time == 'string')
                {
                    reason = rsfirewall_check_locale.file_modified_ago;
                    reason = reason.replace('%s', json.data.files[i].time);
                }
                else
                {
                    reason = rsfirewall_check_locale.file_modified;
                }

            } else if (json.data.files[i].type == 'missing') {
                reason = rsfirewall_check_locale.file_missing;
                is_missing = true;
            }

            var download_original = '<button class="rsfirewall-fix-action" onclick="RSFirewall.diffs.download(\'' + json.data.files[i].path + '\', \'' + hash_index + '\', false, '+(is_missing ? true : false)+')">' + rsfirewall_check_locale.download_original + '</button>';

            $html += '<td>' + json.data.files[i].path + '</td>';
            $html += '<td>' + reason + '</td>';
            $html += '<td class="text-center rs_fix_response">' + (is_missing ? '' : contents) + download_original +'</td>';
            $html += '</tr>';
            long_message.append($html);
        }
    },
    signaturesCheck: function (json, long_message) {
        for (var i = 0; i < json.data.files.length; i++) {
            var j = long_message.find('tr').length;
            var contents = '<button class="rsfirewall-view-contents" data-toggle="rsmodal" data-target="#rsmodal" data-title="'+rsfirewall_check_locale.modal_title+'" data-viewcontent="1" data-usefooter="1" data-showclose="1" data-size="large" data-action="file_view_content" data-file="' + encodeURI(json.data.files[i].path) + '">' + rsfirewall_check_locale.view_contents + '</button>';
            var $html = '<tr><td><input type="checkbox" checked="checked" name="ignore_files[]" value="' + json.data.files[i].path + '" id="checkbox_file' + j + '" /></td>';
            $html += '<td>' + json.data.files[i].path + '</td>';
            $html += '<td>' + json.data.files[i].reason + '</td>';
            $html += '<td>' + json.data.files[i].match.substr(0, 20) + '</td>';
            $html += '<td>' + contents + '</td></tr>';
            long_message.append($html)
        }
    },
    folderPermissions: function (json, long_message) {
        for (var i = 0; i < json.data.folders.length; i++) {
            var j = long_message.find('tr').length;
            var $html = '<tr><td><input type="checkbox" checked="checked" name="ignore_folders_permission[]" value="' + json.data.folders[i].path + '" id="checkbox_folder_ignore' + j + '" /></td>';
            $html += '<td>' + json.data.folders[i].path + '</td>';
            $html += '<td>' + json.data.folders[i].expected + '</td>';
            $html += '<td>' + json.data.folders[i].perms + '</td></tr>';
            long_message.append($html)
        }
    },
    filePermissions: function (json, long_message) {
        for (var i = 0; i < json.data.files.length; i++) {
            var j = long_message.find('tr').length;
            var $html = '<tr><td><input type="checkbox" checked="checked" name="ignore_files_permission[]" value="' + json.data.files[i].path + '" id="checkbox_file_permission' + j + '" /></td>';
            $html += '<td>' + json.data.files[i].path + '</td>';
            $html += '<td>' + json.data.files[i].expected + '</td>';
            $html += '<td>' + json.data.files[i].perms + '</td></tr>';
            long_message.append($html)
        }
    }
};