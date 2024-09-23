/*
 * @package    RSFirewall!
 * @copyright  (c) 2016 RSJoomla!
 * @link       https://www.rsjoomla.com
 * @license    GNU General Public License http://www.gnu.org/licenses/gpl-3.0.en.html
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        RSFirewall.Utils.Selectize(jQuery('.rsfirewall select'));
        RSFirewall.Utils.Switch(jQuery('.rsfirewall-switch-field'));
        if (typeof rsfirewall_statistics != 'undefined') {
            RSFirewall.Utils.Flot(jQuery('.rsfirewall-graph'));

            if (typeof rsfirewall_statistics.by_ip != 'undefined') {
                RSFirewall.Utils.Vmap(jQuery('.rsfirewall-vmap'));
            }
        }
        RSFirewall.Utils.Knob(jQuery('.rsfirewall-knob-score'));
        vex.defaultOptions.className = 'vex-theme-plain';

        RSFirewall.QuickFix.init(jQuery('.rsfirewall-fix-action'));

        RSFirewall.Utils.HelpTips(jQuery('.rsfirewall-help-tip'));
        RSFirewall.Utils.HelpTips(jQuery('.rsfirewall-country-tip'));

        RSFirewall.Widgets.init();
    });

})(jQuery);


if (typeof RSFirewall != 'object') {
    var RSFirewall = {};
}
RSFirewall.QuickFix = {
    init: function (elements) {
        jQuery.each(elements, function () {
            var action = jQuery(this).data('action'),
                info = jQuery(this).data('info');
            switch (action) {
                case 'remove_from_list':
                    RSFirewall.QuickFix.remove_ip(this, action, info);
                break;
                case 'ignore_hash':
                    RSFirewall.QuickFix.ignore_hash(this, action);
                break;
            }
        });
    },
    remove_ip: function (element, action, additional) {
        jQuery(element).click(function () {

            var $data = {
                'action': 'rsfirewall_fix_remove_ip',
                'security': rsfirewall_fix.remove_ip_nonce,
                'args': {ip: additional}
            };

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
                    if (response.data.result) {
                        jQuery(element).parents('.quick-action').slideUp('fast');
                    }
                }
            });
        });
    },

    ignore_hash :  function(element, action) {
        jQuery(element).click(function () {
            var $hashes = [];
            $hashes.push({
                version: jQuery(this).data('version'),
                file: jQuery(this).data('file'),
                type: jQuery(this).data('type'),
            });


            var $data = {
                'action': 'rsfirewall_fix_ignore_hashes',
                'security': rsfirewall_check_security.ignore_hashes_nonce,
                'args': {data: $hashes}
            };

            jQuery.ajax({
                converters: {
                    "text json": RSFirewall.parseJSON
                },
                dataType: 'json',
                type: 'POST',
                url: ajaxurl,
                data: $data,
                complete: function (json) {
                    var $object = RSFirewall.parseJSON(json.responseText);
                    if ($object.success) {
                        if ($object.data.result) {
                            jQuery(element).parents('.quick-action').slideUp('fast').remove();
                            vex.dialog.alert($object.data.message);
                            // remove the entire box if there are no more quick actions left
                            if (jQuery('.rs-box > .quick-action').length == 0) {
                                jQuery('.quick-actions-box').parent().remove();
                            }
                        }
                    } else {
                        vex.dialog.alert($object.data.message);
                    }
                }
            });
        });
    }
};

/**
 * Helper function to parse jsons. (AVOID SOME ERRORS THAT MIGHT OCCUR)
 * @param data
 */
if (typeof RSFirewall.parseJSON != 'function') {
    RSFirewall.parseJSON = function (data) {
        if (typeof data != 'object') {
            var match = data.match(/{.*}/);
            return jQuery.parseJSON(match[0]);
        }
        return jQuery.parseJSON(data);
    };
}

