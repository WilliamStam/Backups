/* 
 *	autoLogout: a jQuery plugin, version: 0.1.1 (2010-08-23)
 *
 *
 *	thanks mekwall (on #jQuery) for the tips :D
 *
 *
 *	Licensed under the MIT:
 *	http://www.opensource.org/licenses/mit-license.php
 *
 *	Copyright (c) 2010, William Stam (awstam -[at]- gmail [*dot*] com)

 -------- Change log ---------

 25 Septs 2010	-	added {m} to format options
 */
(function ($) {
	// --------------------------------------plugins methods--------------------------------------------------
	var methods = {
		init      :function (options) {
			return this.each(function () {
				var $this = $(this), old = $this.data("settings");
				$this.data('countShow', "0");
				if (options.logout != "none") {
					$this.data("settings", options);

					if (old) {
						if (old.LogoutTime != options.LogoutTime) {
							doResetTimer($this);
						}
					} else {
						var remainingSeconds = parseFloat(options.LogoutTime) + 1;
						$this.data("remainingSeconds", remainingSeconds);
						doTimer($this);
					}
				} else {
					$.error("logout option is required for '" + $this.attr("id") + "'");
				}
			});
		},
		logout    :function () {
			return this.each(function () {
				var $this = $(this);
				$this.data("forceLogout", "force");
				$this.data("remainingSeconds", 0);
				$this.data('countShow', "0");
				doLogout($this);
			});
		},
		resetTimer:function () {
			return this.each(function () {
				var $this = $(this);
				$this.data('countShow', "0");
				doResetTimer($this)
			});
		}
	};
	// ----------------------------------------------------------------------------------------------------

	// ------------------------------------plugins functions-----------------------------------------------
	// the counter plugin
	var doTimer = function (e) {
		var force = e.data('forceLogout');
		function timedCount() {
			var t, options = e.data("settings");

			if (force != "force") {
				c = e.data('remainingSeconds');
				//console.log(c)
				if (c <= 0) {
					clearTimeout(t);
					doLogout(e);
				} else {
					c = c - 1, minVar = Math.floor(c / 60), secVar = c % 60;
					if (secVar < 10) {
						secVar = "0" + secVar;
					}
					var t, str = options.countingDownLook.replace(/{s}/g, c).replace(/{m}/g, minVar + ":" + secVar);
					if (c <= parseFloat(options.ShowLogoutCountdown) && options.countingDownLookShow) {
						if (c == options.ShowLogoutCountdown){

							if (options.keepAliveSelector && ($(options.keepAliveSelector).length  || $("iframe").contents().find(options.keepAliveSelector).length)) {
								options.keepAlive.call(e);
								e.data("remainingSeconds", parseFloat(options.LogoutTime) + 1);
								c = parseFloat(options.LogoutTime) + 1;

							} else {
								options.onLogoutCountdown.call(e);
							}

						}

						str = options.countingDownLookShow.replace(/{s}/g, c).replace(/{m}/g, minVar + ":" + secVar);
					}
					$element = e.data("remainingSeconds", c);
					if (options.countingDownSelector && $element.find(options.countingDownSelector)) {
						$element = $element.find(options.countingDownSelector);
					}
					$element.html(str);

					options.onTimerSecond.call(e,c);



					t = setTimeout(timedCount, 1000);
				}
			}
		}

		timedCount();
	};
	// reset the timer function
	var doResetTimer = function (e) {
		var options = e.data("settings"), remainingSeconds = e.data("remainingSeconds");
		e.data("forceLogout", "clear");
		c = options.LogoutTime
		minVar = Math.floor(c / 60);
		secVar = c % 60;
		if (secVar < 10) {
			secVar = "0" + secVar;
		}
		options.onResetTimer.call(e);
		$element = e.data("remainingSeconds", parseFloat(options.LogoutTime) + 1);
		if (options.countingDownSelector && $element.find(options.countingDownSelector)) {
			$element = $element.find(options.countingDownSelector);
		}
		$element.html(options.countingDownLook.replace(/{s}/g, c).replace(/{m}/g, minVar + ":" + secVar));


		if (remainingSeconds <= 0) {
			doTimer(e);
		}

	};
	// the function that does the logging out
	var doLogout = function (e) {
		var $this = e, options = $this.data("settings"), force = $this.data('forceLogout');
		if (options) {
			if (options.keepAliveSelector && ($(options.keepAliveSelector).length > 0 || $("iframe").contents().find(options.keepAliveSelector).length > 0) && force != "force") {
				options.keepAlive.call($this);
				$this.data("remainingSeconds", parseFloat(options.LogoutTime) + 1);
				doTimer($this);
			} else {
				options.logout($this);
			}
		}
	};
	// ----------------------------------------------------------------------------------------------------

	$.fn.autoLogout = function (method) {
		var options = {
			LogoutTime          :'30',
			ShowLogoutCountdown :'5',
			onLogoutCountdown: function(){},
			onResetTimer: function(){},
			onTimerSecond: function(){},
			keepAliveSelector   :"",
			logout              :"none",
			keepAlive           :function () {
			},
			countingDownLook    :"",
			countingDownLookShow:""
		};
		if (method) {
			var settings = arguments[1];
			if (typeof method === "object") {
				var settings = arguments[0];
			}
			var options = $.extend({}, options, settings);
		}
		options = $.makeArray(options);
		if (!method || method == 'remainingSeconds') {
			return $(this).data("remainingSeconds");
		} else if (methods[method]) {
			var old = $(this).data("settings");
			if (old) {
				options = $.extend({}, old, settings);
				options = $.makeArray(options);
			}
			return methods[method].apply(this, options);
		} else if (typeof method === 'object') {
			var old = $(this).data("settings");
			if (old) {
				options = $.extend({}, old, settings);
				options = $.makeArray(options);
			}

			return methods.init.apply(this, options); // if theres options passed to the plugin create the timer
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.autoLogout');
		}

	};
})(jQuery);