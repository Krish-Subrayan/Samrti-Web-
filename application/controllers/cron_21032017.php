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
		   
		    //echo count($orders);
			//print_r( $orders); 
			
		   
			foreach($orders as $orderinfo)
			{
				$orderid=$orderinfo['id'];
				$orderlines=$this->process_order_model->getOrderLine($orderid);	
				
				//print_r( $orderlines); 
				
				if(count($orderlines) > 0)
				{	
					$i=1;
					foreach($orderlines as $orderitems)
					{
						if($orderitems['heat_seal_barcode'] != '' && $orderitems['status'] == 'started')
						{
							/*$query=$this->db->query("SELECT order_id_start FROM a_partner_branch WHERE id='".$orderinfo['partner_branch']."' AND partner='".$orderinfo['partner']."'");
							$partners=$query->row_array();
							$order_id_start=$partners['order_id_start'];*/
							
							if($orderinfo['type'] == 'shop')
							{
								$oid=$orderitems['order'];
							}
							else
							{
								//$oid=$order_id_start+$orderitems['order'];
								$oid=26000000+$orderitems['order'];
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
							$garment_name = mb_str_pad($orderitems['name'], 30); 
							$garment_per='033';
							$hung='S';
							$garment_status=str_pad('R', 56);
							//$space=str_pad('', 55);
							
							if($orderinfo['type'] == 'shop')
							{
								$query=$this->db->query("SELECT name FROM a_partner_branch
								WHERE id='".$orderinfo['partner_branch']."'");		
								$bresult=$query->row_array();
								$odate  = date("d/m/Y",strtotime($orderinfo['odate']));
								
								$customer_info= ';'.$oid.';In '.$odate.'  Ready '.$orderitems['p_b_delivery_time'].';'.$orderinfo['customer_name'].', '.$orderinfo['zip'].' ;'.$orderinfo['number'].';'.$bresult['name'].';';
							}
							else
							{
								$customer_info= ';'.$oid.';In '.$orderinfo['collection_time'].'  Ready '.$orderinfo['delivery_time'].';'.$orderinfo['customer_name'].', '.$orderinfo['zip'].' ;'.$orderinfo['number'].';Smart Laundry;';
							}
							
							
							$indata.=$orderid.$total_items.$ival.$garcode.$garment_name.$garment_per.$hung.$garment_status.$customer_info;
							$indata.="\n";
							
							//$this->process_order_model->updateHetseallogstatus($orderitems['logid']);
							
							$i++;
							
						}
					}
					
				}
			}
		
	   }
	   
			$date = date('Ymd').'_'.time();
			$filename = 'automat_'.$date.'.SMART';

			if($indata != '')
			{
					if (!write_file('sorting/'.$filename,$indata,'w')){
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
/* End of file cron.php */
/* Location: ./application/controllers/cron.php */