RSFirewall.Widgets = {
    available_widgets: [],
    init: function() {
        jQuery("div[id^='rsfirewall_widget_']").each(function(){
            var widget = jQuery(this).attr('id');
            widget = widget.replace('rsfirewall_widget_', '');
            RSFirewall.Widgets.available_widgets.push(widget);
        });

        if ( RSFirewall.Widgets.available_widgets.length) {
            // init the found widgets
            jQuery.each(RSFirewall.Widgets.available_widgets, function(){
                if (typeof RSFirewall.Widgets[this] == 'function') {
                    RSFirewall.Widgets[this]();
                }
            });
        }
    },

    dashboard: function(){
        var rsfirewall_version  = jQuery('#rsfirewall_widget_dashboard').find('#widget-rsfirewall-firewall-version');
        var wordpress_version   = jQuery('#rsfirewall_widget_dashboard').find('#widget-rsfirewall-wp-version');

        // Check the if the current RSFirewall! version is the last one
        if (rsfirewall_version.length) {
            jQuery.ajax({
                converters: {
                    "text json": RSFirewall.parseJSON
                },
                dataType: 'json',
                type: 'POST',
                url : ajaxurl,
                data: {
                    'action': 'rsfirewall_check_rsfirewall_version_check',
                    'security': rsfirewall_widget_security.step_check_nonce,
                },
                complete: function(json) {
                    // remove the loader
                    rsfirewall_version.empty();

                    // decode the retuned json
                    var $object = RSFirewall.parseJSON(json.responseText);

                    // set the message color
                    if ($object.success) {
                        if ($object.data.result) {
                            rsfirewall_version.addClass('ok');
                        } else  {
                            rsfirewall_version.addClass('rsnotice');
                        }
                    } else {
                        rsfirewall_version.addClass('rsnotice');
                    }

                    // append the message regardless of the success state
                    rsfirewall_version.append($object.data.message)

                }
            });
        }

        // Check the if the current WordPress version is the last one
        if (wordpress_version.length) {
            jQuery.ajax({
                converters: {
                    "text json": RSFirewall.parseJSON
                },
                dataType: 'json',
                type: 'POST',
                url : ajaxurl,
                data: {
                    'action': 'rsfirewall_check_wp_version_check',
                    'security': rsfirewall_widget_security.step_check_nonce,
                },
                complete: function(json) {
                    // remove the loader
                    wordpress_version.empty();

                    // decode the retuned json
                    var $object = RSFirewall.parseJSON(json.responseText);

                    // set the message color
                    if ($object.success) {
                        if ($object.data.result) {
                            wordpress_version.addClass('ok');
                        } else  {
                            wordpress_version.addClass('rsnotice');
                        }
                    } else {
                        wordpress_version.addClass('rsnotice');
                    }

                    // append the message regardless of the success state
                    wordpress_version.append($object.data.message)
                }
            });
        }
    }
};

/**
 * Utility functions - library initiator
 *
 * @type {{Selectize: RSFirewall.Utils.Selectize, Switch: RSFirewall.Utils.Switch, Vmap: RSFirewall.Utils.Vmap, Flot: RSFirewall.Utils.Flot, Dropdowns: RSFirewall.Utils.Dropdowns, Knob: RSFirewall.Utils.Knob, checkUploads: RSFirewall.Utils.checkUploads}}
 */
