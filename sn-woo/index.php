<?php
/*
Plugin Name:  درگاه پرداخت ووکامرس
Plugin URI: http://www.wordpress.org
Description: درگاه پرداخت ووکامرس
Version: 1.0
Author: WooCommerce
Author URI: http://www.wordpress.org
*/
session_start();
add_action('plugins_loaded', 'WC_sn', 0); 

function WC_sn() 
{
    if ( !class_exists( 'WC_Payment_Gateway' ) ) 
		return;
	
    class WC_full_sn extends WC_Payment_Gateway
	{
        public function __construct()
		{
        	
            $this ->id 			 	 = 'sn';
            $this ->method_title 	  	 = 'درگاه پرداخت آنلاين';
            $this ->has_fields 	   	 = false;
            $this ->init_form_fields();
            $this ->init_settings();
			
			$this->title				= $this->settings['title'];
			$this->description			= $this->settings['description'];
			$this->api_sn			 	= $this->settings['api_sn'];
			$this ->redirect_page_id	= $this ->settings['redirect_page_id'];
 
			$this ->msg['message'] = "";
			$this ->msg['class'] = "";
 
			add_action('woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_sn_response' ) );
			add_action('valid-sn-request', array($this, 'successful_request'));

  		    if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) 
                add_action( 'woocommerce_update_options_payment_gateways_sn', array( &$this, 'process_admin_options' ) );
             else 
                add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
			 
			add_action('woocommerce_receipt_sn', array(&$this, 'receipt_page'));
        }

       function init_form_fields()
	   {
            $this ->form_fields = array(
                'enabled' =>array(
                    'title' =>'فعال سازی/غیر فعال سازی :',
                    'type' =>'checkbox',
                    'label' =>'فعال سازي درگاه پرداخت انلاين',
                    'description' =>'برای امکان پرداخت کاربران از طریق این درگاه باید تیک فعال سازی زده شده باشد .',
                    'default' =>'no'),
                'api_sn' =>array(
                    'title' =>'API درگاه : ',
                    'type' =>'text',
                    'description' =>'شناسه درگاه api خود را وارد نمایید .'),
                'title' =>array(
                    'title' =>'عنوان درگاه :',
                    'type'=>'text',
                    'description' =>'این عتوان در سایت برای کاربر نمایش داده می شود .',
                    'default' =>'درگاه پرداخت انلاين'),
                'description' =>array(
                    'title' =>'توضیحات درگاه :',
                    'type' =>'textarea',
                    'description' =>'این توضیحات در سایت، بعد از انتخاب درگاه توسط کاربر نمایش داده می شود .',
                    'default' =>'پرداخت وجه از طريق تمامي درگاه پرداخت آنلاين'),
				'redirect_page_id' =>array(
                    'title' =>'آدرس بازگشت',
                    'type' =>'select',
                    'options' =>$this ->get_pages('صفحه مورد نظر را انتخاب نمایید'),
                    'description' =>"صفحه‌ای که در صورت پرداخت موفق نشان داده می‌شود را نشان دهید."),
            );
        }
		
        public function admin_options()
		{
            echo '<h3>'.__('درگاه پرداخت اينترنتي', 'sn').'</h3>';
            echo '<p>'.__('درگاه پرداخت اينترنتي').'</p>';
            echo '<table class="form-table">';
            $this ->generate_settings_html();
            echo '</table>';
		}

		function receipt_page($order_id)
		{
            global $woocommerce;
            $order = new WC_Order($order_id);
            $redirect_url = ($this ->redirect_page_id=="" || $this ->redirect_page_id==0)?get_site_url() . "/":get_permalink($this ->redirect_page_id);
			$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
			unset( $woocommerce->session->zegersot );
			unset( $woocommerce->session->zegersot_id );
			$woocommerce->session->zegersot = $order_id;
			


			$amount = round($order ->order_total)/10;

			$items_list = NULL;
			foreach ($order->get_items() as $item)
			{
				if(isset($items_list))
					$items_list .= "-" . $item['name'];
				else
					$items_list = $item['name'];
			}
// Security
$sec = uniqid();
$md = md5($sec.'vm');
// Security

			$api = $this ->api_sn;
			$redirect_url = $redirect_url . '&sec=' . $sec . '&md=' . $md; 
			
$data_string = json_encode(array(
'pin'=> $api,
'price'=> $amount,
'callback'=> $redirect_url ,
'order_id'=> $order_id,
'ip'=> $_SERVER['REMOTE_ADDR'],
'callback_type'=>2
));

$ch = curl_init('https://developerapi.net/api/v1/request');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen($data_string))
);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
$result = curl_exec($ch);
curl_close($ch);


