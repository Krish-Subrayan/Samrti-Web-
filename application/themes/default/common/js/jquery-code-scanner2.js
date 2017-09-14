(function ($) {
    $.fn.codeScanner1 = function (options) {
        var settings = $.extend({}, $.fn.codeScanner1.defaults, options);

        return this.each(function () {
            var pressed = false;
            var chars = [];
            var $input = $(this);
			//$( "#heatseal" ).focus();
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
							var barcode = $("#heatseal").val();
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

    $.fn.codeScanner1.defaults = {
        minEntryChars: 1,
        maxEntryTime: 100,
        onScan: function ($element, barcode) {
			 $("#heatseal" ).focus();
             $element.val(barcode);
			//var first2 = barcode.substr(0, 2);
			//  var barlength=barcode.toString().length;
			//if(first2 == 10 && barlength == 8)
			// {
				getHeatsealdetails();
			// }
			
			
			
        }
    };
})(jQuery);
