<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Products extends MY_Controller
{
    /**
     * constructor method
     */
    public function __construct()
    {
        parent::__construct();
        //profiling::
        $this->data['controller_profiling'][] = __function__;
		
        //template file
		$current_partner_branch = $this->session->userdata['partner_branch'];
		
		//echo '<pre>';print_r($this->session->userdata);exit;
		
		
		//echo '<pre>';print_r($this->session->userdata);exit;
		//print_r($this->session->userdata);
		//echo $this->session->userdata['customer']['id'];
		
			/*$s_data['producttypecart1']='';
			$this->session->set_userdata($s_data);
			$s_data['producttypecart']='';
			$this->session->set_userdata($s_data);
			
		echo '<pre>';print_r($this->session->userdata);exit;*/
		//echo '<pre>';print_r($this->session->userdata['lastproduct']);exit;
		//echo '<pre>';print_r($this->cart->contents());exit;
		
		
		if($current_partner_branch == 14 || $current_partner_branch == 28 || $this->session->userdata['company_status'])
		{	
		
			if($this->session->userdata['current_heatseal'])
			{
				$barcode=$this->session->userdata['current_heatseal'];
				
				if(intval($barcode) == 0)
				{
					$newarray=array('status'=>'error','message'=>'Ugyldig strekkode.');
					echo json_encode($newarray);exit;
				}
				
				if(strlen($barcode) != 8)
				{
					$newarray=array('status'=>'error','message'=>'Ugyldig strekkode.');
					echo json_encode($newarray);exit;
				}
				
				$barcode2char = substr($barcode, 0, 2);
				
				if($barcode2char != '10') 
				{
					$newarray=array('status'=>'error','message'=>'Ugyldig strekkode.');
					echo json_encode($newarray);exit;
				}
				
				$found = $this->general_model->search_array($barcode, $this->session->userdata['heatsealcart']);
				
				if($found) {
					$result = array("status"=>'error',"message"=>"The heatseal was scanned already.");
					echo json_encode($result);exit;
				}
				
				$type='laundry';
				$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
				if($baginfo)
				{
					$newarray=array('status'=>'bag','message'=>'Ugyldig strekkode.');
					echo json_encode($newarray);exit;
				}
				$type='client';
				$baginfo=$this->orders_model->getBagBarcodeinfo($barcode,$type);
				if($baginfo)
				{
					$newarray=array('status'=>'bag','message'=>'Ugyldig strekkode.');
					echo json_encode($newarray);exit;
				}
				
				$customerstatus=$this->process_order_model->heatsealCustomerstatus($barcode,'');
				
				if($customerstatus)
				{
					$newarray=array('status'=>'error','message'=>'Heatseal belongs to '.$customerstatus['name'].' ('.$customerstatus['customer'].'). Please contact administrator.');
					echo json_encode($newarray);exit;
				}
				
				$barstatus=$this->process_order_model->heatsealbarcodestatus($barcode,'');
				if($barstatus)
				{
					
					$this->session->set_flashdata('notice-error', 'This heatseal was already in process with order #'.$barstatus['order'].'.');
					//$newarray=array('status'=>'error','message'=>'This heatseal was already in process with order #'.$barstatus['order'].'.');
					//echo json_encode($newarray);
				}
				
				$barinfo=$this->process_order_model->getHeatsealinfo($barcode);
				$product=$barinfo['product'];
				if(intval($product) > 0)
				{
					$proqty = $this->process_order_model->validateProducttype($product);
					if($proqty == 1)
					{
						$productinfo=$this->products_model->getProductinfo($product);
						$heatseal[0]['name'] = $productinfo['name'];
						$heatseal[0]['barcode'] = $barcode;
						self::__insert_Cart($productinfo,$heatseal);
						$session_items = array('current_heatseal' => '');
						$this->session->unset_userdata($session_items);
					}
				}
			}
			
			$carttype=$this->session->userdata['carttype'];
			if($carttype == 'heatseal')
			{	
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'products-scan.html';
			}
			else
			{
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'products.html';
			}
		}
		else
		{
			$carttype=$this->session->userdata['carttype'];
			if($carttype == 'heatseal')
			{	
				$this->data['template_file'] = PATHS_ADMIN_THEME . 'products-scan.html';
			}
			else
			{
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'products.html';
			}
			
		}
		
       
        //css settings
        $this->data['vars']['css_menu_products'] = 'current'; //menu
		
		$this->data['vars']['today'] = date('d.m.Y');
		
		$this->data['vars']['current_partner_branch'] = $current_partner_branch;
		
		$cus_id = $this->session->userdata['pos_customer_id'];
		$saldo_status = $this->payments_model->getSaldostatus($cus_id);
		$this->data['visible']['saldo_status'] = $saldo_status;
		
		if(count($this->session->userdata['cart_contents']) == 0)
		{
			$s_data['salg']='';
			$this->session->set_userdata($s_data);
		}
		

    }
    function index()
    {
        //profiling::
        $this->data['controller_profiling'][] = __function__;
        //login check
        $this->__commonAdmin_LoggedInCheck();
		
		$this->__categoryMenu();
		// echo '<pre>';print_r($this->session->userdata['type']);exit;
		//$this->session->userdata['customer']['company']
		if(!isset($this->session->userdata['start_current_staff']))
		{
			$this->session->set_flashdata('notice-error', $this->data['lang']['lang_please_login']);
			redirect('/admin/');
			exit();
		}
		
		if($this->session->userdata['start_current_staff'] != $this->session->userdata['current_staff'])
		{
			$this->session->set_flashdata('notice-error', $this->data['lang']['lang_please_login']);
			redirect('/admin/');
			exit();
		}
		
		
		if(!$this->session->userdata['pos_customer_id'])
		{
			redirect('/');
		}
		else
		{
		
		}
		
		
        //uri - action segment
        $action = $this->uri->segment(3);
        //get data
        $this->__pulldownLists();
		
		
        //re-route to correct method
        switch ($action) {
	
			/*case 'parkere':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'parkerte.orders.html';
				 $this->__page();
           		 break;
			*/	
            case 'category':
			    $this->__listProducts();
                break;
				
            case 'add-product':
			    $this->__addCart();
                break;
				
            case 'edit-product':
			    $this->__editCart();
                break;
				
            case 'update-cart':
			    $this->__updateCart();
                break;
				
           case 'insert-cart':
		  		$this->__insertCart();
                break;
				
            case 'update-utlevering':
			    $this->__updateUtlevering();
                break;
				
			case 'get-voucher':
				$this->__getVoucher();
          		 break;	
		   
		   case 'saveParkere':
				$this->__saveParkere();
			   break;
			   
			case 'search-product':
				$this->__searchProduct();
          		 break;	
			   
			case 'update-product-price':
				$this->__updateProductPrice();
          		 break;	
			 case 'skipsaldo':
				$this->__skipsaldo();
          		 break;	
			   
			case 'print':
				 //template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'print.heatseal.html';
          		 break;	
	
		    case 'get-products':
				$this->__getProductsList();
			   break;
			  case 'add-parkerte-cart':
				$this->__addParkerteCart();
			   break;
			 
		   case 'parkerte':
				//template file
				 $this->data['template_file'] = PATHS_ADMIN_THEME . 'parkerte.orders.html';
				 $this->__getCustomerDetail();
				 $this->__getparkerte();
				 //css settings
				 $this->data['vars']['css_menu_parkerte'] = 'current'; //menu
				 $this->data['vars']['css_menu_products'] = ''; //menu
           		 break;
			 default:
				$this->__page();
        }
        //load view
        $this->__flmView('admin/main');
    }
	
	
     /**
      * search product
      */
     function __searchProduct(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		 
		$keyword = $this->input->post('keyword');
		
		$this->data['reg_blocks'][] = 'products';
		//$this->data['blocks']['products'] = $this->products_model->searchProduct($keyword);
		
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		$this->data['blocks']['products'] = $this->products_model->getProductsearch('','main',$id,$this->session->userdata['logged_in'],$subscription,$keyword);
		
		//echo '<pre>';print_r($this->data['blocks']['products']);exit;
			
			
		$this->data['debug'][] = $this->products_model->debug_data;
		
		$str ='<h2 class="title">Search for : '.ucfirst($keyword).'</h2>';
		
		if (count($this->data['blocks']['products']) > 0) {
			
			for($i=0;$i<count($this->data['blocks']['products']);$i++){
				

				$this->data['blocks']['products'][$i]['i'] = $i;
				$path_parts = pathinfo($this->data['blocks']['products'][$i]['path']);
				$this->data['blocks']['products'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
				
				if(($i%4)== 0){
					if($i!=0){
					 $str .=' <div class="clearfix"></div>
					</div>';
					}
				 $str .='<div class="product-block">';
				}
				
			  	$color = $this->data['blocks']['products'][$i]['bg_color'];
				

				  $str .='<div class="col-md-3 no-padd text-center">';
				  
					//$str .='<div class="pblock"  style="background:#'.$color.'"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#addProductModal" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'" class="" data-toggle="modal" data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';

												
					 $str .='<div class="pblock"  style="background:#'.$color.'"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'@'.$this->data['blocks']['products'][$i]['note'].'" class=""  data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';
				  
												
				   $str .='</div>';
				  
				if($i == (count($this->data['blocks']['products'])-1)){
					 $str .=' <div class="clearfix"></div>
					</div>';
				}
				
				
			}//for
			
			$str.='<script type="text/javascript">
	$(document).ready(function() {		
			$(\'a.productimg\').click(function(){  
			var title = $(this).data(\'title\');
		    $("h4#product-title").html(title);		
			var stuff = $(this).attr(\'data-stuff\').split(\'@\');
			var id = stuff[0];
			 $(\'#quantity\').val(\'1\');
			 $(\'#last_product\').val(id);
			 
	    });
	
		
});
</script>';
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg"><h2>Ingen resultat</h2></div>';
		}
		
		$result = array("prod_list"=>$str);
		echo json_encode($result);exit;
	
	 }
	 
	 
	
     /**
      * update Product Price
      */
     function __updateProductPrice(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		 
		$id = $this->input->post('category');
		
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //get results
		if($id == 12)
		{
			
			$this->data['reg_blocks'][] = 'products';
			//$this->data['blocks']['products']=$this->products_model->getTilbudProducts();
			$tilbudProducts=$this->products_model->getTilbudProducts();
			if(count($tilbudProducts) > 0)
			{
				$i=0;
				foreach($tilbudProducts as $tilbuditems)
				{
					if($tilbuditems['id'] != '66')
					{
						$this->data['blocks']['products'][$i]=$tilbuditems;
					}
					
					$i++;
				}
			}
			
		}
		else if($id == 13)
		{
			
			$cus_id = $this->session->userdata['pos_customer_id'];
			$subscription = $this->payments_model->getSaldostatus($cus_id);
			
			//get results
			$this->data['reg_blocks'][] = 'products';
			$this->data['blocks']['products'] = $this->products_model->getPopularProduct($cus_id,'main',$this->session->userdata['logged_in'],$subscription);
			$this->data['debug'][] = $this->products_model->debug_data;	
			
			
		}
		else
		{
			$this->data['reg_blocks'][] = 'products';
			$cus_id = $this->session->userdata['pos_customer_id'];
			$subscription = $this->payments_model->getSaldostatus($cus_id);
			$this->data['blocks']['products'] = $this->products_model->getProduct('','main',$id,$this->session->userdata['logged_in'],$subscription);
			
		}
		
		$str ='';
		if (count($this->data['blocks']['products']) > 0) {
			
			for($i=0;$i<count($this->data['blocks']['products']);$i++){
					
				$this->data['blocks']['products'][$i]['i'] = $i;
				
				$path_parts = pathinfo($this->data['blocks']['products'][$i]['path']);
				$this->data['blocks']['products'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
				
				if(($i%4)== 0){
					if($i!=0){
					 $str .=' <div class="clearfix"></div>
					</div>';
					}
				 $str .='<div class="product-block">';
				}
				 
				  $str .='<div class="col-md-3 no-padd text-center">';
				  
			  	$color = $this->data['blocks']['products'][$i]['bg_color'];
				  
				  
				 if($this->data['blocks']['products'][$i]['child'] > 0){
					 
					//$str .='<div class="pblock"><a class="catclass" id="'.$this->data['blocks']['products'][$i]['cid'].'"  rel="'.$this->data['blocks']['products'][$i]['pbcid'].'" href="#">'.$this->data['blocks']['products'][$i]['name']." (<span class='child'>".$this->data['blocks']['products'][$i]['child'].'</span>)</a></div>';
				
					$str .='<div class="pblock"  style="background:#'.$color.'"><a class="catclass" id="'.$this->data['blocks']['products'][$i]['cid'].'"  rel="'.$this->data['blocks']['products'][$i]['pbcid'].'" href="#">'.$this->data['blocks']['products'][$i]['name'].' <br>(<span class="child">'.$this->data['blocks']['products'][$i]['child'].'</span>)</a></div>';
				
				
				
				 }
				 else{
					 

				//$str .='<div class="pblock"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#addProductModal" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'" class="" data-toggle="modal" data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';
					 
												
					 $str .='<div class="pblock"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'@'.$this->data['blocks']['products'][$i]['note'].'" class="" data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';
					 										
				 }
					 
												
				   $str .='</div>';
				  
				if($i == (count($this->data['blocks']['products'])-1)){
					 $str .=' <div class="clearfix"></div>
					</div>';
				}
				
			}//for
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg"><h2>Ingen resultat</h2></div>';
		}
		
		$result = array("prod_list"=>$str);
		echo json_encode($result);exit;
	
	 }
	
	
	/*insert items to cart  */
	function __insertCart(){
		
		  if(count($_POST) > 0)
		  {
			$id = $this->input->post('pid');
			$catid = $this->input->post('catid');
			$name = $this->input->post('name');
			$price = $this->input->post('price');
			$qty = $this->input->post('qty');
			$img = $this->input->post('img');
			$desp = $this->input->post('desp');
			$gtype = $this->input->post('gtype');
			$duration = $this->input->post('duration');
			$complain = $this->input->post('complain');
			$in_house = $this->input->post('in_house');
			$spl_instruction = $this->input->post('spl_instruction');
			
			$s_data=array();
			$s_data['lastproduct']=$id;
			$this->session->set_userdata($s_data);
			/*if($id == 143)
			{
				$price=$qty;
				$qty=1;
				if(intval($price) == 0)
				{	
					$qty=0;
				}
			}*/
			$price = ($complain == '1')  ?   0 : $price;
			if($id == 143)
			{
				$price=$qty;
				$qty=1;
				
				if($complain == '1')
				{
					$price=0;
					$qty=1;
				}
				else
				{
					if(intval($price) == 0)
					{	
						$qty=0;
					}
				}
				
				
				
			}
			
			
			
			/*$id = 77;
			$name = 'Teppe';
			$price = '199.00';
			$qty = '1';
			$img = 'http://secureserver.no/pos/images/silkebluse.jpg';
			$desp = '';
			$gtype = 'rens';
			$duration = '168';
			$complain = 0;
			$in_house = 0;
			$spl_instruction = '';*/
			
			
			
			$pdata = $this->products_model->getProduct($id,'main','',$this->session->userdata['logged_in']);
			//print_r($pdata);
			$in_meter = $pdata['in_meter'];
			
			
			$days = floor (($duration*60) / 1440);
			
		
			
			if(($duration == 96)){
				
				$c_date=date('Y-m-d');
				$date = new DateTime($c_date);
				
				$delivery_date=addDays($c_date,$days,true);
				$delivery_date=date('d.m.Y',strtotime($delivery_date));
			}
			else{
				
				$c_date = date("Y-m-d", strtotime("- 1 day"));
				$date = new DateTime($c_date);
				
				$days=$days+1;
				$date->modify("+$days day");
				$delivery_date = $date->format('d.m.Y');
			}
			
			
			
			
			$first =$c_date;
			
			$last=$delivery_date;
			
			$dates = array();
			$current = strtotime($first);
			$last = strtotime($last);
			$output_format = 'Y-m-d';
			while( $current <= $last ) {

				$date=date('l',$current);
				
				$special_date=date($output_format, $current);
				$sdelivery_date = $this->general_model->checkSpecialdate($special_date);
				//$date != 'Saturday' &&
				if($date != 'Sunday' && $sdelivery_date)  
				{
					$dates[] = date($output_format, $current);
				}
				$current = strtotime('+1 day', $current);
			}
			
			
			$sdelivery_date=end($dates);
			
			$delivery_date=date('d.m.Y',strtotime($sdelivery_date));
			
			
			
			$price = ($complain!='1')  ?   $price : 0;
			$price = ($in_meter !='1')  ?   $price : 0;
			
				
			$dataa = $this->products_model->getProduct_Discount($id);
			$newdiscount=array();
			if(count($dataa) > 0)
			{
				$newdiscount=$dataa[0];
			}
			
			//insert into cart items
			$this->cart->product_name_rules ='\d\D';    //remove product name validation for special characters
			if($qty!=''){
				$data[] =  array(
						'id'      => $id,
						'name'     => $name,
						'price'   => $price,
						'qty'    => $qty,
						'utlevering' =>$delivery_date,
						'options' => array('image'=>$img,'description'=>$spl_instruction,'gtype'=>$gtype,'discount'=>$newdiscount,'duration'=>$duration,'complain'=>$complain,'in_house'=>$in_house)
			     );
				
				
			
			$result = $this->cart->insert($data);       //insert cart data
			
			
			/*$response = array("error"=>$result);
			echo json_encode($response);exit;*/
			
			}
		  }
			
			// reverse in descending order
			$data = array_reverse($this->cart->contents());
		 
			 $this->data['reg_blocks'][] = 'cart';
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			 $j=0;
			 $temp = 0;
			 foreach ($data as $items){
				 //print_r($items);
				 $date = $items['utlevering'];
					//echo  $temp ."=". $date;
				 if( (strtotime($temp) != strtotime($date)) || ($j==0)){
				 	$this->data['blocks']['cart'][$date][] = $items;
				 }
				 else{
					 $this->data['blocks']['cart'][$temp][] = $items;
				 }
				 $temp  =  $date;
				 $j++;
			 }
			 

			 $total =0;
			 //print_r($this->data['blocks']['cart']); 
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			if (count($this->data['blocks']['cart']) > 0) {
			  $k = 0;
			  $str ='';
			  $row = array();
			  foreach($this->data['blocks']['cart'] as $key => $value) {
				$day = $key;
				for($j=0;$j<count($this->data['blocks']['cart'][$day]);$j++){ 
					
						$gtype = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
						
						$row[$day][$cart_type][$k] = $this->data['blocks']['cart'][$day][$j]['rowid'];

						$this->data['blocks'][$cart_type][$k]['heatseal']  = array_values($this->data['blocks']['cart'][$day][$j]['options']['heatseal']);	
						
						$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['cart'][$day][$j]['utlevering'];
						$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['name'] = $this->data['blocks']['cart'][$day][$j]['name'];
						$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['cart'][$day][$j]['price'];
						$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['cart'][$day][$j]['options']['description'];
						$this->data['blocks'][$cart_type][$k]['rowid'] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						$this->data['blocks'][$cart_type][$k]['qty'] = $this->data['blocks']['cart'][$day][$j]['qty'];
						$this->data['blocks'][$cart_type][$k]['gtype'] = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$this->data['blocks'][$cart_type][$k]['subtotal'] = $this->data['blocks']['cart'][$day][$j]['subtotal'];
						
						$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['cart'][$day][$j]['options']['in_house'];
						$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['cart'][$day][$j]['options']['complain'];						
						
						
						$this->data['blocks'][$cart_type][$k]['subtotal_currency'] = formatcurrency($this->data['blocks']['cart'][$day][$j]['subtotal']);
	
						$product_id = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['i'] = $k;
						$path_parts = pathinfo($this->data['blocks']['cart'][$day][$j]['options']['image']);
						$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
						$this->data['debug'][] = $this->products_model->debug_data;
						$total += $this->data['blocks']['cart'][$day][$j]['subtotal'];
						$k++;
				}//for 	 
				
			  }//for
			  
			  
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			  
			
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
			  if(count($vask_arr) >0 ){
				 $vaskstatus=1;
			  	for($z=0;$z<count($vask_arr);$z++){ 
					$date = $vask_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['vask']);
							}
						}
						
						$str_vask.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
                    $hs_vask_arr = $vask_arr[$z]['heatseal'];
					
					$hs_vask ='';
					if(count($hs_vask_arr) > 0)
					{
						$hs_vask .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_vask_arr as $heatseal_vask)
						{
								if($heatseal_vask['barcode'] != '')
								{
									$hs_vask .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_vask['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_vask['barcode'].'</div>';
								}
						}
						
						$hs_vask .= '</div>';
					}
					
					
					
			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
							/*$str_vask.=' <div class="count">'.$vask_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div></div>';*/
						   
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
								
							$str_vask.='               
						   </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   <div class="pull-left col-md-3 no-padd text-right"><p><span>kr '.$vask_arr[$z]['subtotal_currency'].'</span></p>
						   
						</div>							   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$vask_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['rowid'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr> '.$hs_vask.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  }
			 
			 //for  rens 
			 $prev_date ='';
			 $date = '';
			 $str_rens ='';
			 if(count($rens_arr) >0 ){
			  $rensstatus=1;
			  for($z=0;$z<count($rens_arr);$z++){
					$date = $rens_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['rens']);
							}
						}
						
						$str_rens.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($rens_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($rens_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
                    $hs_rens_arr = $rens_arr[$z]['heatseal'];
					
					$hs_rens ='';
					if(count($hs_rens_arr) > 0)
					{
						$hs_rens .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_rens_arr as $heatseal_rens)
						{
								if($heatseal_rens['barcode'] != '')
								{
									$hs_rens .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_rens['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_rens['barcode'].'</div>';
								}
						}
						
						$hs_rens .= '</div>';
					}
										
			  		$str_rens.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						/*$str_rens.='<div class="count">'.$rens_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div>';*/
							
							$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							               
						  $str_rens.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$rens_arr[$z]['subtotal_currency'].'</span></div></p>
						   
						   <div class="pull-left col-md-2 no-padd text-center">
						 <a class="editprod" id="editpro_'.$rens_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['rowid'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr> '.$hs_rens.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			 }
			  
			}//if
			else{
				 $str.='<div class="col-md-12"> 
                  <p class="black-text  text-center">'.$this->data['lang']['lang_cart_empty'].'</p>
                 </div>';
			}
			
			if($rensstatus == '1')
			{
				$str_rens .='<style>#rens_div{display:block;}</style>';
			}
			else
			{
				$str_rens .='<style>#rens_div{display:none;}</style>';
			}
			
			if($vaskstatus == '1')
			{
				$str_vask .='<style>#vask_div{display:block;}</style>';
			}
			else
			{
				$str_vask .='<style>#vask_div{display:none;}</style>';
			}
			
			if($catid == 13)
			{	
				$sdata=array();
				$sdata['salg_status']=1;
				$this->session->set_userdata($sdata);
				
				$s_data['salg']=$this->session->userdata['salg'];
				$s_data['salg'][$id]=$id;
				$this->session->set_userdata($s_data);
					
			}
			
			
			
			$sdata=array();
			if($this->session->userdata['salg'])
			{
				$sdata['salg']=$this->session->userdata['salg'];
			}
			else
			{
				$sdata['salg']=array();
			}
			
			$salg_status=0;
			
			if(count($sdata['salg']) > 0)
			{
				
				$salg_status=1;
			}
			else
			{
				$sdata=array();
				$sdata['salg_status']=0;
				$this->session->set_userdata($sdata);
			}
			
			
			//echo '<pre>';print_r();exit;
		
			
			$count = $this->cart->total_items();
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$total-$delsumamt;	
			$result = array("order_list_rens"=>$str_rens,"order_list_vask"=>$str_vask,"delsum"=>$total,"mva"=>$delsumvat,"count"=>$count,"delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat),'lastproduct'=>$this->session->userdata['lastproduct'],"salg_status"=>$salg_status);
			
			
			echo json_encode($result);exit;
			
		
		
	}	
	
	
    /**
     * edit product qty in cart via modal popup
     *
     */
    function __editCart()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //get product id
        $row_id = $this->uri->segment(4);
		$next=true;
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'cart.modal.html';
        if ($next) {
			$data = $this->cart->contents();
			
            //load from database
            $this->data['row'] = $data[$row_id];
			
            //visibility - show table or show nothing found
            if (!empty($this->data['row'])) {
                $this->data['visible']['wi_product'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
            }
        }
    }
	
	
	/*remove an item to cart  */
	function __updateCart(){
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
			//print_r($this->cart->contents());
			
			$data = array_reverse($this->cart->contents());
			$rowid = $this->input->post('rowid');
			$heatseal=$data[$rowid]['options']['heatseal'];
		
			
			$id = $this->input->post('pid');
			$price = $this->input->post('price');
			$qty = $this->input->post('qty');
			$complain = $this->input->post('complain');
			$in_house = $this->input->post('in_house');
			$spl_instruction = $this->input->post('desp');
			$price = ($complain == '1')  ?   0 : $price;
			if($id == 143)
			{
				$price=$qty;
				$qty=1;
				
				if($complain == '1')
				{
					$price=0;
					$qty=1;
				}
				else
				{
					if(intval($price) == 0)
					{	
						$qty=0;
					}
				}
				
				
				
			}
		
			
			if(intval($qty) > 0){
				$data[$rowid] =  array(
						'rowid'      => $rowid,
						'qty'    => $qty,
						'price'   => $price,
						'options' => array('description'=>$spl_instruction,'complain'=>$complain,'in_house'=>$in_house)
						
				);
			
				
		
				/*update removed item quantity to 0*/
				$result = $this->cart->update($data);	
		
			}
			else
			{
				if(intval($qty) == 0)
				{
					$data[$rowid] =  array(
						'rowid'      => $rowid,
						'qty'    => 0,
						'price'   => 0,
						'options' => array('description'=>$spl_instruction,'complain'=>$complain,'in_house'=>$in_house)
					);
					
					$sdata['salg']=$this->session->userdata['salg'];
					if(isset($sdata['salg'][$id]))
					{
						unset($sdata['salg'][$id]);
					}
					
				    $this->session->set_userdata($sdata);
				
					/*update removed item quantity to 0*/
					$result = $this->cart->update($data);
		$s_data=array();		
		$s_data['producttypeskip']=$this->session->userdata['producttypeskip'];
		$s_data['producttypeskip'][$id]='';
		$this->session->set_userdata($s_data);
		
		$s_data=array();
		$s_data['producttype_cart']=$this->session->userdata['producttype_cart'];
		$s_data['producttype_cart'][$id]='';
		$this->session->set_userdata($s_data);
		
		$s_data=array();
		$s_data['heatsealcart']=$this->session->userdata['heatsealcart'];
		if(isset($s_data['heatsealcart'][$heatseal]))
		{
			unset($s_data['heatsealcart'][$heatseal]);
		}
		$this->session->set_userdata($s_data);
		
		}
				
			}
			
			//echo '<pre>';print_r($this->cart->contents());exit;
			
			
				/*$response = array("error"=>$result);
				echo json_encode($response);exit;*/
				
			// reverse in descending order
			$data = array_reverse($this->cart->contents());
			

		 
			 $this->data['reg_blocks'][] = 'cart';
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			 $j=0;
			 $temp = 0;
			 foreach ($data as $items){
				 //print_r($items);
				 $date = $items['utlevering'];
					//echo  $temp ."=". $date;
				 if( (strtotime($temp) != strtotime($date)) || ($j==0)){
				 	$this->data['blocks']['cart'][$date][] = $items;
				 }
				 else{
					 $this->data['blocks']['cart'][$temp][] = $items;
				 }
				 $temp  =  $date;
				 $j++;
			 }
			 
			// print_r($this->data['blocks']['cart']);

			 $total =0;
			 //print_r($this->data['blocks']['cart']); 
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			if (count($this->data['blocks']['cart']) > 0) {
			  $k = 0;
			  $str ='';
			  $row = array();
			  foreach($this->data['blocks']['cart'] as $key => $value) {
				$day = $key;
				for($j=0;$j<count($this->data['blocks']['cart'][$day]);$j++){ 
					
						$gtype = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
						
						$row[$day][$cart_type][$k] = $this->data['blocks']['cart'][$day][$j]['rowid'];


						$this->data['blocks'][$cart_type][$k]['heatseal']  = array_values($this->data['blocks']['cart'][$day][$j]['options']['heatseal']);	

						
						$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['cart'][$day][$j]['utlevering'];
						$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['name'] = $this->data['blocks']['cart'][$day][$j]['name'];
						$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['cart'][$day][$j]['price'];
						$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['cart'][$day][$j]['options']['description'];
						$this->data['blocks'][$cart_type][$k]['rowid'] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						$this->data['blocks'][$cart_type][$k]['qty'] = $this->data['blocks']['cart'][$day][$j]['qty'];
						$this->data['blocks'][$cart_type][$k]['gtype'] = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$this->data['blocks'][$cart_type][$k]['subtotal'] = $this->data['blocks']['cart'][$day][$j]['subtotal'];
						
						$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['cart'][$day][$j]['options']['in_house'];
						$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['cart'][$day][$j]['options']['complain'];						
						
						
						
						$this->data['blocks'][$cart_type][$k]['subtotal_currency'] = formatcurrency($this->data['blocks']['cart'][$day][$j]['subtotal']);
	
						$product_id = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['i'] = $k;
						$path_parts = pathinfo($this->data['blocks']['cart'][$day][$j]['options']['image']);
						$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
						$this->data['debug'][] = $this->products_model->debug_data;
						$total += $this->data['blocks']['cart'][$day][$j]['subtotal'];
						$k++;
				}//for 	 
				
			  }//for
			  
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			  
			 // print_r($rens_arr);
			  
			
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
			  if(count($vask_arr) >0 ){
				 $vaskstatus=1;
			  	for($z=0;$z<count($vask_arr);$z++){ 
					$date = $vask_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['vask']);
							}
						}
						
						$str_vask.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
                    $hs_vask_arr = $vask_arr[$z]['heatseal'];
					
					$hs_vask ='';
					if(count($hs_vask_arr) > 0)
					{
						$hs_vask .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_vask_arr as $heatseal_vask)
						{
								if($heatseal_vask['barcode'] != '')
								{
									$hs_vask .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_vask['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_vask['barcode'].'</div>';
								}
						}
						
						$hs_vask .= '</div>';
					}
					
					
			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
							 /*$str_vask.='<div class="count">'.$vask_arr[$z]['qty'].'</div>
							 
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div>
							</div>';*/
							
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
							               
						  $str_vask.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$vask_arr[$z]['subtotal_currency'].'</span></p>
						   </div> 
						   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$vask_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['rowid'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr>'.$hs_vask.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  }
			 
			 //for  rens 
			 $prev_date ='';
			 $date = '';
			 $str_rens ='';
			 if(count($rens_arr) >0 ){
			  $rensstatus=1;
			  for($z=0;$z<count($rens_arr);$z++){
					$date = $rens_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['rens']);
							}
						}
						
						$str_rens.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($rens_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($rens_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
				
                    $hs_rens_arr = $rens_arr[$z]['heatseal'];
					
					$hs_rens ='';
					if(count($hs_rens_arr) > 0)
					{
						$hs_rens .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_rens_arr as $heatseal_rens)
						{
								if($heatseal_rens['barcode'] != '')
								{
									$hs_rens .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_rens['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_rens['barcode'].'</div>';
								}
						}
						
						$hs_rens .= '</div>';
					}					
					
			  		$str_rens.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
							/* $str_rens.='<div class="count">'.$rens_arr[$z]['qty'].'</div>
							 
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div> ';*/
							
							$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							              
						   $str_rens.='</div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$rens_arr[$z]['subtotal_currency'].'</span></p>
							</div>						   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$rens_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['rowid'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr>'.$hs_rens.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			 }
			  
			}//if
			else{
				 $str.='<div class="col-md-12"> 
                  <p class="black-text  text-center">'.$this->data['lang']['lang_cart_empty'].'</p>
                 </div>';
			}
			
		
			
			if($rensstatus == '1')
			{
				$str_rens.='<style>#rens_div{display:block;}</style>';
			}
			else
			{
				$str_rens .='<style>#rens_div{display:none;}</style>';
			}
			
			if($vaskstatus == '1')
			{
				$str_vask .='<style>#vask_div{display:block;}</style>';
			}
			else
			{
				$str_vask  .='<style>#vask_div{display:none;}</style>';
			}
			
			$sdata=array();
			if($this->session->userdata['salg'])
			{
				$sdata['salg']=$this->session->userdata['salg'];
			}
			else
			{
				$sdata['salg']=array();
			}
			
			
			
			$salg_status=0;
			if(count($sdata['salg']) > 0)
			{
				$salg_status=1;
			}
			else
			{
				$sdata=array();
				$sdata['salg_status']=0;
				$this->session->set_userdata($sdata);
			}
			
			$count = $this->cart->total_items();
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$total-$delsumamt;	
			$result = array("order_list_rens"=>$str_rens,"order_list_vask"=>$str_vask,"delsum"=>$total,"mva"=>$delsumvat,"count"=>$count,"delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat),'salg_status'=>$salg_status);
			echo json_encode($result);exit;
				
		
	}
	
	
	
	/*update utlevering date for an orderline  */
	function __updateUtlevering(){
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
			$rowid = explode('@',$this->input->post('rowid'));
			$utlevering = $this->input->post('utlevering');
			
			// print_r($this->cart->contents());
			
			
			/*$rowid = '3b302e00abe786dbcb1d53d9a1ce5389';
			$rowid = explode('@',$rowid );
			$utlevering = '6.2.2017';*/
			
			
			if($utlevering!=''){
				
				for($i = 0; $i < count($rowid); $i++) {
					$data[] =  array(
							'rowid'      => $rowid[$i],
							'utlevering' => $utlevering
					);
				}
				
				//print_r($data);
			
				/*update removed item quantity to 0*/
				$result = $this->cart->updateoption($data);	
				
			//print_r($this->cart->contents());
				
		 
			 $this->data['reg_blocks'][] = 'cart';
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			
			// reverse in descending order
			$data = array_reverse($this->cart->contents());
			 
			//print_r($data);
			 
			 
			 $j=0;
			 $temp = 0;
			 foreach ($data as $items){
				 //print_r($items);
				 $date = $items['utlevering'];
					//echo  $temp ."=". $date;
				 if( (strtotime($temp) != strtotime($date)) || ($j==0)){
				 	$this->data['blocks']['cart'][$date][] = $items;
				 }
				 else{
					 $this->data['blocks']['cart'][$temp][] = $items;
				 }
				 $temp  =  $date;
				 $j++;
			 }
			 
			 //print_r($this->data['blocks']['cart']);

			 $total =0;
			 //print_r($this->data['blocks']['cart']); 
		     $rens='<div id="ajax-loader-rens"><img src="img/ajax-loader.gif"/></div>';
		     $vask='<div id="ajax-loader-vask"><img src="img/ajax-loader.gif"/></div>';
			 $rensstatus=0;
			 $vaskstatus=0;
			if (count($this->data['blocks']['cart']) > 0) {
			  $k = 0;
			  $str ='';
			  $row = array();
			  foreach($this->data['blocks']['cart'] as $key => $value) {
				$day = $key;
				for($j=0;$j<count($this->data['blocks']['cart'][$day]);$j++){ 
					
						$gtype = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
						
						$row[$day][$cart_type][$k] = $this->data['blocks']['cart'][$day][$j]['rowid'];

						$this->data['blocks'][$cart_type][$k]['heatseal']  = array_values($this->data['blocks']['cart'][$day][$j]['options']['heatseal']);	
						
						$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['cart'][$day][$j]['utlevering'];
						$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['name'] = $this->data['blocks']['cart'][$day][$j]['name'];
						$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['cart'][$day][$j]['price'];
						$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['cart'][$day][$j]['options']['description'];
						$this->data['blocks'][$cart_type][$k]['rowid'] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						$this->data['blocks'][$cart_type][$k]['qty'] = $this->data['blocks']['cart'][$day][$j]['qty'];
						$this->data['blocks'][$cart_type][$k]['gtype'] = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$this->data['blocks'][$cart_type][$k]['subtotal'] = $this->data['blocks']['cart'][$day][$j]['subtotal'];
						
						$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['cart'][$day][$j]['options']['in_house'];
						$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['cart'][$day][$j]['options']['complain'];						
						
						
						$this->data['blocks'][$cart_type][$k]['subtotal_currency'] = formatcurrency($this->data['blocks']['cart'][$day][$j]['subtotal']);
	
						$product_id = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['i'] = $k;
						$path_parts = pathinfo($this->data['blocks']['cart'][$day][$j]['options']['image']);
						$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
						$this->data['debug'][] = $this->products_model->debug_data;
						$total += $this->data['blocks']['cart'][$day][$j]['subtotal'];
						$k++;
				}//for 	 
				
			  }//for
			  
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			  
			 
			 //print_r($rens_arr);
			
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
			  if(count($vask_arr) >0 ){
				 $vaskstatus=1;
			  	for($z=0;$z<count($vask_arr);$z++){ 
					$date = $vask_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['vask']);
							}
						}
						
						$str_vask.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
                    $hs_vask_arr = $vask_arr[$z]['heatseal'];
					
					$hs_vask ='';
					if(count($hs_vask_arr) > 0)
					{
						$hs_vask .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_vask_arr as $heatseal_vask)
						{
								if($heatseal_vask['barcode'] != '')
								{
									$hs_vask .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_vask['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_vask['barcode'].'</div>';
								}
						}
						
						$hs_vask .= '</div>';
					}
					
					
			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
							/*$str_vask.=' <div class="count">'.$vask_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div>
							</div>'; */
							
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
							              
						  $str_vask.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-center">
						   <p><span>kr '.$vask_arr[$z]['subtotal_currency'].'</span></p>
						  </div> 
						   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$vask_arr[$z]['id'].'"  onclick="editprod();" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['rowid'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr>'.$hs_vask.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  }
			 
			 //for  rens 
			 $prev_date ='';
			 $date = '';
			 $str_rens ='';
			 if(count($rens_arr) >0 ){
			  $rensstatus=1;
			  for($z=0;$z<count($rens_arr);$z++){
					$date = $rens_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['rens']);
							}
						}
						
						$str_rens.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($rens_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($rens_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
                    $hs_rens_arr = $rens_arr[$z]['heatseal'];
					
					$hs_rens ='';
					if(count($hs_rens_arr) > 0)
					{
						$hs_rens .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_rens_arr as $heatseal_rens)
						{
								if($heatseal_rens['barcode'] != '')
								{
									$hs_rens .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_rens['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_rens['barcode'].'</div>';
								}
						}
						
						$hs_rens .= '</div>';
					}					
					
			  		$str_rens.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						/*$str_rens.='	 <div class="count">'.$rens_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div>';*/
							
							$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							             
						   $str_rens.='</div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$rens_arr[$z]['subtotal_currency'].'</span></p>
						   </div>
						   
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$rens_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['rowid'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr> '.$hs_rens.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  }
			  
			}//if
			else{
				 $str.'<div class="col-md-12"> 
                  <p class="black-text  text-center">'.$this->data['lang']['lang_cart_empty'].'</p>
                 </div>';
			}
			
		
			
			if($rensstatus == '1')
			{
				$str_rens.='<style>#rens_div{display:block;}</style>';
			}
			else
			{
				$str_rens.='<style>#rens_div{display:none;}</style>';
			}
			
			if($vaskstatus == '1')
			{
				$str_vask.='<style>#vask_div{display:block;}</style>';
			}
			else
			{
				$str_vask.='<style>#vask_div{display:none;}</style>';
			}
			
			$count = $this->cart->total_items();
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat=$total-$delsumamt;	
			$result = array("order_list_rens"=>$str_rens,"order_list_vask"=>$str_vask,"delsum"=>$total,"mva"=>$delsumvat,"count"=>$count,"delsum_currency"=>formatcurrency($total),"mva_currency"=>formatcurrency($delsumvat));
			echo json_encode($result);exit;
				
		}
		
	}
	
	
	
    /**
     * add product to cart via modal popup
     *
     */
    function __addCart()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //get product id
        $product_id = $this->uri->segment(4);
		$next=true;
        //template file
        $this->data['template_file'] = PATHS_ADMIN_THEME . 'product.modal.html';
        if ($next) {
            //load from database
            $this->data['row'] = $this->products_model->getProduct($product_id,'main');
			
            //visibility - show table or show nothing found
            if (!empty($this->data['row'])) {
                $this->data['visible']['wi_product'] = 1;
            } else {
                $this->notifications('wi_notification', $this->data['lang']['lang_no_results_found']);
            }
        }
    }
	
	
	
	
	function __displayCart(){
		
		 //profiling
		 $this->data['controller_profiling'][] = __function__;
		 $this->data['lists']['total_currency']  = 0;
		 $this->data['lists']['mva_currency'] = 0;
		 $this->data['lists']['total'] = 0;
		 $this->data['lists']['mva'] = 0;
		 
		 $cus_id = $this->session->userdata['pos_customer_id'];
		 
		 $customer=$this->session->userdata['customer']['id'];
		 if($this->session->userdata['skipsaldo'][$customer] == '1')
		 {
			$this->data['visible']['saldo_status'] = 1;
			$this->data['vars']['saldo_status'] = 1;
		 }
		 else
		 {
	
			$saldo_status = $this->payments_model->getSaldostatus($cus_id);
			
			$this->data['visible']['saldo_status'] = $saldo_status;
			$this->data['vars']['saldo_status'] = $saldo_status;
		 }
		
		 
		 
		 
         $this->data['vars']['category'] = is_numeric($this->uri->segment(4)) ?  $this->uri->segment(4): '';
		 
		 
		 //print_r($this->cart->contents());

		 if (count($this->cart->contents()) > 0) {

		 
			$this->data['visible']['wi_products_added_in_cart'] = 1;
			
			//print_r($this->cart->contents());
			
			// reverse in descending order
			$data = array_reverse($this->cart->contents());
		 
			 
			 $this->data['reg_blocks'][] = 'cart';
			 $j=0;
			 $temp = 0;
			 foreach ($data as $items){
				 //print_r($items);
				 $date = $items['utlevering'];
					//echo  $temp ."=". $date;
				 if( (strtotime($temp) != strtotime($date)) || ($j==0)){
				 	$this->data['blocks']['cart'][$date][] = $items;
				 }
				 else{
					 $this->data['blocks']['cart'][$temp][] = $items;
				 }
				 $temp  =  $date;
				 $j++;
			 }
			 
			//echo '<pre>';print_r($this->data['blocks']['cart']);exit;
			//echo '<pre>';print_r($this->data['blocks']['cart']);exit;
			 
			 $this->data['reg_blocks'][] = 'rens';
			 $this->data['reg_blocks'][] = 'vask';
			 
			 
			//print_r($this->data['blocks']['cart']); 
			 $total = 0 ;
			 if (count($this->data['blocks']['cart']) > 0) {
				$k = 0;
				$str ='';
				$row = array();
				
				foreach($this->data['blocks']['cart'] as $key => $value) {
					$day = $key;
					for($j=0;$j<count($this->data['blocks']['cart'][$day]);$j++){ 
						
						$gtype = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$cart_type = ($gtype=='rens') ? 'rens' : 'vask';
						
						$row[$day][$cart_type][$k] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						
						$this->data['blocks'][$cart_type][$k]['heatseal']  = array_values($this->data['blocks']['cart'][$day][$j]['options']['heatseal']);						
						
						$this->data['blocks'][$cart_type][$k]['utlevering'] = $this->data['blocks']['cart'][$day][$j]['utlevering'];
						$this->data['blocks'][$cart_type][$k]['id'] = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['name'] = $this->data['blocks']['cart'][$day][$j]['name'];
						$this->data['blocks'][$cart_type][$k]['price'] = $this->data['blocks']['cart'][$day][$j]['price'];
						$this->data['blocks'][$cart_type][$k]['description'] = $this->data['blocks']['cart'][$day][$j]['options']['description'];
						$this->data['blocks'][$cart_type][$k]['rowid'] = $this->data['blocks']['cart'][$day][$j]['rowid'];
						$this->data['blocks'][$cart_type][$k]['qty'] = $this->data['blocks']['cart'][$day][$j]['qty'];
						$this->data['blocks'][$cart_type][$k]['gtype'] = $this->data['blocks']['cart'][$day][$j]['options']['gtype'];
						$this->data['blocks'][$cart_type][$k]['subtotal'] = $this->data['blocks']['cart'][$day][$j]['subtotal'];
						
						$this->data['blocks'][$cart_type][$k]['subtotal_currency'] = formatcurrency($this->data['blocks']['cart'][$day][$j]['subtotal']);
						
						$this->data['blocks'][$cart_type][$k]['in_house'] = $this->data['blocks']['cart'][$day][$j]['options']['in_house'];
						$this->data['blocks'][$cart_type][$k]['complain'] = $this->data['blocks']['cart'][$day][$j]['options']['complain'];						
						
	
						$product_id = $this->data['blocks']['cart'][$day][$j]['id'];
						$this->data['blocks'][$cart_type][$k]['i'] = $k;
						$path_parts = pathinfo($this->data['blocks']['cart'][$day][$j]['options']['image']);
						$this->data['blocks'][$cart_type][$k]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
						$this->data['debug'][] = $this->products_model->debug_data;
						$total += $this->data['blocks']['cart'][$day][$j]['subtotal'];
						$k++;
						
					}//for
			  }//for
			  
			  
			  $vask_arr = array_values($this->data['blocks']['vask']);
			  $rens_arr = array_values($this->data['blocks']['rens']);
			  
			
			  //for vask	
			 $prev_date ='';
			 $date = '';
			 $str_vask ='';
			  for($z=0;$z<count($vask_arr);$z++){ 
					$date = $vask_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['vask']);
							}
						}
						
						$str_vask.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($vask_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($vask_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
					$hs_vask_arr = $vask_arr[$z]['heatseal'];
					
					$hs_vask ='';
					if(count($hs_vask_arr) > 0)
					{
						$hs_vask .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_vask_arr as $heatseal_vask)
						{
								if($heatseal_vask['barcode'] != '')
								{
									$hs_vask .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_vask['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_vask['barcode'].'</div>';
								}
						}
						
						$hs_vask .= '</div>';
					}
					
					
			  		$str_vask.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
						   
							/*$str_vask.=' <div class="count">'.$vask_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$vask_arr[$z]['thumb'].'\');" class="img"></div>
							</div>';*/
							
							$str_vask.='<div class="round1"><div class="img1"><p><span>'.$vask_arr[$z]['qty'].'</span></p></div></div>';
							               
						  $str_vask.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$vask_arr[$z]['name'].'</span>
							'.$vask_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   
						   <div class="pull-left col-md-3 no-padd text-right">
						   <p><span>kr '.$vask_arr[$z]['subtotal_currency'].'</span></p>
						   </div>
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$vask_arr[$z]['id'].'"  onclick="editprod();" data-toggle="modal" data-title="'.$vask_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$vask_arr[$z]['id'].'@'.$vask_arr[$z]['name'].'@'.$vask_arr[$z]['price'].'@'.$vask_arr[$z]['path'].'@'.$vask_arr[$z]['description'].'@'.$vask_arr[$z]['rowid'].'@'.$vask_arr[$z]['qty'].'@'.$vask_arr[$z]['gtype'].'@'.$vask_arr[$z]['complain'].'@'.$vask_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr> '.$hs_vask.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			   
			 
			 //for  rens 
			 $prev_date ='';
			 $date = '';
			 $str_rens ='';
			  for($z=0;$z<count($rens_arr);$z++){
				   
					$date = $rens_arr[$z]['utlevering'];
					
					if(($z==0) || ($date!=$prev_date)){
						$rowid ='';
						foreach($row as $key2 => $value2) {
							$day = $key2;
							if($day==$date){
								$rowid	= implode('@',$row[$key2]['rens']);
							}
						}
						
						$str_rens.='<div class="row">
						<div class="pull-left col-md-3 no-padd text-center">
						</div> 
						<div class="pull-right col-md-6 no-padd  customer-info">
						   <div class="input-group" >
						   <div class="pull-left saldo">Utlevering: </div> 
						   <input type="text" id="'.$rowid.'" class="form-control datepicker" value="'.$date.'">
							<div class="input-group-addon">
								<span class="glyphicon glyphicon-th"></span>
							</div>
						</div>
					   </div>
					   </div>';
					   $rowid = '';
					}
					
					$reklama = ($rens_arr[$z]['complain'] =='1' ) ? '<span class="orange-text">Reklamasjon</span>' : '';
					$renses =  ($rens_arr[$z]['in_house'] =='1' ) ? '<span class="purple-text">Renses på huset</span>' : '';
					
					
                    $hs_rens_arr = $rens_arr[$z]['heatseal'];
					
					$hs_rens ='';
					if(count($hs_rens_arr) > 0)
					{
						$hs_rens .= '<div class="row mt-sm" style="color: #0678be;margin-bottom:10px">';
						foreach($hs_rens_arr as $heatseal_rens)
						{
								if($heatseal_rens['barcode'] != '')
								{
									$hs_rens .= '<div class="pull-left col-md-2 bold no-padd"></div><div class="pull-left no-padd col-md-6 bold">'.$heatseal_rens['name'].'</div><div class="pull-left col-md-4 no-padd bold">#HS '.$heatseal_rens['barcode'].'</div>';
								}
						}
						
						$hs_rens .= '</div>';
					}
					
					
					
			  		$str_rens.='
						  <div class="orderline mt-sm">
						   <div class="pull-left col-md-2 no-padd text-center">';
						   
							/*$str_rens.=' <div class="count">'.$rens_arr[$z]['qty'].'</div>
							<div class="round">
								<div style="background-image: url(\''.$this->data['vars']['site_url'].'\images/'.$rens_arr[$z]['thumb'].'\');" class="img"></div>
							</div>'; */
							
							$str_rens.='<div class="round1"><div class="img1"><p><span>'.$rens_arr[$z]['qty'].'</span></p></div></div>';
							              
						  $str_rens.=' </div>
						   <div class="pull-left col-md-5 no-padd">
						   <p><span>'.$rens_arr[$z]['name'].'</span>
							'.$rens_arr[$z]['description'].$reklama.$renses.'</p>
						   </div>
						   	<div class="pull-left col-md-3 no-padd text-right"><p><span>kr '.$rens_arr[$z]['subtotal_currency'].'</span></p>
							</div>
						   <div class="pull-left col-md-2 no-padd text-center">
						   
						 <a class="editprod" id="editpro_'.$rens_arr[$z]['id'].'" onclick="editprod();" data-toggle="modal" data-title="'.$rens_arr[$z]['name'].'"  href="#editProductModal" data-stuff="'.$rens_arr[$z]['id'].'@'.$rens_arr[$z]['name'].'@'.$rens_arr[$z]['price'].'@'.$rens_arr[$z]['path'].'@'.$rens_arr[$z]['description'].'@'.$rens_arr[$z]['rowid'].'@'.$rens_arr[$z]['qty'].'@'.$rens_arr[$z]['gtype'].'@'.$rens_arr[$z]['complain'].'@'.$rens_arr[$z]['in_house'].'"><img src="img/plus-minus.png" alt="Edit" title=""/></a>	
						   </div>
						   <div class="clearfix"></div>
						   <hr>'.$hs_rens.'
						 </div> 
					  ';
					$prev_date =  $date;  
				  }
			  
			  $this->data['lists']['total'] = $total;
			  $this->data['lists']['total_currency'] =formatcurrency($total);
			  $this->data['lists']['vask'] = $str_vask;
			  $this->data['lists']['rens'] = $str_rens;
			  
			  $delsumamt=$total/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat=$total-$delsumamt;	
			  $this->data['lists']['mva']=$delsumvat;
			   $this->data['lists']['mva_currency']=formatcurrency($delsumvat);
			}//if
			$this->data['visible']['wi_products_added_in_cart'] = 1;
			
			if(isset($this->data['blocks']['rens']))
			{
				$this->data['visible']['wi_products_added_in_cart_rens'] = 1;
			}
			else
			{
				$this->data['visible']['wi_no_products_added_in_cart_rens'] = 1;
			}
			
			if(isset($this->data['blocks']['vask']))
			{
				$this->data['visible']['wi_products_added_in_cart_vask'] = 1;
			}
			else
			{
				$this->data['visible']['wi_no_products_added_in_cart_vask'] = 1;
			}
			
			
			
			
			
		 }
		else{
			$this->data['visible']['wi_no_products_added_in_cart'] = 1;
			$this->data['visible']['wi_no_products_added_in_cart_vask'] = 1;
			$this->data['visible']['wi_no_products_added_in_cart_rens'] = 1;
			
		}
		
		//echo '<pre>';print_r($this->data['visible']);exit;	
		
		
		$customer=$this->session->userdata['customer']['id'];
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];

			
		if(intval($pendingsaldo) > 0)
		{	
			//$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$amount= formatcurrency($paidsaldo);
			
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
		}
		
		$this->data['lists']['saldo']=$amount;
		$this->data['lists']['saldocolor']='';
		if($paidsaldo < 0)
		{
			$this->data['lists']['saldocolor']='style="color:red;"';
		}
		
		
		/*adding cart data to session variable*/	
		$newdata = array('cartdata'  => $this->data['blocks']['cart']);
		$this->session->set_userdata($newdata);
		//print_r($this->session);
		
	}
    /**
     * loads the categories
     */
    function __page()
    {
	
		//echo '<pre>';print_r($this->cart->contents());
		//print_r($this->session);
		
		$this->__getCategories();
		$this->__displayCart();
		
		
    }
	
	
    /**
     * loads the products in a category
     */
    function __listProducts()
    {
		//$this->cart->destroy();
		//echo '<pre>';print_r($this->cart->contents());exit;
		$this->__displayCart(); 
		$lastid = $this->__getProducts();
		
    }	
	
	/*list categories*/
	function __getCategories(){
		
		$tilbud = ($this->session->userdata['logged_in']!='shop') ?  12 : 0 ;
		$pop_cat = ($this->session->userdata['logged_in']!='shop') ?  13 : 1 ;
		
		
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //get results
		$this->data['reg_blocks'][] = 'category';
		$this->data['blocks']['category'] = $this->products_model->getCategories();
		
			if (count($this->data['blocks']['category']) > 0) {
				foreach($this->data['blocks']['category']  as $ckey=>$catitems)
				{
					if($catitems['id'] == $tilbud )
					{
						$tilbudProducts=$this->products_model->getTilbudProducts();
						if(count($tilbudProducts) > 0)
						{
							$ii=0;
							foreach($tilbudProducts as $tilbuditems)
							{
								if($tilbuditems['id'] != '66')
								{
									$ii++;
								}
								
							}
							if($ii > 0)
							{
								$this->data['blocks']['category'][$ckey]['count']=$ii;
							}
							else
							{
								unset($this->data['blocks']['category'][$ckey]);
							}
							
							
						}
					}
				}
			}
			 
			if(count($this->data['blocks']['category']) > 0)
			{
				$newcat=$this->data['blocks']['category'];
				$i=0;
				foreach($newcat as $citems)
				{
					$this->data['blocks']['category'][$i]=$citems;
					$i++;
				}
			}
		
	//echo '<pre>';print_r($this->data['blocks']['category']);exit;
		$this->data['debug'][] = $this->products_model->debug_data;	
		 $str ='';
		
		 //$str .='<a class="catclass" rel="0" id="1" href="#" style="display:none">Populær</a>';
		 
		//get poupular count 
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		$this->data['reg_blocks'][] = 'popular';
		$this->data['blocks']['popular'] = $this->products_model->getPopularProduct($cus_id);
		$this->data['debug'][] = $this->products_model->debug_data;
		
		$popular_count = count($this->data['blocks']['popular']);
		$boolean = false;
		 
		 
		if (count($this->data['blocks']['category']) > 0) {
			
			$col_count = 0;
			for($i=0;$i<count($this->data['blocks']['category']);$i++){
			 
			 $count =	($this->data['blocks']['category'][$i]['id']==$pop_cat) ?  $popular_count : $this->data['blocks']['category'][$i]['count'];
			 
			  $color = $this->data['blocks']['category'][$i]['bg_color'];
			  
			 // echo $count."===".$this->data['blocks']['category'][$i]['id']."=".$this->data['blocks']['category'][$i]['name']."<br>";
				
			  if( $count != ''){
				$boolean = true;
				
				$path_parts = pathinfo($this->data['blocks']['category'][$i]['path']);
				$this->data['blocks']['category'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
			
				if(($col_count%6)== 0){
					if($col_count!=0){
					 $str .=' <div class="clearfix"></div>
					</div>';
					}
					
				 $str .='<div class="product-block">';
				}
				 
				 //$first	= ($i==0) ? ' first' : '';
				
				 
				  $str .='<div class="col-md-2 no-padd text-center  '.$first.'"> 
					<div class="pblock" style="background:#'.$color.'"><a class="catclass" rel="0"  id="'.$this->data['blocks']['category'][$i]['id'].'" href="#">'.$this->data['blocks']['category'][$i]['name'].'</a></div>
				  </div>';
				  
				  
				
				$col_count ++;
				
				if($i == (count($this->data['blocks']['category'])-1)){
					 $str .=' <div class="clearfix"></div>
					</div>';
				}
			} //if
			
		  }//for
		}
		if(!$boolean){
			$str.='<div class="col-md-12 text-center mt-lg"><h2>No Categories Found</h2></div>';
		}
		
		$this->data['lists']['categories'] =  $str;
			
	}
	
	
	/*list  product items*/
	function __getProducts(){
		
        //get category id
        $id = is_numeric($this->uri->segment(4)) ?  $this->uri->segment(4): '';
		$tilbud = ($this->session->userdata['logged_in']!='shop') ?  12 : 0 ;
		$pop_cat = ($this->session->userdata['logged_in']!='shop') ?  13 : 1 ;


        //profiling
        $this->data['controller_profiling'][] = __function__;
        //get results
		$this->data['reg_blocks'][] = 'products';
		
		
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		
		if($id == $tilbud)
		{
			
			$tilbudProducts=$this->products_model->getTilbudProducts('','main','',$this->session->userdata['logged_in'],$subscription);
			
			if(count($tilbudProducts) > 0)
			{
				$i=0;
				foreach($tilbudProducts as $tilbuditems)
				{
					if($tilbuditems['id'] != '66')
					{
						$this->data['blocks']['products'][$i]=$tilbuditems;
					}
					
					$i++;
				}
			}
			
		}
		else if($id == $pop_cat)
		{
			
			//get results
			$this->data['reg_blocks'][] = 'products';
			$this->data['blocks']['products'] = $this->products_model->getPopularProduct($cus_id,'main',$this->session->userdata['logged_in'],$subscription);
			$this->data['debug'][] = $this->products_model->debug_data;	
			
			
		}
		else
		{
			
			$this->data['blocks']['products'] = $this->products_model->getProduct('','main',$id,$this->session->userdata['logged_in'],$subscription);
			
		}
		
		$this->data['debug'][] = $this->products_model->debug_data;	
		$str ='';
		if (count($this->data['blocks']['products']) > 0) {
			
			for($i=0;$i<count($this->data['blocks']['products']);$i++){
					
				$this->data['blocks']['products'][$i]['i'] = $i;
				
				$path_parts = pathinfo($this->data['blocks']['products'][$i]['path']);
				$this->data['blocks']['products'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
				
				if(($i%4)== 0){
					if($i!=0){
					 $str .=' <div class="clearfix"></div>
					</div>';
					}
				 $str .='<div class="product-block">';
				}
				 
				  $str .='<div class="col-md-3 no-padd text-center">';
				  
				  $str .='<div class="square">
				  
				   <a style="background-image: url('.$this->data['vars']['site_url'].'images/'.$this->data['blocks']['products'][$i]['thumb'].');" class="img productimg"
                                                href="#addProductModal" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'" class="" data-toggle="modal" data-title="'.$this->data['blocks']['products'][$i]['name'].'"></a></div>';
												
					 $str .='<p><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#addProductModal" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'" class="" data-toggle="modal" data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].' <br><span class="green-text">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></p>';										
												
				   $str .='</div>';
				  
				if($i == (count($this->data['blocks']['products'])-1)){
					 $str .=' <div class="clearfix"></div>
					</div>';
				}
				
			}//for
			
			
			$this->data['visible']['wi_products'] = 1;
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg"><h2>Ingen resultat</h2></div>';
			
			$this->data['visible']['wi_products'] = 0;
		}
		
		$this->data['lists']['products'] =  $str;
					
		return $i;
	}
	
	
	
	/*list  product items*/
	function __getProductsList(){
		
        //get category id
		$cid = $this->input->post('cid');
		$cpid = $this->input->post('cpid');
		
		/*$cid = 0;
		$cpid = 2;*/
		
		$parent = ($cpid == 0) ? 0 : (($cid == 0) ? 0 : $cid);
		$id = $cpid;
		
		
		
		$tilbud = ($this->session->userdata['logged_in']!='shop') ?  12 : 0 ;
		$pop_cat = ($this->session->userdata['logged_in']!='shop') ?  13 : 1 ;
		

		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$this->data['reg_fields'][] = 'cat';
		$this->data['fields']['cat'] = $this->products_model->getCategories($id);
		
		
		$this->data['reg_blocks'][] = 'catnames';
		$this->data['blocks']['catnames'] = $this->products_model->getParentCategories($parent);
		
		
        //get results
		$this->data['reg_blocks'][] = 'products';
		$cus_id = $this->session->userdata['pos_customer_id'];
		$subscription = $this->payments_model->getSaldostatus($cus_id);
		
		
		if($id == $tilbud )
		{
			
			$tilbudProducts=$this->products_model->getTilbudProducts('','main','',$this->session->userdata['logged_in'],$subscription);
			
			if(count($tilbudProducts) > 0)
			{
				$i=0;
				foreach($tilbudProducts as $tilbuditems)
				{
					if($tilbuditems['id'] != '66')
					{
						$this->data['blocks']['products'][$i]=$tilbuditems;
					}
					
					$i++;
				}
			}
			
		}
		else if($id == $pop_cat)
		{
			
			//get results
			$this->data['reg_blocks'][] = 'products';
			$this->data['blocks']['products'] = $this->products_model->getPopularProduct($cus_id,'main',$this->session->userdata['logged_in'],$subscription);
			$this->data['debug'][] = $this->products_model->debug_data;	
			
			
		}
		else
		{
			
			$this->data['blocks']['products'] = $this->products_model->getProduct('','main',$id,$this->session->userdata['logged_in'],$subscription,$parent);
			
		}
		
		$this->data['debug'][] = $this->products_model->debug_data;	
		
		$breadcrumb  = ($this->data['blocks']['catnames'][0]['name']!='') ? " > ". $this->data['blocks']['catnames'][0]['name'] : '';
		
		$str ='<h2 class="title">'.$this->data['fields']['cat']['name'].$breadcrumb.'</h2>';
		
		
		
		if (count($this->data['blocks']['products']) > 0) {
			
			for($i=0;$i<count($this->data['blocks']['products']);$i++){
					
				$this->data['blocks']['products'][$i]['i'] = $i;
				
				$path_parts = pathinfo($this->data['blocks']['products'][$i]['path']);
				$this->data['blocks']['products'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
				
				if(($i%4)== 0){
					if($i!=0){
					 $str .=' <div class="clearfix"></div>
					</div>';
					}
				 $str .='<div class="product-block">';
				}
				 
				  $str .='<div class="col-md-3 no-padd text-center">';
				  
			  	$color = $this->data['blocks']['products'][$i]['bg_color'];
				  
				
				 if($this->data['blocks']['products'][$i]['child'] > 0){
					 
					//$str .='<div class="pblock"><a class="catclass subcat" id="'.$this->data['blocks']['products'][$i]['cid'].'"  rel="'.$this->data['blocks']['products'][$i]['pbcid'].'" href="#">'.$this->data['blocks']['products'][$i]['name']." (<span class='child'>".$this->data['blocks']['products'][$i]['child'].'</span>)</a></div>';
					
					$str .='<div class="pblock"  style="background:#'.$color.'"><a class="catclass subcat" id="'.$this->data['blocks']['products'][$i]['cid'].'"  rel="'.$this->data['blocks']['products'][$i]['pbcid'].'" href="#">'.$this->data['blocks']['products'][$i]['name'].'  <br>(<span class="child">'.$this->data['blocks']['products'][$i]['child'].'</span>)</a></div>';
				
					
				
				 }
				 else{
												
					 /*$str .='<div class="pblock"  style="background:#'.$color.'"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'"  href="#addProductModal" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'@'.$this->data['blocks']['products'][$i]['note'].'" class="" data-toggle="modal" data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';*/
					$proqty=1;
					if($this->session->userdata['partner_branch'] == 14 || $this->session->userdata['partner_branch'] == 28 || $this->session->userdata['company_status'])
					{
						$proqty = $this->process_order_model->validateProducttype($this->data['blocks']['products'][$i]['id']);
					}
					
					//echo '<pre>';print_r($this->data['blocks']['products']);exit;
					
					if($proqty == 1)
					{
						 $str .='<div class="pblock"  style="background:#'.$color.'"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'" data-cat="'.$this->data['blocks']['products'][$i]['cid'].'" href="#" data-qty="'.$proqty.'" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'@'.$this->data['blocks']['products'][$i]['note'].'" class=""  data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';
					}
					else
					{
						 $str .='<div class="pblock"  style="background:#'.$color.'"><a class="productimg" id="pro_'.$this->data['blocks']['products'][$i]['id'].'" data-cat="'.$this->data['blocks']['products'][$i]['cid'].'"  href="#" data-qty="'.$proqty.'" data-stuff="'.$this->data['blocks']['products'][$i]['id'].'@'.$this->data['blocks']['products'][$i]['name'].'@'.$this->data['blocks']['products'][$i]['price'].'@'.$this->data['blocks']['products'][$i]['path'].'@'.$this->data['blocks']['products'][$i]['description'].'@'.$this->data['blocks']['products'][$i]['gtype'].'@'.$this->data['blocks']['products'][$i]['duration'].'@'.$this->data['blocks']['products'][$i]['note'].'" class=""  data-title="'.$this->data['blocks']['products'][$i]['name'].'">'.$this->data['blocks']['products'][$i]['name'].'<span class="price">kr '.formatcurrency($this->data['blocks']['products'][$i]['price']).'</span></a></div>';
					}					
															

					 																									
				 }
				 
				   $str .='</div>';
				  
				if($i == (count($this->data['blocks']['products'])-1)){
					 $str .=' <div class="clearfix"></div>
					</div>';
				}
				
			}//for
			
			
			$this->data['visible']['wi_products'] = 1;
		}
		else{
			$str.='<div class="col-md-12 text-center mt-lg"><h2>Ingen resultat</h2></div>';
			
			$this->data['visible']['wi_products'] = 0;
		}
		
		$str.='<script>$(document).ready(function() {
$(\'a.subcat\').click(function(e){
								e.preventDefault();	
								var cid = $(this).attr(\'rel\');
								var cpid = $(this).attr(\'id\');
								//alert("ajax==" + cid + "==" +  cpid);
								getFirstcatProducts(this.rel,this.id);
						});

});</script>';
		
			$result = array("prod_list"=>$str);
			//echo '<pre>';print_r($result);exit;
			echo json_encode($result);exit;
	
					
		return $i;
	}
	
	
    /**
     * validates forms for various methods in this class
     * 
     * @param string $form identify the form to validate
     */
    function __flmFormValidation($form = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //form validation
        if ($form == 'edit_task') {
            //check required fields
            $fields = array(
                'tasks_text' => $this->data['lang']['lang_title'],
                'tasks_start_date' => $this->data['lang']['lang_start_date'],
                'tasks_end_date' => $this->data['lang']['lang_end_date'],
                'tasks_milestones_id' => $this->data['lang']['lang_milestone'],
                'tasks_assigned_to_id' => $this->data['lang']['lang_assigned_to']);
            if (!$this->form_processor->validateFields($fields, 'required')) {
                return false;
            }
            //everything ok
            return true;
        }
        //nothing specified - return false & error message
        $this->form_processor->error_message = $this->data['lang']['lang_form_validation_error'];
        return false;
    }
	
	
	
    /**
     * Generates various pulldown (<option>...</option>) lists for ready use in HTML
     * Output is set to e.g. $this->data['lists']['milestones']
     *
     */
    function __pulldownLists()
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		
		//get utlevering count for a customer
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		$this->data['reg_blocks'][] = 'orders';
		$this->data['blocks']['orders'] = $this->orders_model->getCustomerOrderhistory($customer_id,'utlevering');
		$this->data['debug'][] = $this->orders_model->debug_data;
		
		$this->data['vars']['order_count'] =  count($this->data['blocks']['orders']);
		
		
        //get results
		$this->data['reg_blocks'][] = 'category';
		$this->data['blocks']['category'] = $this->products_model->getCategories();
		$this->data['debug'][] = $this->products_model->debug_data;	
		if (count($this->data['blocks']['category']) > 0) {
			for($i=0;$i<count($this->data['blocks']['category']);$i++){
				$path_parts = pathinfo($this->data['blocks']['category'][$i]['path']);
				$this->data['blocks']['category'][$i]['thumb'] = $path_parts['filename'] . "." .$path_parts['extension'];
			}
		}
    }
    /**
     * loads the view
     *
     * @param string $view the view to load
     */
    function __flmView($view = '')
    {
        //profiling
        $this->data['controller_profiling'][] = __function__;
        //template::
        $this->data['template_file'] = help_verify_template($this->data['template_file']);
        //post data
        $this->data['post'] = $_POST;
        //get data
        $this->data['get'] = $_GET;
        //url segmets array
        $this->data['url_segments'] = $this->uri->segment_array();
        $this->load->view($view, array('data' => $this->data));
    }
	
	/*get voucher details*/
	function __getVoucher(){
		
		$voucher = $this->input->post('voucher');
		$customer = $this->session->userdata['customer']['id'];
		
		$amount = $this->cart->total();
		$delivery_type = 'normal'; //default
		$zone = $this->session->userdata['zipdata']['zone'];
		$customerinfo=$this->customer_model->getCustomerinfo($customer);
		$total=$amount;
		$subtotal=$total;
		$cus_sub_total=$subtotal;
		
		$this->data['reg_fields'][] = 'delivery';
		$this->data['fields']['delivery'] =$this->general_model->checkMinimumAmount($delivery_type,$customer,$total,$zone);
		
		$min_price=$this->data['fields']['delivery']['min_price'];
		$min_price_txt = '';
		$min_price_status=0;
		if($cus_sub_total < $min_price)
		{
			$min_price_txt =  ' (Minste beløp kr '.formatcurrency($min_price).')';
			$min_price_status=1;
		}
		
		$subtotal = ($subtotal < $min_price) ?  $min_price : $subtotal;
		$delsum=$subtotal;
		
		$old_delivery_charge = $this->data['fields']['delivery']['delivery_charge'];
		
		$delviery = ($subtotal >= $this->data['fields']['delivery']['free_delivery_after']) ?  '0' : $this->data['fields']['delivery']['delivery_charge'];

		
		//get voucher type
		$voucher_data = $this->general_model->getVoucherType($voucher,$customer,$amount);
		$this->data['debug'][] = $this->general_model->debug_data;
		
		
		if (empty($voucher_data)) {
			
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Kupongen er ikke gyldig';
			$data['error2'] = 'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.';

			echo json_encode($data);exit;
		}
		
		//print_r($voucher_data);			
		
		$voucher_type = $voucher_data['type'];
		
		if($voucher_type == 'invitation'){
			$usedstatus = $this->general_model->checkVoucherused($voucher,$customer,$amount);
			if($usedstatus)
			{
				
				$delsumamt=$total/1.25;	
				$delsumamt=round($delsumamt, 2);
				$delsumvat= $total-$delsumamt;
				$data['mva']= formatcurrency($delsumvat);	
				$data['total']= $total;	
				$data['total_currenccy']= formatcurrency($total);	
				$data['error'] = true ;
				$data['error1'] = 'Beklage, du har allerede brukt den kupongkode. Kann ikke bruke mer enn én.';
				
				echo json_encode($data);
				
			}
		}
		

		$status = $this->general_model->validateVoucher($voucher_data['id'],$customer);
		if($status)
		{
			
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Beklager dette kupongkode allerede brukt.';
			$data['error2'] = 'Vennligst prøv en annen kupongkode';
			
			echo json_encode($data);exit;
		}
		
		
		$data = $this->general_model->getVoucherInfo($voucher,$customer,$amount,$voucher_type);
		$this->data['debug'][] = $this->general_model->debug_data;
		
		
		if(!empty($data)){
			
			
			$delivery_charge = $delivery['delivery_charge'];
			$min_price = $delivery['min_price'];
			$new_delsum = $this->cart->total();
				
			if($data['percentage'] != '')
			{
				$percentage=$data['percentage']/100;
				$discount=$new_delsum*$percentage;
				
				$data['price']=$discount;
				$data['discount_price']=$discount;
				
			}
			else
			{
				$discount=$data['price'];
				$data['discount_price']=$data['price'];
			}
			
			$new_delsum=$new_delsum-$discount;
			$data['min_price_txt']='';
			if($new_delsum < $min_price)
			{
				$data['min_price_txt']=' (Minste beløp kr '.$min_price.')';
				$min_price_status=1;
	
			}
			
			
			$new_delsum = ($new_delsum < $min_price) ?  $min_price : $new_delsum;
			$total =  $new_delsum;
			
			
			if($this->session->userdata['logged_in'] == 'shop')
			{
				$data['delivery_price']=0;	
				$min_price_status=0;
				$data['min_price']= 0;
			}
			
	
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['name']=$voucher;
			$data['discount_price_currency']= formatcurrency($discount);
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			
			
			$this->session->unset_userdata('subtotal');
			$this->session->unset_userdata('total_price');
			$newdata = array('total_price'  => $total,'subtotal'=>$new_delsum);
			$this->session->set_userdata($newdata);
			
			
			$newdata = array('total_price'  => $total);
			$this->session->set_userdata($newdata);
			
			
			if(!empty($data))
				echo json_encode($data);
			else{
			
				$data = array('error'=>'true','error1'=>'Kupongen er ikke gyldig','error2'=>'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.');
				echo json_encode($data);
			}
		}
		else{
			$total = $this->cart->total();
			$delsumamt=$total/1.25;	
			$delsumamt=round($delsumamt, 2);
			$delsumvat= $total-$delsumamt;
			$data['mva']= formatcurrency($delsumvat);	
			$data['total']= $total;	
			$data['total_currenccy']= formatcurrency($total);	
			$data['error'] = 'true' ;
			$data['error1'] = 'Kupongen er ikke gyldig';
			$data['error2'] = 'Din totale beløpet var mindre enn minimumsbeløpet for å bruke kupongkode.';
		
			echo json_encode($data);exit;
			
		}
		
		exit;
		
    }
	
	
	/*save order in draft(session) for a day*/
    function __saveParkere()
	{
		
			$pos_customer_id=$this->session->userdata['pos_customer_id'];
			$cartdata=$this->cart->contents();
			$dt=explode(':',date('j:n:Y',time()));
			$midnight1=mktime(0,0,0,$dt[1],$dt[0],$dt[2]);
			$midnight2=$midnight1+(1*24*60*60);
			$rest = $midnight2 - time();
			setcookie("cons1", 1 ,time()+$r);
			setcookie($pos_customer_id,serialize($cartdata),time()+$rest, '/');
			//setcookie($pos_customer_id,serialize($cartdata), time() + 1*60*60*24, '/');
			echo '1';exit;
	}

    function __skipsaldo()
	{
		$customer=$this->session->userdata['customer']['id'];
		$newdata = array('skipsaldo'  =>array($customer=>1));
		$this->session->set_userdata($newdata);
		echo '1';exit;
		
	}	
	
	 /**
      * get details of a customer
      */
     function __getCustomerDetail(){
 		
        //profiling
        $this->data['controller_profiling'][] = __function__;
		
		$customer_id = $this->session->userdata['pos_customer_id']; //get  customer id from session
		
		//get results
		$this->data['reg_fields'][] = 'customer';
		$this->data['fields']['customer'] = $this->customer_model->getCustomerinfo($customer_id);
		$this->data['debug'][] = $this->customer_model->debug_data;
		
		$this->data['lists']['customer_name'] = $this->data['fields']['customer']['firstname'];
		if(count($this->data['fields']['customer'])> 0){
			$this->data['visible']['wi_customer_profile'] = 1;
		}
		else{
			$this->data['visible']['wi_profile_none'] = 0;
		}
		
		$customer=$this->session->userdata['customer']['id'];
		$data = $this->payments_model->getAccountBalance($customer);
		$pendingsaldo = $data['pending'];
		$paidsaldo = $data['paid'];
			
		if(intval($pendingsaldo) > 0)
		{	
			//$amount= formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
			$amount= formatcurrency($paidsaldo);
		}
		else
		{
			$amount= formatcurrency($paidsaldo);
		}
		$this->data['lists']['saldo']=$amount;
		$this->data['lists']['saldocolor']='';
		if($paidsaldo < 0)
		{
			$this->data['lists']['saldocolor']='style="color:red;"';
		}
		
	 }
	
	
	
	
	function __getparkerte()
	{
		//$cus_id=$this->session->userdata['pos_customer_id'];
		//echo '<pre>';print_r($_COOKIE);exit;
		$this->data['reg_blocks'][] = 'parkerte';
		
			if(count($_COOKIE) > 0)
			{
				$newarray=array();
				$i=0;
				foreach($_COOKIE as $pitems=>$parkerteitems)
				{
					
					if(intval($pitems) > 0)
					{
						$customer = $this->customer_model->getCustomerinfo($pitems);
						$cartdata=unserialize($parkerteitems);
						
						$amtarray=array();
						foreach($cartdata as $cartitems)
						{
							$amtarray[]=$cartitems['subtotal'];
						}
						
						$color_array = array("order-green", "order-red", "order-blue", "order-orange");
						// get random index from array $arrX
						$randIndex = array_rand($color_array);
						// output the value for the random index
						$color = $color_array[$randIndex];
			
						$this->data['blocks']['parkerte'][$i]['id']=$pitems;
						
						
						$str .='<div id="order_'.$this->data['blocks']['parkerte'][$i]['id'].'" class="order-block parkerte-block">'; 
						
						$amount = array_sum($amtarray);
					
						$str .='<div class="col-md-2 no-padd text-center '.$color.'">
						  <p class="price"><a href="#" rel="'.$this->data['blocks']['parkerte'][$i]['id'].'">#'.$this->data['blocks']['parkerte'][$i]['id'].' <span>kr '.formatcurrency($amount).'</span></a></p>
						  <p class="name"><a href="#" rel="'.$this->data['blocks']['parkerte'][$i]['id'].'">'.$customer['firstname'].'</a></p>
						</div>';
					 $str .='</div>';
					 
					
					}
				
					$i++;
				}
				
			}
			
			
			if(!empty($str)){
				$this->data['visible']['wi_customer_orders'] = 1;
				$this->data['lists']['orders'] =  $str;
			}
			else{
				$this->data['visible']['wi_customer_orders'] = 0;
			}
			
	}
	
	function __addParkerteCart()
	{
		
			$cus_id=$_POST['cid'];
			
			
			if(isset($_COOKIE[$cus_id]))
			{
				$cartdata=unserialize($_COOKIE[$cus_id]);
				$this->cart->destroy();
				
				$data=$this->customer_model->getCustomerinfo($cus_id);
				$mobileinfo=$this->customer_model->customermobileinfo($data['mobile']);	
				$newdata = array('customer'  => $data);
				$this->session->set_userdata($newdata);
				$newdata = array('pos_customer_id'  => $cus_id);
				$this->session->unset_userdata('pos_customer_id');
				$this->session->set_userdata($newdata);
				
				$data = $this->payments_model->getAccountBalance($cus_id);
				$pendingsaldo = $data['pending'];
				$paidsaldo = $data['paid'];
				if(intval($pendingsaldo) > 0)
				{	
					//$amount=formatcurrency($paidsaldo).' ('.formatcurrency($pendingsaldo).')';
					$amount=formatcurrency($paidsaldo);
				}
				else
				{
					$amount=formatcurrency($paidsaldo);
				}
				
				$profile='<p><span>'.$mobileinfo['firstname'].' '.$mobileinfo['lastname'].'</span> (+47) '.$mobileinfo['mobile'].' <br>
                        <a href="mailto:'.$mobileinfo['email'].'">'.$mobileinfo['email'].'</a> '.$mobileinfo['partner_branch_name'].' </p>';
				
				
				$cookie_name = $cus_id;
				unset($_COOKIE[$cookie_name]);
				//empty value and expiration one hour before
				setcookie($cookie_name, '', time() - 3600);
			
				if(count($cartdata) > 0)
				{    
					foreach($cartdata as $citems)
					{
						$cart[] =  array(
						'id'      => $citems['id'],
						'name'     => $citems['name'],
						'price'   => $citems['price'],
						'qty'    => $citems['qty'],
						'utlevering'    => $citems['utlevering'],
						'options' => array('image'=>$citems['options']['image'],'description'=>$citems['options']['description'],'gtype'=>$citems['options']['gtype'],'complain'=>$citems['options']['complain'],'in_house'=>$citems['options']['in_house'])
						);
					}
				}
				$this->cart->insert($cart);
				//print_r($this->cart->contents());
			
			$result=array('status'=>'success','saldo'=>$amount,'profile'=>$profile);
		 }
		else
		{
			$result=array('status'=>'error');
		}
		echo json_encode($result);exit;
	}
	
		/*insert items to cart  */
	function __insert_Cart($productinfo,$barcode){
		
		  if(count($productinfo) > 0)
		  {
			$id = $productinfo['id'];
			$name = $productinfo['name'];
			$price = $productinfo['price'];
			$qty = 1;
			$img = $productinfo['path'];
			$desp = $productinfo['description'];
			$gtype = $productinfo['gtype'];
			$duration = $productinfo['duration'];
			$complain = '';
			$in_house = '';
			$spl_instruction = '';
			
			/*if($id == 143)
			{
				$price=$qty;
				$qty=1;
				if(intval($price) == 0)
				{	
					$qty=0;
				}
			}*/
			$price = ($complain == '1')  ?   0 : $price;
			if($id == 143)
			{
				$price=$qty;
				$qty=1;
				
				if($complain == '1')
				{
					$price=0;
					$qty=1;
				}
				else
				{
					if(intval($price) == 0)
					{	
						$qty=0;
					}
				}
				
				
				
			}
			
			
			
			
			$pdata = $this->products_model->getProduct($id,'main','',$this->session->userdata['logged_in']);
			//print_r($pdata);
			$in_meter = $pdata['in_meter'];
			
			
			$days = floor (($duration*60) / 1440);
			$c_date=date('Y-m-d');
			$date = new DateTime($c_date);
			
			if($duration == 96){
				$delivery_date=addDays($c_date,$days,true);
				$delivery_date=date('d.m.Y',strtotime($delivery_date));
			}
			else{
				$date->modify("+$days day");
				$delivery_date = $date->format('d.m.Y');
			}
			
			$special_date=date('Y-m-d',strtotime($delivery_date));
			$sdelivery_date = $this->general_model->checkSpecialdate($special_date);
			$delivery_date=date('d.m.Y',strtotime($sdelivery_date));
			
			$price = ($complain!='1')  ?   $price : 0;
			$price = ($in_meter !='1')  ?   $price : 0;
			
				
			$dataa = $this->products_model->getProduct_Discount($id);
			$newdiscount=array();
			if(count($dataa) > 0)
			{
				$newdiscount=$dataa[0];
			}
			
			//insert into cart items
			$this->cart->product_name_rules ='\d\D';    //remove product name validation for special characters
			if($qty!=''){
				$data[] =  array(
						'id'      => $id,
						'name'     => $name,
						'price'   => $price,
						'qty'    => $qty,
						'utlevering' =>$delivery_date,
						'options' => array('heatseal'=>$barcode,'image'=>$img,'description'=>$spl_instruction,'gtype'=>$gtype,'discount'=>$newdiscount,'duration'=>$duration,'complain'=>$complain,'in_house'=>$in_house)
			     );
				
				
			
			$result = $this->cart->insert($data);       //insert cart data
			
			
			/*$response = array("error"=>$result);
			echo json_encode($response);exit;*/
			
			}
		  }
		
	}	
	
	
	
 
}
/* End of file products.php */
/* Location: ./application/controllers/admin/products.php */