$json = json_decode($result,true);
if(!empty($json['result']) AND $json['result'] == 1)
			{ 
// Set Session
$_SESSION[$sec] = [
	'price'=>$amount ,
	'order_id'=>$invoice_id ,
	'au'=>$json['au'] ,
];

				$woocommerce->session->zegersot_id = $result;
			echo "<div style='display:none'>{$json['form']}</div>Please wait ... <script language='javascript'>document.payment.submit(); </script>";
				
			}
			else
				echo '<meta charset=utf-8><pre>';
	$res = array_map('urldecode',$json['msg']);
	print_r($res);
        }
        
        function process_payment($order_id)
		{
            $order = &new WC_Order($order_id);
			return array('result' =>'success', 'redirect' =>$order->get_checkout_payment_url( true )); 
        }
	

	    function check_sn_response()
		{
			global $woocommerce;
			$order_id = $_GET['order_id'];
			
// Security
$sec=$_GET['sec'];
$mdback = md5($sec.'vm');
$mdurl=$_GET['md'];
// Security
$transData = $_SESSION[$sec];
$au=$transData['au']; //			
			
			$order = &new WC_Order($order_id);
			$api = $this ->api_sn;
			$amount = round($order ->order_total)/10;
	

$bank_return = $_POST + $_GET ;
$data_string = json_encode(array (
'pin' => $api,
'price' => $amount,
'order_id' => $order_id,
'au' => $au,
'bank_return' =>$bank_return,
));

$ch = curl_init('https://developerapi.net/api/v1/verify');
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'Content-Type: application/json',
'Content-Length: ' . strlen($data_string))
);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 20);
$result = curl_exec($ch);
curl_close($ch);
$json = json_decode($result,true);

	
	if(!empty($_GET['sec']) AND !empty($_GET['md']) AND !empty($_GET['au']))
			{

		
		                if($mdback == $mdurl)
	                          { 
		                  
				if( ! empty($json['result']) and $json['result'] == 1)
				{
					$this ->msg['message'] = "پرداخت شما با موفقیت انجام شد";
					$this ->msg['class'] = 'woocommerce_message';
					$order ->payment_complete();
					$order ->add_order_note($this->msg['message']);
					$order ->add_order_note('پرداخت انجام شد<br/>کد پیگیری : '.$au);					
					$woocommerce ->cart ->empty_cart();
					wp_redirect( add_query_arg( 'wc_status', 'success', $this->get_return_url( $order ) ) );
					exit;
				}
				else
				{
					$this ->msg['class'] = 'woocommerce_error';
					$this ->msg['message'] = "خطا ({$result}) : {$json['msg']}";	
					$order ->add_order_note('پرداخت ناموفق');
					$order ->add_order_note($this ->msg['message']);
				}	
			     }
				else
				{
					$this ->msg['class'] = 'woocommerce_error';
					$this ->msg['message'] = "خطای امنیتی ({$result}) : {$errorCode[$result]}";	
					$order ->add_order_note('پرداخت ناموفق');
					$order ->add_order_note($this ->msg['message']);
				}	
				
			}
			else
			{
				$this ->msg['class'] = 'woocommerce_error';
				$this ->msg['message'] = "پرداخت ناموفق";	
				$order ->add_order_note('اطلاعات ارسالی صحیح نمیباشد .');
				$order ->add_order_note($this ->msg['message']);
			}
						
			$redirect_url = ($this ->redirect_page_id=="" || $this ->redirect_page_id==0)?get_site_url() . "/":get_permalink($this ->redirect_page_id);
			$redirect_url = add_query_arg( array('msg'=>base64_encode($this ->msg['message']), 'type'=>$this ->msg['class']), $redirect_url );
			wp_redirect( $redirect_url );
            exit;
		}

	    function get_pages($title = false, $indent = true) 
		{
	        $wp_pages = get_pages('sort_column=menu_order');
	        $page_list = array();
	        if ($title) $page_list[] = $title;
	        foreach ($wp_pages as $page) 
			{
	            $prefix = '';
	            if ($indent) 
				{
	                $has_parent = $page->post_parent;
	                while($has_parent) 
					{
	                    $prefix .=  ' - ';
	                    $next_page = get_page($has_parent);
	                    $has_parent = $next_page->post_parent;
	                }
	            }
	            $page_list[$page->ID] = $prefix . $page->post_title;
	        }
	        return $page_list;
	    }
	}

    function woocommerce_add_sn_gateway($methods) 
	{
        $methods[] = 'WC_full_sn';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'woocommerce_add_sn_gateway' );
}

if($_GET['message']!='')
{
	add_action('the_content', 'showMessage');
	
	function showMessage($content)
	{
			return '<div class="'.htmlentities($_GET['class']).'">'.base64_decode($_GET['message']).'</div>'.$content;
	}
}
?>