RSFirewall.Utils = {
    HelpTips : function(elements) {
        // Create the container
        if (elements.length && !jQuery('#helper_tip_holder').length) {
            jQuery('body').append('<div id="rshelper_tip_holder" style="max-width: 200px; display: none;">'+
                '<div id="rshelper_tip_arrow" style="margin-top: -12px;"><div id="rshelper_tip_arrow_inner"></div></div>'+
                '<div id="rshelper_tip_content"></div>'+
                '</div>');
            var tip_container = jQuery('#rshelper_tip_holder');
        }

        // Activate the tips
        jQuery.each(elements, function(){
            jQuery(this).on('mouseover', function(){
                var tip_content = jQuery(this).data('tip');
                tip_container.find('#rshelper_tip_content').empty().append(tip_content);

                // Determine the proper position for the helper tip
                var coords = jQuery(this).offset();
                tip_container.css({'margin-top': (coords.top + jQuery(this).height() + 5)+'px', 'margin-left': (coords.left - (tip_container.width())/2 + jQuery(this).innerWidth()/2)+'px'});

                if (jQuery(this).innerWidth() > tip_container.width()) {
                    tip_container.find('#rshelper_tip_arrow').css('margin-left', ((jQuery(this).innerWidth() / 2) - tip_container.width()/2 + 2)+'px');
                } else {
                    tip_container.find('#rshelper_tip_arrow').css('margin-left', ((tip_container.width() / 2) - jQuery(this).innerWidth() / 2 + 2) + 'px');
                }

                // Show the helper tip
                tip_container.fadeIn('fast');
            });

            jQuery(this).on('mouseout', function(){
                tip_container.fadeOut('fast');
            });
        });
    },

    /**
     * Stylish select lists
     *
     * @param elements
     * @constructor
     */
    Selectize: function (elements) {
        jQuery.each(elements, function () {
            jQuery(this).selectize();
        });
    },
    /**
     * Create switch elements (toggles)
     *
     * @param elements
     * @constructor
     */
    Switch: function (elements) {
        jQuery.each(elements, function () {
            /**
             * Initiate the Switchery library
             */
            var switchery = new Switchery(this);
            /**
             * Get the sibling value (hidden input)
             */
            var sibling = jQuery('#' + jQuery(this).attr('data-id'));
            /**
             * Listen to the change event and change the value of the hidden input
             */
            jQuery(this).change(function () {
                if (sibling.val() == '0') {
                    sibling.val('1');
                } else {
                    sibling.val('0');
                }

                sibling.trigger('change');
            })
        });
    },
    /**
     * Create the vector map
     *
     * @param elements
     * @constructor
     */
    Vmap: function (elements) {
        /**
         * Parse the JSON passed through the WordPress localization script
         */
        var $ips = JSON.parse(rsfirewall_statistics.by_ip);

        jQuery.each(elements, function () {
            /**
             * Set the width of the map according to it's parent
             */
            jQuery(this).css('width', jQuery(this).parent().width());
            /**
             * Initiate script
             */
            jQuery(this).vectorMap(
                {
                    map: 'world_en',
                    backgroundColor: null,
                    color: '#ffffff',
                    hoverOpacity: 0.7,
                    selectedColor: '#666666',
                    enableZoom: true,
                    showTooltip: true,
                    values: $ips,
                    scaleColors: ['#F8C3C4', '#e8363a'],
                    normalizeFunction: 'polynomial'
                }
            );
            /**
             * Create tooltips
             */
            jQuery(this).bind('labelShow.jqvmap',
                function (event, label, code) {
                    var text = label.text();
                    if (typeof $ips[code] != 'undefined') {
                        label.text(text + ' : ' + $ips[code]);
                    }
                }
            );
        });
    },
    /**
     * Create the charts
     *
     * @param elements
     * @constructor
     */
    Flot: function (elements) {
        /**
         * Parse the JSON passed through the WordPress localization script
         */
        var $date = JSON.parse(rsfirewall_statistics.by_date);

        jQuery.each(elements, function () {
            jQuery(this).css('width', jQuery(this).parent().width());
            var attacks = [];
            /**
             * Build the array
             */
            jQuery.each($date, function (element, count) {
                var attack = [];
                attack.push(element);
                attack.push(count);

                attacks.push(attack);
            });

            /**
             * Create the tooltips
             */
            jQuery("<div id='tooltip'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "4px",
                "background-color": "#fee",
                opacity: 0.80
            }).appendTo("body");

            /**
             * Initiate the charts
             */
            var plot = jQuery.plot(jQuery(this), [{
                    data: attacks,
                    lines: {
                        fill: 0.6,
                        lineWidth: 0
                    },
                    color: ['#f89f9f']
                }, {
                    data: attacks,
                    points: {
                        show: true,
                        fill: true,
                        radius: 5,
                        fillColor: "#f89f9f",
                        lineWidth: 3
                    },
                    color: '#fff',
                    shadowSize: 0
                }],
                {
                    xaxis: {
                        tickLength: 0,
                        tickDecimals: 0,
                        mode: "categories",
                        min: 0,
                        font: {
                            lineHeight: 14,
                            style: "normal",
                            variant: "small-caps",
                            color: "#6F7B8A"
                        }
                    },
                    yaxis: {
                        ticks: 5,
                        tickDecimals: 0,
                        tickColor: "#eee",
                        font: {
                            lineHeight: 14,
                            style: "normal",
                            variant: "small-caps",
                            color: "#6F7B8A"
                        }
                    },
                    grid: {
                        hoverable: true,
                        clickable: true,
                        tickColor: "#eee",
                        borderColor: "#eee",
                        borderWidth: 1
                    }
                }
            );

            /**
             * Show tooltips
             */
            jQuery(this).bind("plothover", function (event, pos, item) {
                if (item) {
                    var x = parseInt(item.datapoint[1]);

                    jQuery("#tooltip").html(x + ' attacks.')
                        .css({top: item.pageY - 25, left: item.pageX + 10})
                        .fadeIn(200);
                } else {
                    jQuery("#tooltip").hide();
                }
            });

        });
    },
    /**
     * Create the dropdown elements
     *
     * @param elements
     */
    Dropdowns: function (elements) {
        elements.each(function () {
            var $btn = jQuery(this).find('.system-check-dropdown-btn'),
                $message = jQuery($btn.data('target'));

            /**
             * Bind the event
             */
            $btn.click(function (e) {
                e.preventDefault();
                $message.slideToggle('fast');
            });
        });
    },
    /**
     * Build the system check circular progress bar
     *
     * @param elements
     * @constructor
     */
    Knob: function (elements) {
        elements.each(function () {
            var $options = {
                'min': jQuery(this).data('min'),
                'max': jQuery(this).data('max'),
                'readOnly': true,
                'width': 90,
                'height': 90,
                'inputColor': '#000000',
                'dynamicDraw': true,
                'thickness': 0.3,
                'tickColorizeValues': true,
                'change': function (v) {
                    var grade = v;
                    var color = '';
                    if (grade <= 75) {
                        color = '#FFB601';
                    } else if (grade <= 90) {
                        color = '#00ADEF';
                    } else if (grade <= 100) {
                        color = '#97cc04';
                    }
                    this.fgColor = color;
                }
            };
            /**
             * Initiate the library
             */
            jQuery(this).knob($options);
        })
    }
};


