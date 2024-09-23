// Get the modal

RSModal = {
    modal : null,
    options : null,
    $body : null,
    sizes: {
      'small' : 'modal-sm',
      'medium' : false,
      'large' : 'modal-lg'
    },

    init : function(element, options) {
        this.options = options;
        this.$body = jQuery(document.body);
        this.modal = jQuery(element).delegate('[data-dismiss="rsmodal"]', 'click.dismiss.rsmodal', jQuery.proxy(this.hide, this));

        return this;
    },

    toggle: function () {
        return this[!this.isShown ? 'show' : 'hide']()
    },

    show : function() {
        var that = this, e = jQuery.Event('show');

        this.modal.trigger(e);
        if (this.isShown || e.isDefaultPrevented()) return;

        this.isShown = true;
        this.escape();

        this.backdrop(function () {
            // set the modal size
            if (that.sizes[that.options.size]) {
                that.modal.find('.modal-dialog').removeClass('modal-sm modal-lg').addClass(that.sizes[that.options.size]);
            }

            // if the title is provided
            if (that.options.title && that.modal.find('.modal-header > .modal-title').length) {
               that.modal.find('.modal-header > .modal-title').empty().append(that.options.title);
            }

            //remove the footer if specified
            if(!that.options.usefooter) {
                that.modal.find('.modal-footer').hide();
            } else {
                // if the footer is displayed check if the close button should be hidden
                if (!that.options.showclose) {
                    that.modal.find('.modal-footer > [data-dismiss="rsmodal"]').hide();
                } else {
                    that.modal.find('.modal-footer > [data-dismiss="rsmodal"]').empty().append(that.options.btnclosetext);
                }
            }

            // if an external source is added then we need to use an iframe
            if (that.options.external) {
                var iframe_element = jQuery('<iframe>', {
                    'class': 'rsmodal-iframe',
                    'src': that.options.external,
                    'style' : 'width:100%; min-height:490px;'
                });

                that.modal.find('.modal-body').empty().append(iframe_element);
            }

            var transition = rstransitionEnd() && that.modal.hasClass('fade');

            that.$body.addClass('modal-open');

            if (!that.modal.parent().length) {
                that.modal.appendTo(that.$body);
            }

            that.modal.show();

            if (transition) {
                that.modal[0].offsetWidth // force reflow
            }

            that.modal.addClass('in').attr('aria-hidden', false);

            that.enforceFocus();

            transition ? that.modal.one(rstransitionEnd(), function () { that.modal.focus().trigger('shown') }) : that.modal.focus().trigger('shown');

        });
    },

    hide: function (e) {
        e && e.preventDefault();

        e = jQuery.Event('hide');

        this.modal.trigger(e);

        if (!this.isShown || e.isDefaultPrevented()) return;

        this.isShown = false;

        this.escape();

        jQuery(document).off('focusin.rsmodal');

        this.modal.removeClass('in').attr('aria-hidden', true);

        rstransitionEnd() && this.modal.hasClass('fade') ? this.hideWithTransition() : this.hideModal();
    },

    hideWithTransition: function () {
        var that = this
            , timeout = setTimeout(function () {
            that.modal.off(rstransitionEnd())
            that.hideModal()
        }, 500);

        this.modal.one(rstransitionEnd(), function () {
            clearTimeout(timeout);
            that.hideModal();
        })
    },

    hideModal: function () {
        var that = this;
        this.modal.hide();

        this.backdrop(function () {
            that.removeBackdrop();
            // add the footer back in case other modals need it
            if(!that.options.usefooter) {
                that.modal.find('.modal-footer').show();
            } else {
                // if the footer is displayed check if the close button should be hidden
                if (!that.options.showclose) {
                    that.modal.find('.modal-footer > [data-dismiss="rsmodal"]').show();
                }
            }

            if (that.options.external) {
                that.modal.find('.modal-body .rsmodal-iframe').remove();
            }

            that.modal.trigger('hidden');
            that.$body.removeClass('modal-open');
        });
    },

    removeBackdrop: function () {
        this.$backdrop && this.$backdrop.remove();
        this.$backdrop = null
    },

    enforceFocus: function () {
        var that = this;
        jQuery(document).on('focusin.rsmodal', function (e) {
            if (that.modal[0] !== e.target && !that.modal.has(e.target).length) {
                that.modal.focus()
            }
        })
    },

    escape: function () {
        var that = this;
        if (this.isShown && this.options.keyboard) {
            this.modal.on('keyup.dismiss.rsmodal', function ( e ) {
                e.which == 27 && that.hide()
            })
        } else if (!this.isShown) {
            this.modal.off('keyup.dismiss.rsmodal');
        }
    },

    backdrop: function (callback) {
        var that = this;
        var animate = this.modal.hasClass('fade') ? 'fade' : '';

        if (this.isShown && this.options.backdrop) {

            this.$backdrop = jQuery('<div class="rsmodal-backdrop ' + animate + '" />')
                .appendTo(this.$body);

            this.$backdrop.click(
                this.options.backdrop == 'static' ?
                    jQuery.proxy(this.modal[0].focus, this.modal[0])
                    : jQuery.proxy(this.hide, this)
            );

            this.$backdrop.addClass('in');

            if (!callback) return;

            callback();

        } else if (!this.isShown && this.$backdrop) {
            this.$backdrop.removeClass('in');

            callback();

        } else if (callback) {
            callback()
        }
    }
};

/* RSMODAL PLUGIN DEFINITION
 * ======================= */

var old = jQuery.fn.rsmodal;

jQuery.fn.rsmodal = function (option) {
    return this.each(function () {
        var $this = jQuery(this)
            , data = $this.data('rsmodal')
            , options = jQuery.extend({}, jQuery.fn.rsmodal.defaults, $this.data(), typeof option == 'object' && option);
        if (!data) $this.data('rsmodal', (data = RSModal.init(this, options)));
        if (typeof option == 'string') data[option]();
        else if (options.show) data.show();
    })
};

jQuery.fn.rsmodal.defaults = {
    backdrop: true,
    keyboard: true,
    usefooter: true,
    showclose: true,
    btnclosetext: 'Close',
    show: true,
    external: false,
    size: 'medium'
};


/* RSMODAL NO CONFLICT
 * ================= */

jQuery.fn.rsmodal.noConflict = function () {
    jQuery.fn.rsmodal = old;
    return this
};

jQuery(document).on('click.rsmodal.data-api', '[data-toggle="rsmodal"]', function (e) {
    var $this = jQuery(this)
        , href = $this.attr('href')
        , $target = jQuery($this.attr('data-target') || (href && href.replace(/.*(?=#[^\s]+$)/, ''))) //strip for ie7
        , option = $target.data('rsmodal') ? 'toggle' : jQuery.extend({ remote:!/#/.test(href) && href }, $target.data(), $this.data())

    e.preventDefault();

    $target.rsmodal(option).one('hide', function () {
        $this.focus()
    });
});


// CSS TRANSITION SUPPORT (Shoutout: http://www.modernizr.com/)
// ============================================================

function rstransitionEnd() {
    var el = document.createElement('rsmodalfake');

    var transEndEventNames = {
        WebkitTransition : 'webkitTransitionEnd',
        MozTransition    : 'transitionend',
        OTransition      : 'oTransitionEnd otransitionend',
        transition       : 'transitionend'
    };

    for (var tname in transEndEventNames) {
        if (el.style[tname] !== undefined) {
            return { end: transEndEventNames[tname] }
        }
    }

    return false;
}
