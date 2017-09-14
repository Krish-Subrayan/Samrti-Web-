<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
class Cron extends MY_Controller {
 
    function __construct()
    {
        parent::__construct();
		
        // this controller can only be called from the command line
    }
 
    function index()
    {
	
       $orders = $this->orders_model->getInfileOrders();
	   
	  
		$indata='';
		$writedata='';
	   if(count($orders) > 0)
	   {
			foreach($orders as $orderinfo)
			{
		
		
				$orderid=$orderinfo['id'];
				$orderlines=$this->process_order_model->getOrderLine($orderid);	
				
			//	echo '<pre>';print_r($orderlines);exit;
				
				//$orderinfo=$this->process_order_model->getCustomerDetails($orderid);
				
				if(count($orderlines) > 0)
				{	
					$i=1;
					$firstdeliverytime=$orderlines[0]['p_b_delivery_time'];
					if($firstdeliverytime == '')
					{
						$firstdeliverytime=date('Y-m-d',strtotime($orderinfo['delivery_start_time']));
					}
					
					foreach($orderlines as $orderitems)
					{
					
						if($orderitems['p_b_delivery_time'] == '')
						{
							$orderline_today_delivery = true;
							
						}
						else
						{
							if($firstdeliverytime == $orderitems['p_b_delivery_time'])
							{
								$orderline_today_delivery = true;
							}
							
							//$orderline_today_delivery = $this->orders_model->validateOrderlineTodaydelivery($orderitems['orderline']);
						}
						
						if($orderline_today_delivery)
						{
							$query=$this->db->query("SELECT order_id_start FROM a_partner_branch WHERE id='".$orderinfo['partner_branch']."' AND partner='".$orderinfo['partner']."'");
							$partners=$query->row_array();
							$order_id_start=$partners['order_id_start'];
							
							if($orderinfo['type'] == 'shop')
							{
								$oid=$orderitems['order'];
							}
							else
							{
								$oid=$order_id_start+$orderitems['order'];
							}
							//app', 'web', 'shop'
				
							$total_items = count($orderlines);
							
							if(intval($orderitems['additional_product']) > 0)
							{	
								$query=$this->db->query("SELECT a_additional_product.name FROM a_product_additional_product
								LEFT JOIN a_additional_product ON a_additional_product.id=a_product_additional_product.additional_product
								WHERE a_product_additional_product.id='".$orderitems['additional_product']."'");		
								if($query->num_rows() > 0)		
								{				
									$result=$query->row_array();
									$orderitems['name']=$result['name'];
								}
							}
							
							$orderid = str_pad($oid, 20);
							$total_items = sprintf("%02d", $total_items);
							$ival = sprintf("%02d", $i);
							$garcode = str_pad($orderitems['heat_seal_barcode'], 20);  
							$garment_name = str_pad($orderitems['name'], 30); 
							$garment_per='033';
							$hung='S';
							$garment_status=str_pad('R', 56);
							//$space=str_pad('', 55);
							
							$customer_info= ';'.$oid.';In '.$orderinfo['collection_time'].'  Ready '.$orderinfo['delivery_time'].';'.$orderinfo['customer_name'].', '.$orderinfo['zip'].' ;'.$orderinfo['number'].';Smart Laundry;';
							
							
							$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
							
							
							if(count($orderlines) > $i) 
							{
								$indata.="\n";
							}
						
						}
						
						
						
						
					}
					if($writedata != '')
					{
						$writedata.="\n";
					}
					$writedata.=$indata;
					
					
				}
			}
			
			
			if($writedata != '')
			{ 
					if (!write_file('sorting/automat.in',$writedata,'w')){
						return false;
					}
					else
					{
						echo 'Success';
					}
			}
			else
			{
				echo 'Data not found';
			}
					
	   }

	
    }
}
/* End of file cron.php */
/* Location: ./application/controllers/cron.php */