/**
 * File manager handler object (selection/navigation)
 */
RSFirewall.file_manager = {
    options: null,
    element: null,
    init: function (element, options) {
        this.options = options;
        this.element = element;

        return this;
    },

    show: function() {
        var that                = this,
            modal               = jQuery(this.options.target),
            selection_separator = "\n";
            selection_textarea  = jQuery(this.options.selection); // this is the textarea where the selection will appear

        // trigger the show function callback on the modal
        modal.unbind('show').bind('show', function(){
            var rsmodal = jQuery(this);

            // Build the select item button
            var add_btn = jQuery('<button>', {
                'class': 'button button-primary',
                'type' : 'button',
                'id'  : 'add_files_btn'
            });

            add_btn.on('click', function(){
                var current_val = selection_textarea.val();
                if (rsmodal.find('[name="cid[]"]').length) {
                    rsmodal.find('[name="cid[]"]').each(function (index, checkbox) {
                        if (jQuery(checkbox).is(':checked')) {
                            var file = jQuery(checkbox).val();
                            if ( current_val.length > 0) {
                                current_val += selection_separator + file;
                            } else  {
                                current_val = file;
                            }
                        }
                    });
                }
                selection_textarea.val(current_val);
            });

            add_btn.append(rsfirewall_file_manager.modal_select_items_button_text);

            rsmodal.find('.modal-footer').find('#add_files_btn').remove();
            rsmodal.find('.modal-footer [data-dismiss="rsmodal"]').before(add_btn);

            that.get_ajax_list(modal);
        });
    },

    get_ajax_list: function(modal, path){
        var that = this;

        var sendData = {
            'action': 'rsfirewall_folders_open_file_manager',
            'security':   rsfirewall_file_manager.file_manager_nonce
        };

        if (typeof this.options.limitto != 'undefined') {
            sendData.limit_to = encodeURI(this.options.limitto);
        }

        if (typeof path != 'undefined') {
            sendData.path = path;
        }

        jQuery.ajax({
            type    : 'POST',
            url     : ajaxurl,
            dataType: 'html',
            data    : sendData,
            beforeSend: function(jqXHR){
                modal.find('.modal-body').html('<div class="loading-holder" style="min-height:100px"><span id="loader" class="dashicons dashicons-update rsfirewall-animated"></span></div>');
            },
            complete: function (data) {
                that.remove_loader(modal);
                modal.find('.modal-body').html(data.responseText);
            },
            fail: function(jqXHR, textStatus, errorThrown) {
                that.handle_modal_errors(modal, errorThrown);
            }
        });
    },

    remove_loader: function(modal) {
        modal.find('.loading-holder').remove();
    },

    handle_modal_errors: function(modal, message) {
        this.remove_loader(modal);
        modal.find('.modal-body').append('<p><strong>' + message + '</p></strong>').hide().fadeIn();
    },

    load_path: function(e, element) {
        e.preventDefault();
        var modal  = jQuery(this.options.target);
        var path = jQuery(element).data('path');

        this.get_ajax_list(modal, path);
    },

    checkAll: function() {
        var modal = jQuery(this.options.target);
        if (modal.find('[name="cid[]"]').length) {
            modal.find('[name="cid[]"]').each(function () {
                this.checked = !this.checked
            });
        }
    }
};

