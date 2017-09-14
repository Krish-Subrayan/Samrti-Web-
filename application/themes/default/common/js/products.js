jQuery(document).ready(function($) {
	/*add , remove items to cart*/
	$('.minus,.plus').bind('click', function () {
		var $number = parseInt($(this).parent().find('.qty').val());
		var $pid = parseInt($(this).parent().find('.prodId').val());
		var $ori_price = parseInt($(this).parent().find('.product_price').val());
		
		$("#edit_order_frm").hide();
		$("#edit-ajax-loader").show();
		
		
		
		if ($(this).hasClass('minus')) {
			if ($number >= 0) {
				$org = $number ;
				if($number > 0)
				$number = $number - 1;
				$type = 'minus';
				$(this).parent().find('input.qty').val($number);
			}
		} else if ($(this).hasClass('plus')) {
				$number = $number + 1;
				$type = 'plus';
				$org = $number ;
				$(this).parent().find('input.qty').val($number);
		}
		
		
		
		save_editorder();
		
		
	});	
	
});




