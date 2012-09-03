 /*!
 * Buttons helper for fancyBox
 * version: 1.0.2
 * @requires fancyBox v2.0 or later
 *
 * Usage: 
 *     $(".fancybox").fancybox({
 *         buttons: {
 *             position : 'top'
 *         }
 *     });
 * 
 * Options:
 *     tpl - HTML template
 *     position - 'top' or 'bottom'
 * 
 */
(function ($) {

    window._gaq = window._gaq || [];
    
	//Shortcut for fancyBox object
	var F = $.fancybox;

	//Add helper object
	F.helpers.buttons = {
		tpl: ['<div id="fancybox-buttons"><div class="btn-group">',
		          '<a href="javascript:;" class="btn btnPrev"><i class="icon-chevron-left"></i></a>',
    		      '<a href="javascript:;" class="btn btnPlay"><i class="icon-play"></i></a>',
		          '<a href="javascript:;" class="btn btnNext"><i class="icon-chevron-right"></i></a>',
		          '<a href="javascript:;" class="btn btnToggle"><i class="icon-resize-full"></i></a>',		          
		          '<a href="javascript:;" class="btn btnDownload"><i class="icon-download"></i></a>',
		          '<a href="javascript:jQuery.fancybox.close();" class="btn"><i class="icon-remove"></i></a>',
		          '</div></div>'].join(""),
		list: null,
		buttons: {},

		update: function () {
			var toggle      = this.buttons.toggle;

            toggle.removeClass('disabled')
                .children('i')
                .removeClass('icon-resize-small')
                .addClass('icon-resize-full');

			//Size toggle button
			if (F.current.canShrink) {
				toggle.children('i')
                      .removeClass('icon-resize-full')
                      .addClass('icon-resize-small');

			} else if (!F.current.canExpand) {
				toggle.addClass('disabled');
			}
		},

		beforeLoad: function (opts) {
			//Remove self if gallery do not have at least two items
			if (F.group.length < 2) {
				F.coming.helpers.buttons = false;
				F.coming.closeBtn = true;

				return;
			}

			//Increase top margin to give space for buttons
			F.coming.margin[ opts.position === 'bottom' ? 2 : 0 ] += 30;
		},

		onPlayStart: function () {
			if (this.list) {
				this.buttons.play.attr('title', 'Pause slideshow')
				    .children('i')
				    .addClass('icon-pause')
				    .removeClass('icon-play');
			}
		},

		onPlayEnd: function () {
			if (this.list) {
				this.buttons.play.attr('title', 'Start slideshow')
				.children('i')
				.removeClass('icon-pause')
				.addClass('icon-play');
			}
		},

		afterShow: function (opts) {
			var buttons,
    			self        = this,
    			link        = $(F.current.element),
    			downloadUrl = link.data('download-url'),
    			filename    = link.data('filename'),
    			eventname   = link.data('fancybox-group');

			if (!this.list) {
				this.list = $(opts.tpl || this.tpl).addClass(opts.position || 'top').appendTo('body');

				this.buttons = {
				    download : this.list.find('.btnDownload'),
					prev : this.list.find('.btnPrev').click( F.prev ),
					next : this.list.find('.btnNext').click( F.next ),
					play : this.list.find('.btnPlay').click( F.play ),
					toggle : this.list.find('.btnToggle').click( F.toggle )
				}
				
				this.buttons.download.on('click', function() {
        			_gaq.push(["_trackEvent", eventname, 'Download', filename]);			
				});
			}
			buttons = this.buttons;
						
            buttons.download.attr('href', downloadUrl);

			//Prev
			if (F.current.index > 0 || F.current.loop) {
				buttons.prev.removeClass('disabled');
			} else {
				buttons.prev.addClass('disabled');
			}

			//Next / Play
			if (F.current.loop || F.current.index < F.group.length - 1) {
				buttons.next.removeClass('disabled');
				buttons.play.removeClass('disabled');

			} else {
				buttons.next.addClass('disabled');
				buttons.play.addClass('disabled');
			}

			this.update();
			_gaq.push(["_trackEvent", eventname, 'View', filename]);			
		},

		onUpdate: function () {
			this.update();
		},

		beforeClose: function () {
			if (this.list) {
				this.list.remove();
			}

			this.list = null;
			this.buttons = {};
		}
	};

}(jQuery));