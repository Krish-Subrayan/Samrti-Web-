jQuery(document).ready(function($) {
	var product_arr = new Array();
	$(".prodId").each(function() {
    var npid=this.value;
	var nqty=$('#oqty_'+npid).val();
	var nprice=$('#price_'+npid).val();
	
	product_arr.push({pid: npid,qty: nqty,price : nprice});
	
	});
	
	var $priceTotal = $(".min-cart span").text();
	
	var $priceTotal = 0;
	
	/*var min_qty_price = {};  //Or, new Object()
	min_qty_price[66] = [{"min_quantity":"1","price":"150"},{"min_quantity":"5","price":"100"}]; */
	
	/*To price for minimum quantity*/
	function getMinQtyPrice(pid){
	
	   var $totalt = $priceTotal;
		$.each(product_arr, function (index, value) {
			
		
			var qty =  value.qty;
			//var nqty=$('#oqty_'+value.pid).val();
			//if(qty >= nqty)
			//{
				//qty=qty-nqty;
			//}
			
			//alert(qty);
			var $productPrice = ((value.price) * qty);
			//	alert(value);
			if(value.pid == pid){
				for(var i = 0 ; i < min_qty_price[pid].length; i++){
					var obj = min_qty_price[pid][i];
					var min_qty = obj["min_quantity"];
					var price = obj["price"];
					//alert(price);
					if(!(min_qty > qty)){
						product_arr[index].price  = price;
						continue;
					}
				}
				$productPrice = ((value.price) * qty);
				//alert("product_price="+ $productPrice);
			}
		
		    var discount_status=$('#discount_status_' + value.pid).val();
			if(discount_status == '1' && $productPrice > 0)
			{
				var discounttxt=$('#discount_' + value.pid).val();
				var disarr = {};
				var obj = jQuery.parseJSON(discounttxt);
				$.each(obj, function (index, value) {
					disarr[index] = value;
				});
			
				if(disarr['type'] == 1)//min_qty
				{
					var dis_min_quantity=parseInt(disarr['min_quantity']);
					var dis_price=disarr['price'];
					var dis_repeat=parseInt(disarr['repeat']);
					if(qty >= dis_min_quantity)
					{
						/*if(dis_repeat == 1)
						{
							var sqty=(qty - qty % dis_min_quantity) / dis_min_quantity;
							var new_dis_price=sqty*dis_price;
							$productPrice=$productPrice-new_dis_price;
							
						}
						else
						{
							$productPrice=$productPrice-dis_price;
						}*/
						
						$productPrice=qty*dis_price;
							
					}
				}
				else if(disarr['type'] == 2)//percent 2
				{
					
					var dis_percentage=disarr['percentage'];
					var dis_repeat=parseInt(disarr['repeat']);
					if(dis_repeat == 1)
					{
							var dis_price=(product_arr[index].price*dis_percentage)/100;
							var new_dis_price=qty*dis_price;
							$productPrice=$productPrice-new_dis_price;
							
					}
					else
					{
							var dis_price=(product_arr[index].price*dis_percentage)/100;
							$productPrice=$productPrice-dis_price;
					}
							
					
				}
				else if(disarr['type'] == 3)//buy_free 3
				{
					
					var dis_buy_get=disarr['buy_get'];
					var dis_buy_get_free=disarr['buy_get_free'];
					var dis_repeat=parseInt(disarr['repeat']);
					if(dis_repeat == 1)
					{
							if(qty >= dis_buy_get)
							{
								var sqty=(qty - qty % dis_buy_get) / dis_buy_get;
								var dqty=(dis_buy_get_free*sqty);
								var dis_price=(product_arr[index].price*dqty);
								$productPrice=$productPrice-dis_price;
							}
							//var sqty=(qty - qty % dis_min_quantity) / dis_min_quantity;
					}
					else
					{
							if(qty >= dis_buy_get)
							{
								var dis_price=(product_arr[index].price*dis_buy_get_free);
								$productPrice=$productPrice-dis_price;
							}
							
						
					}
							
					
				}
				//buy_free 3 //percent 2
				//{"title":"Kilovask","type":1,"min_quantity":"5","price":"100.00","repeat":"0"}
				
				
			}
			//(6.688689).toFixed(); 
			
			
			$productPrice=$productPrice.toFixed();
			
			$('#subtotal_' + value.pid).html(' kr '+$productPrice+'');
		
			$('.subtotal_' + value.pid).val($productPrice);
			$('.new_price_' + value.pid).val(product_arr[index].price);
			//alert("before=" + $totalt);
			$totalt = parseInt($totalt) + parseInt($productPrice) ;  
			//alert("after=" + $totalt);
		});
		return $totalt;
	}	
	
	
	/*add , remove items to cart*/
	$('.minus,.plus').bind('click', function () {
		var $number = parseInt($(this).parent().find('.qty').val());
		var $pid = parseInt($(this).parent().find('.prodId').val());
		var $ori_price = parseInt($(this).parent().find('.product_price').val());
		
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
		
		if(product_arr.length > 0){
			bool = false;
			$.each(product_arr, function (index, value) {
				//alert("pid: "+ value.pid +" and qty: "+ value.qty);
				//alert(product_arr[index].qty);
				if(value.pid == $pid){
					product_arr[index].qty  = $number;
					bool = true;
				}
			});
			if(!bool){
				product_arr.push({pid: $pid,  qty: $number,price : ''});
			}
		}
		else{
			product_arr.push({pid: $pid,  qty: $number,price : ''});
		}
		
		
				var zoneinfo=$('#zoneinfo').val();
				//alert(zoneinfo);
				var zoneinfoarr = {};
				var zobj = jQuery.parseJSON(zoneinfo);
				$.each(zobj, function (index, value) {
					zoneinfoarr[index] = value;
					
				});
				
				var newsubtotal=getMinQtyPrice($pid);
				var order_type=$('#order_type').val();
				
				if(order_type != 'shop')
				{
					if(parseInt(newsubtotal) < parseInt(zoneinfoarr['min_price']))
					{
						$('#minstebel').show();
						$('#min_price').html('kr '+zoneinfoarr['min_price']+'');
						newsubtotal=zoneinfoarr['min_price'];
						
					}
					else
					{
						$('#minstebel').hide();
					}
					
					
					var delviery_amt=zoneinfoarr['delivery_charge'];
					if(parseInt(newsubtotal) >= parseInt(zoneinfoarr['free_delivery_after']))
					{
						var delviery_amt=0;
					}
					
					var free_delivery_charge=$('#free_delivery_charge').val();
					if(parseInt(free_delivery_charge) == 1)
					{
						var delviery_amt=0;
					}
					
					
					$('#delviery_amt').html('kr '+delviery_amt+'');
					
				}
				
				
				
				jQuery('.min-cart p span').text(newsubtotal);
				
				var discount=$('#order_discount_amount').val();
				
				
				
				
				
				newsubtotal=parseInt(newsubtotal)-parseInt(discount);
				
				if(order_type == 'shop')
				{
					var total_amount=parseInt(newsubtotal);
				}
				else
				{
					var total_amount=parseInt(newsubtotal)+parseInt(delviery_amt);
				}
				
				$('#total_amount').html('kr '+total_amount+'');
				$('#order_total_amount').val(total_amount);
				$('#order_delviery_amt').val(delviery_amt);
				
				
				//{"min_price":"100","delivery_charge":"79","free_delivery_after":"500","min_delivery_hours":"96"}
				
		
	});	
	
	/*add , remove items to cart*/
	$('.minusplus').bind('click', function () {
		//var $number = parseInt($(this).parent().find('.qty').val());
				var $pid = $(this).attr( "data" );
				//var $ori_price = parseInt($(this).parent().find('.product_price').val());
		
	
				var zoneinfo=$('#zoneinfo').val();
				var zoneinfoarr = {};
				var zobj = jQuery.parseJSON(zoneinfo);
				$.each(zobj, function (index, value) {
					zoneinfoarr[index] = value;
					
				});
				
				var order_type=$('#order_type').val();
				
				
				var newsubtotal=getMinQtyPrice($pid);
				
				if(order_type != 'shop')
				{
					if(parseInt(newsubtotal) < parseInt(zoneinfoarr['min_price']))
					{
						$('#minstebel').show();
						$('#min_price').html('kr. '+zoneinfoarr['min_price']+',-');
						newsubtotal=zoneinfoarr['min_price'];
						
					}
					else
					{
						$('#minstebel').hide();
					}
					
					var delviery_amt=zoneinfoarr['delivery_charge'];
					if(parseInt(newsubtotal) >= parseInt(zoneinfoarr['free_delivery_after']))
					{
						var delviery_amt=0;
					}
					
					var free_delivery_charge=$('#free_delivery_charge').val();
					if(parseInt(free_delivery_charge) == 1)
					{
						var delviery_amt=0;
					}
					
					
					$('#delviery_amt').html('kr '+delviery_amt+'');
				}
				
				
				
				
				
				
				jQuery('.min-cart p span').text(newsubtotal);
				
				var discount=$('#order_discount_amount').val();
				
				
				
				
				
				newsubtotal=parseInt(newsubtotal)-parseInt(discount);
			
				if(order_type == 'shop')
				{
					var total_amount=parseInt(newsubtotal);
				}
				else
				{
					var total_amount=parseInt(newsubtotal)+parseInt(delviery_amt);
				}
				
			
				
				$('#total_amount').html('kr '+total_amount+'');
				$('#order_total_amount').val(total_amount);
				$('#order_delviery_amt').val(delviery_amt);
				
				
				//{"min_price":"100","delivery_charge":"79","free_delivery_after":"500","min_delivery_hours":"96"}
				
		
	});	

	
});