jQuery(document).on('click', '[data-filemanager="1"]', function (e) {
    var $this = jQuery(this),
        options = $this.data();
        manager = RSFirewall.file_manager.init($this, options);

    manager.show();

    e.preventDefault();
});

// Handle view content in modal without external

/**
 * View contents handler object (selection/navigation)
 */
RSFirewall.view_contents = {
    options: null,
    element: null,
    init: function (element, options) {
        this.options = options;
        this.element = element;

        return this;
    },

    show: function() {
        var that                = this,
            modal               = jQuery(this.options.target);

        // trigger the show function callback on the modal
        modal.unbind('show').bind('show', function(){
            that.get_ajax_list(modal, that.options.file);
        });
    },

    get_ajax_list: function(modal, file){
        var that = this;

        var sendData = {
            'action': 'rsfirewall_'+that.options.action,
            'security':   rsfirewall_check_security.view_contents_nonce
        };

        if (typeof file != 'undefined') {
            sendData.file = file;
        }

        if (typeof that.options.hid != 'undefined') {
            sendData.hid = that.options.hid;
        }

        jQuery.ajax({
            type    : 'POST',
            url     : ajaxurl,
            dataType: 'html',
            data    : sendData,
            beforeSend: function(jqXHR){
                modal.find('.modal-body').html('<div class="loading-holder" style="min-height:100px"><span id="loader" class="dashicons dashicons-update rsfirewall-animated"></span></div>');
            },
            complete: function (data) {
                that.remove_loader(modal);
                modal.find('.modal-body').html(data.responseText);
            },
            fail: function(jqXHR, textStatus, errorThrown) {
                that.handle_modal_errors(modal, errorThrown);
            }
        });
    },

    remove_loader: function(modal) {
        modal.find('.loading-holder').remove();
    },

    handle_modal_errors: function(modal, message) {
        this.remove_loader(modal);
        modal.find('.modal-body').append('<p><strong>' + message + '</p></strong>').hide().fadeIn();
    }
};

jQuery(document).on('click', '[data-filemanager="1"]', function (e) {
    var $this = jQuery(this),
        options = $this.data();
    manager = RSFirewall.file_manager.init($this, options);

    manager.show();

    e.preventDefault();
});

jQuery(document).on('click', '[data-viewcontent="1"]', function (e) {
    var $this = jQuery(this),
        options = $this.data();
    manager = RSFirewall.view_contents.init($this, options);

    manager.show();

    e.preventDefault();
});

/**
 * Whitelist PHP files modal
 */
