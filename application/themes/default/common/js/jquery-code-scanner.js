(function ($) {
    $.fn.codeScanner = function (options) {
        var settings = $.extend({}, $.fn.codeScanner.defaults, options);

        return this.each(function () {
            var pressed = false;
            var chars = [];
            var $input = $(this);
			$( "#barcode" ).focus();
            $(window).keypress(function (e) {
                var keycode = (e.which) ? e.which : e.keyCode;
                if ((keycode >= 65 && keycode <= 90) ||
                    (keycode >= 97 && keycode <= 122) ||
                    (keycode >= 48 && keycode <= 57)
                ) {
                    chars.push(String.fromCharCode(e.which));
                }
                // console.log(e.which + ":" + chars.join("|"));
                if (pressed == false) {
                    setTimeout(function () {
                        if (chars.length >= settings.minEntryChars) {
                            //var barcode = chars.join('');
							var barcode = $("#barcode").val();
							settings.onScan($input, barcode);
                        }
                        chars = [];
                        pressed = false;
                    }, settings.maxEntryTime);
                }
                pressed = true;
            });

            $(this).keypress(function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                }
            });

            return $(this);
        });
    };

    $.fn.codeScanner.defaults = {
        minEntryChars: 1,
        maxEntryTime: 100,
        onScan: function ($element, barcode) {
			
			$( "#barcode" ).focus();
            $element.val(barcode);
			getBagdetails();
			/*if($element.attr('id') == 'orderline_barcode')
			{
				getOrderlinedetails(barcode);
			}
			else
			{
				getBagdetails(barcode);
			}*/
			
        }
    };
})(jQuery);