RSFirewall.whitelistfiles = {
    options: null,
    element: null,
    init: function (element, options) {
        this.options = options;
        this.element = element;

        return this;
    },

    show: function() {
        var that                = this,
            modal               = jQuery(this.options.target);

        // trigger the show function callback on the modal
        modal.unbind('show').bind('show', function(){
            that.get_ajax_content(modal);
        });
    },

    get_ajax_content: function(modal, extra_data){
        var that = this;

        var sendData = {
            'action': 'rsfirewall_configuration_whitelist_php_form_list',
            'security': rsfirewall_whitelist_php_files.whitelist_php_files_nonce
        };

        if (typeof extra_data != 'undefined') {
            jQuery.extend( sendData, extra_data );
        }

        console.log(sendData);

        jQuery.ajax({
            type    : 'POST',
            url     : ajaxurl,
            dataType: 'html',
            data    : sendData,
            beforeSend: function(jqXHR){
                modal.find('.modal-body').html('<div class="loading-holder" style="min-height:100px"><span id="loader" class="dashicons dashicons-update rsfirewall-animated"></span></div>');
            },
            complete: function (data) {
                that.remove_loader(modal);
                modal.find('.modal-body').html(data.responseText);

                // initiate the trigger functions needed in the modal
                // action triggered by the form for adding a file to the whitelist
                modal.find('.modal-body').find('#add_to_whitelist').unbind('submit').bind('submit', function(evt){
                    evt.preventDefault();
                    that.add_file(modal);
                });

                // action to check/uncheck all the checkboxes for the current listed files
                modal.find('#rsfirewall-whitelist-check-all').unbind('click').bind('click', function() {
                   that.check_all(this, modal);
                });

                // action for the actual delete from the whitelist
                modal.find('#rsfirewall-whitelist-delete').unbind('click').bind('click', function() {
                    that.delete_whitelisted(modal);
                });

                // extra data is present then an action of deletion or addition has occoured, so we need to recount the whitelisted files
                if (typeof extra_data != 'undefined') {
                    if (modal.find('#rsf-whitelisted-files-list').length) {
                        var whitelisted_files = modal.find('.rsfirewall-whitelisted-files tr').length;
                        jQuery('#rsf-whitelisted-count').html(whitelisted_files);
                    } else {
                        // it meens that there is no list so there must be no whitelisted files
                        jQuery('#rsf-whitelisted-count').html(0);
                    }
                }
            },
            fail: function(jqXHR, textStatus, errorThrown) {
                that.handle_modal_errors(modal, errorThrown);
            }
        });
    },

    add_file: function(modal) {
        var file     = jQuery('#rsf-whitelist-file').val().trim();
        var folder   = jQuery('#rsf-whitelist-folder').val();
        var security = jQuery('#rsf-whitelist-security');

        if (file == '') {
            alert(rsfirewall_whitelist_php_files.modal_no_file_specified);
            return false;
        }

        if (!security.length) {
            alert(rsfirewall_whitelist_php_files.modal_no_noce_detected);
            return false;
        }

        var extra_data = {
            'action'    : 'rsfirewall_configuration_add_file_to_whitelist',
            'security'  : security.val(),
            'file'      : file,
            'folder'    : folder
        };

        this.get_ajax_content(modal, extra_data);

    },

    check_all: function(checkbox, modal) {
        checkbox = jQuery(checkbox);

        if (checkbox.is(':checked')) {
            modal.find('.rsfirewall-whitelist-delete').attr('checked', 'checked');
        } else {
            modal.find('.rsfirewall-whitelist-delete').removeAttr('checked');
        }
    },

    delete_whitelisted: function(modal) {
        var checkboxes = modal.find('.rsfirewall-whitelist-delete:checked');
        if (!checkboxes.length) {
            alert(rsfirewall_whitelist_php_files.modal_please_make_selection);
            return false;
        }

        if (!jQuery('#rsfirewall-whitelist-delete-nonce').length) {
            alert(rsfirewall_whitelist_php_files.modal_no_noce_detected);
            return false;
        }

        var extra_data = {
            'action': 'rsfirewall_configuration_delete_whitelisted_files',
            'security': jQuery('#rsfirewall-whitelist-delete-nonce').val()
        };

        var selected_files = [];
        checkboxes.each(function() {
            var file = {
               'file'   : jQuery(this).data('file'),
               'folder' : jQuery(this).data('folder')
            };

            selected_files.push(file);
        });

        extra_data.files = selected_files;

        this.get_ajax_content(modal, extra_data);
    },

    remove_loader: function(modal) {
        modal.find('.loading-holder').remove();
    },

    handle_modal_errors: function(modal, message) {
        this.remove_loader(modal);
        modal.find('.modal-body').append('<p><strong>' + message + '</p></strong>').hide().fadeIn();
    }
};

jQuery(document).on('click', '[data-whitelistfiles="1"]', function (e) {
    var $this = jQuery(this),
        options = $this.data();
    manager = RSFirewall.whitelistfiles.init($this, options);

    manager.show();

    e.preventDefault();
});

