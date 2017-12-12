<?php
session_start();
if (!defined('ABSPATH')) {

	die('This file cannot be accessed directly');
}

function Load_sn_Gateway()
{
	if (class_exists('WC_Payment_Gateway') && !class_exists('WC_sn_Gateway') && !function_exists('Woocommerce_Add_sn_Gateway')) {

		add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_sn_Gateway');

		function Woocommerce_Add_sn_Gateway($methods)
		{
			$methods[] = 'WC_sn_Gateway';

			return $methods;
		}

		add_filter('woocommerce_currencies', 'add_sn_currency');

		function add_sn_currency($currencies)
		{
			$currencies['IRR']  = __('ریال', 'woocommerce');
			$currencies['IRT']  = __('تومان', 'woocommerce');
			$currencies['IRHR'] = __('هزار ریال', 'woocommerce');
			$currencies['IRHT'] = __('هزار تومان', 'woocommerce');

			return $currencies;
		}

		add_filter('woocommerce_currency_symbol', 'add_sn_currency_symbol', 10, 2);

		function add_sn_currency_symbol($currency_symbol, $currency)
		{
			switch($currency) {

				case 'IRR': $currency_symbol = 'ریال';
					break;

				case 'IRT': $currency_symbol = 'تومان';
					break;

				case 'IRHR': $currency_symbol = 'هزار ریال';
					break;

				case 'IRHT': $currency_symbol = 'هزار تومان';
					break;
			}

			return $currency_symbol;
		}

		class WC_sn_Gateway extends WC_Payment_Gateway
		{
			public function __construct()
			{
				$this->id                 = 'sn';
				$this->method_title       = __('درگاه پرداخت و کیف پول الکترونیک ', 'woocommerce');
				$this->method_description = __('تنظیمات درگاه پرداخت و کیف پول الکترونیک  برای افزونه WooCommerce', 'woocommerce');
				$this->icon               = apply_filters('WC_sn_logo', WP_PLUGIN_URL . '/' . plugin_basename(dirname(__FILE__)) . '/assets/images/sn.png');
				$this->has_fields         = false;

				$this->init_form_fields();
				$this->init_settings();

				$this->title       = $this->settings['title'];
				$this->description = $this->settings['description'];

				$this->api_key = $this->settings['api_key'];
				
					$this->webservice = $this->settings['webservice'];
				
				$this->success_massage = $this->settings['success_massage'];
				$this->failed_massage  = $this->settings['failed_massage'];

				if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {

				    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

				} else {

				    add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
				}

				add_action('woocommerce_receipt_' . $this->id, array($this, 'Send_to_sn_Gateway'));
				add_action('woocommerce_api_' . strtolower(get_class($this)) . '', array($this, 'Return_from_sn_Gateway'));
			}

			public function admin_options()
			{
				parent::admin_options();
			}

			public function init_form_fields()
			{
				$this->form_fields = apply_filters('WC_sn_Config', array(
					

					'base_confing' => array(

						'title'       => __('تنظیمات پایه', 'woocommerce'),
						'type'        => 'title',
						'description' => null,
						'desc_tip'    => false
					),
					'enabled' => array(

						'title'       => __('فعال سازی / غیر فعال سازی', 'woocommerce'),
						'type'        => 'checkbox',
						'label'       => __('فعال سازی درگاه پرداخت ', 'woocommerce'),
						'description' => __('برای فعال سازی درگاه پرداخت  باید چک باکس را تیک بزنید', 'woocommerce'),
						'default'     => 'yes',
						'desc_tip'    => true
					),
					'webservice' => array(

						'title'       => __('فعال سازی / غیر فعال سازی', 'woocommerce'),
						'type'        => 'checkbox',
						'label'       => __('آیتم های اختیاری وب سرویس ', 'woocommerce'),
						'description' => __('در صورت فعال بودن ، اطلاعات خریدار در پنل کاربری ثبت خواهد شد', 'woocommerce'),
						'default'     => 'yes',
						'desc_tip'    => true
					),
					'title' => array(

						'title'       => __('عنوان درگاه', 'woocommerce'),
						'type'        => 'text',
						'description' => __('عنوانی که برای درگاه در طی مراحل خرید به مشتری نمایش داده میشود', 'woocommerce'),
						'default'     => __('درگاه پرداخت و کیف پول الکترونیک ', 'woocommerce'),
						'desc_tip'    => true
					),
					'description' => array(

						'title'       => __('توضیحات درگاه', 'woocommerce'),
						'type'        => 'text',
						'description' => __('توضیحاتی که در مرحله پرداخت برای درگاه نمایش داده خواهد شد', 'woocommerce'),
						'default'     => __('پرداخت امن با استفاده از کلیه کارت های عضو شبکه شتاب از طریق درگاه پرداخت و کیف پول الکترونیک ', 'woocommerce'),
						'desc_tip'    => true
					),
					'account_confing' => array(

						'title'       => __('تنظیمات حساب ', 'woocommerce'),
						'type'        => 'title',
						'description' => null,
						'desc_tip'    => false
					),
					'api_key' => array(

						'title'       => __('کلید API', 'woocommerce'),
						'type'        => 'text',
						'description' => null,
						'default'     => null,
						'desc_tip'    => false
					),
					'payment_confing' => array(

						'title'       => __('تنظیمات عملیات پرداخت', 'woocommerce'),
						'type'        => 'title',
						'description' => null,
						'desc_tip'    => false
					),
					'success_massage' => array(

						'title'       => __('پیام پرداخت موفق', 'woocommerce'),
						'type'        => 'textarea',
						'description' => __('متن پیامی که می خواهید پس از پرداخت موفق به کاربر نمایش داده شود را وارد نمایید.<br/>شما می توانید از شورت کد {transaction_id} برای نمایش شماره پیگیری تراکنش استفاده نمایید.', 'woocommerce'),
						'default'     => __('از شما سپاسگزاریم، سفارش شما با موفقیت پرداخت و تایید شد. شماره پیگیری: {transaction_id}', 'woocommerce'),
						'desc_tip'    => false
					),
					'failed_massage' => array(

						'title'       => __('پیام پرداخت ناموفق', 'woocommerce'),
						'type'        => 'textarea',
						'description' => __('متن پیامی که می خواهید پس از پرداخت ناموفق به کاربر نمایش داده را وارد نمایید.<br/>شما می توانید از شورت کد {fault} برای نمایش دلیل خطای رخ داده استفاده نمایید. این دلیل خطا از سایت  ارسال میگردد.', 'woocommerce'),
					'default' => __('متاسفانه پرداخت شما ناموفق بوده است، لطفا مجددا تلاش نمایید یا در صورت بروز اشکال با مدیر سایت تماس بگیرید.<p> شرح خطا: {fault}', 'woocommerce'),
						'desc_tip'    => false
					)
				));
			}

			public function process_payment($order_id)
			{
				$order = new WC_Order($order_id);

				return array(

					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url(true)
				);
			}

			public function Send_to_sn_Gateway($order_id)
			{
				global $woocommerce;

				$woocommerce->session->order_id_sn = $order_id;

				$order = new WC_Order($order_id);

				$currency = $order->get_currency();
				$currency = apply_filters('WC_sn_Currency', $currency, $order_id);

				$form = '<form id="sn-checkout-form" name="sn-checkout-form" method="post" class="sn-checkout-form">';
				$form .= '<input id="sn-payment-button" name="sn_submit" type="submit" class="button alt" value="'.__('پرداخت', 'woocommerce').'"/>';
				$form .= '<a href="' . $woocommerce->cart->get_checkout_url() . '" class="button cancel">' . __('بازگشت', 'woocommerce') . '</a>';
				$form .= '</form><br/>';

				$form = apply_filters('WC_sn_Form', $form, $order_id, $woocommerce);

				do_action('WC_sn_Gateway_Before_Form', $order_id, $woocommerce);

				echo $form;

				do_action('WC_sn_Gateway_After_Form', $order_id, $woocommerce);

				$amount = intval($order->get_total());
				$amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_before_check_currency', $amount, $currency);

				if (strtolower($currency) == strtolower('IRT') || strtolower($currency) == strtolower('TOMAN')) {

					$amount = $amount;
				}

				if (strtolower($currency) == strtolower('Iran TOMAN') || strtolower($currency) == strtolower('Iranian TOMAN')) {

					$amount = $amount;
				}

				if (strtolower($currency) == strtolower('Iran-TOMAN') || strtolower($currency) == strtolower('Iranian-TOMAN')) {

					$amount = $amount;
				}

				if (strtolower($currency) == strtolower('Iran_TOMAN') || strtolower($currency) == strtolower('Iranian_TOMAN')) {

					$amount = $amount;
				}

				if (strtolower($currency) == strtolower('تومان') || strtolower($currency) == strtolower('تومان ایران')) {

					$amount = $amount;
				}

				if (strtolower($currency) == strtolower('IRHT')) {

					$amount = $amount * 1000;
				}

				if (strtolower($currency) == strtolower('IRHR')) {

					$amount = ceil((($amount * 1000)/10));
				}

				if (strtolower($currency) == strtolower('IRR')) {

					$amount = ceil(($amount/10));
				}

				$amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_after_check_currency', $amount, $currency);
				$amount = apply_filters('woocommerce_order_amount_total_IRANIAN_gateways_irt', $amount, $currency);
				$amount = apply_filters('woocommerce_order_amount_total_sn_gateway', $amount, $currency);


					$products    = array();
					$order_items = $order->get_items();

					foreach ((array)$order_items as $product) {

						$products[] = $product['name'] . ' (' . $product['qty'] . ') ';
					}

					$products = implode(' - ', $products);

					$description = 'خرید به شماره سفارش: ' . $order->get_order_number() . ' | محصولات: ' . $products;
					$mobile      = get_post_meta($order_id, '_billing_phone', true) ? get_post_meta($order_id, '_billing_phone', true) : null;

					$description = apply_filters('WC_sn_Description', $description, $order_id);
					$mobile      = apply_filters('WC_sn_Mobile', $mobile, $order_id);
					$email       = apply_filters('WC_sn_Email', $order->get_billing_email(), $order_id);
					$paymenter   = apply_filters('WC_sn_Paymenter', $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), $order_id);
					$res_number  = apply_filters('WC_sn_ResNumber', intval($order->get_order_number()), $order_id);

					do_action('WC_sn_Gateway_Payment', $order_id, $description, $mobile);

					$api_key  = $this->api_key;
					$web  = $this->webservice;
					
				
				//echo $web;
			//	echo $options;
		
					
					
				//exit;
					// Security
					$sec = uniqid();
					$md = md5($sec.'vm');
					// Security
					$callback = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_sn_Gateway'));

					
						if ($web == 'yes'){
						    
				    if($email==''){$email='0'; }
				     if($paymenter==''){$paymenter='0';}
				      if($mobile==''){$mobile='0';}
				       if($description==''){$description='0';}
				       
					   	$data_string = json_encode(array(
					'pin'=> $api_key,
					'price'=> $amount,
					'callback'=>$callback. '&sec=' . $sec . '&md=' . $md. '&price=' . $amount ,
					'order_id'=> $order_id,
					'email'=> $email,
					'description'=> $description,
					'name'=> $paymenter,
					'mobile'=> $mobile,
					'ip'=> $_SERVER['REMOTE_ADDR'],
					'callback_type'=>2
					));
				    
			}
					
					else
					{
					   	$data_string = json_encode(array(
					'pin'=> $api_key,
					'price'=> $amount,
					'callback'=>$callback. '&sec=' . $sec . '&md=' . $md. '&price=' . $amount ,
					'order_id'=> $order_id,
					'email'=> '0',
					'description'=> $description,
					'name'=> '0',
					'mobile'=> '0',
					'ip'=> $_SERVER['REMOTE_ADDR'],
					'callback_type'=>2
					));
					    
					}
				

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
					
					
					
						 $res=$json['result'];
                 
	                 switch ($res) {
						    case -1:
						    $msg = "پارامترهای ارسالی برای متد مورد نظر ناقص یا خالی هستند . پارمترهای اجباری باید ارسال گردد";
						    break;
						     case -2:
						    $msg = "دسترسی api برای شما مسدود است";
						    break;
						     case -6:
						    $msg = "عدم توانایی اتصال به گیت وی بانک از سمت وبسرویس";
						    break;

						     case -9:
						    $msg = "خطای ناشناخته";
						    break;

						     case -20:
						    $msg = "پین نامعتبر";
						    break;
						     case -21:
						    $msg = "ip نامعتبر";
						    break;

						     case -22:
						    $msg = "مبلغ وارد شده کمتر از حداقل مجاز میباشد";
						    break;


						    case -23:
						    $msg = "مبلغ وارد شده بیشتر از حداکثر مبلغ مجاز هست";
						    break;
						    
						      case -24:
						    $msg = "مبلغ وارد شده نامعتبر";
						    break;
						    
						      case -26:
						    $msg = "درگاه غیرفعال است";
						    break;
						    
						      case -27:
						    $msg = "آی پی مسدود شده است";
						    break;
						    
						      case -28:
						    $msg = "آدرس کال بک نامعتبر است ، احتمال مغایرت با آدرس ثبت شده";
						    break;
						    
						      case -29:
						    $msg = "آدرس کال بک خالی یا نامعتبر است";
						    break;
						    
						      case -30:
						    $msg = "چنین تراکنشی یافت نشد";
						    break;
						    
						      case -31:
						    $msg = "تراکنش ناموفق است";
						    break;
						    
						      case -32:
						    $msg = "مغایرت مبالغ اعلام شده با مبلغ تراکنش";
						    break;
						 
						    
						      case -35:
						    $msg = "شناسه فاکتور اعلامی order_id نامعتبر است";
						    break;
						    
						      case -36:
						    $msg = "پارامترهای برگشتی بانک bank_return نامعتبر است";
						    break;
						        case -38:
						    $msg = "تراکنش برای چندمین بار وریفای شده است";
						    break;
						    
						      case -39:
						    $msg = "تراکنش در حال انجام است";
						    break;
						    
                            case 1:
						    $msg = "پرداخت با موفقیت انجام گردید.";
						    break;

						    default:
						       $msg = $json['msg'];
						}
						
			
					
					
					
					
					if(!empty($json['result']) AND $json['result'] == 1)
					{ 
						// Set Session
						$_SESSION[$sec] = [
							'price'=>$amount ,
							'order_id'=>$order_id ,
							'au'=>$json['au'] ,
						];

									echo "<div style='display:none'>{$json['form']}</div>Please wait ... <script language='javascript'>document.payment.submit(); </script>";

					}
					else
					{
					$message = $msg;
					}

				

				if (!empty($message) && $message) {

					$note = sprintf(__('خطایی رخ داده است: %s', 'woocommerce'), $message);
					$note = apply_filters('WC_sn_Send_to_Gateway_Failed_Note', $note, $order_id, $fault);

					$order->add_order_note($note);

					$notice = sprintf(__('خطایی رخ داده است:<br/>%s', 'woocommerce'), $message);
					$notice = apply_filters('WC_sn_Send_to_Gateway_Failed_Notice', $notice, $order_id, $fault);

					if ($notice) {

						wc_add_notice($notice, 'error');
					}
						
					do_action('WC_sn_Send_to_Gateway_Failed', $order_id, $fault);
				}
			}

			public function Return_from_sn_Gateway()
			{
				global $woocommerce;

				$order_id = null;

									// Security
					$sec=$_GET['sec'];
					$mdback = md5($sec.'vm');
					$mdurl=$_GET['md'];
					// Security
					$transData = $_SESSION[$sec];
					$au=$transData['au']; //	
				    $order_id=$transData['order_id']; //
                    $amount=$transData['price']; //	
				
				if ($trans_id) {

					update_post_meta($order_id, '_transaction_id', $trans_id);
				}

				if (isset($_GET['wc_order'])) {

					$order_id = sanitize_text_field($_GET['wc_order']);

				} elseif ($factor_number) {

					$order_id = $factor_number;

				} else {

					$order_id = $woocommerce->session->order_id_sn;

					unset($woocommerce->session->order_id_sn);
				}

                if ($order_id) {

					$order    = new WC_Order($order_id);
					$currency = $order->get_currency();
					$currency = apply_filters('WC_sn_Currency', $currency, $order_id);

					if ($order->get_status() != 'completed' && $order->get_status() != 'processing') {

						if(!empty($_GET['sec']) AND !empty($_GET['md']) AND !empty($_GET['au'])){

							$api_key = $this->api_key;


							  if($mdback == $mdurl){

								
									$bank_return = $_POST + $_GET ;
									$data_string = json_encode(array (
									'pin' => $api_key,
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
								  
								  
								if( ! empty($json['result']) and $json['result'] == 1){

				   					
										   $order->payment_complete($au);
												$woocommerce->cart->empty_cart();

											$note = sprintf(__('پرداخت موفقیت آمیز بود.<br/>شماره پیگیری: %s', 'woocommerce'), $au);
											$note = apply_filters('WC_sn_Return_from_Gateway_Success_Note', $note, $order_id, $au);

											if ($note) {

												$order->add_order_note($note, 1);
											}

											$notice = wpautop(wptexturize($this->success_massage));
											$notice = str_replace('{transaction_id}', $au, $notice);
											$notice = apply_filters('WC_sn_Return_from_Gateway_Success_Notice', $notice, $order_id, $au);

											if ($notice) {

												wc_add_notice($notice, 'success');
											}

											do_action('WC_sn_Return_from_Gateway_Success', $order_id, $aud);

											wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
											exit;

								} else {

									$message = 'در ارتباط با وب سرویس  خطایی رخ داده است'.$json['msg'];

									$message = isset($result->errorMessage) ? $result->errorMessage : $message;
								}

							} else {

									$message = 'تراكنش با خطا مواجه شد و یا توسط پرداخت کننده کنسل شده است';
								
							}

						} else {

							$message = 'اطلاعات ارسال شده مربوط به تایید تراکنش ناقص و یا غیر معتبر است';
						}

						if ($json['result'] == 1) {

							$order->payment_complete($au);
							$woocommerce->cart->empty_cart();

							$note = sprintf(__('پرداخت موفقیت آمیز بود.<br/>شماره پیگیری: %s', 'woocommerce'), $au);
							$note = apply_filters('WC_sn_Return_from_Gateway_Success_Note', $note, $order_id, $au);

							if ($note) {

								$order->add_order_note($note, 1);
							}
							
							$notice = wpautop(wptexturize($this->success_massage));
							$notice = str_replace('{transaction_id}', $au, $notice);
							$notice = apply_filters('WC_sn_Return_from_Gateway_Success_Notice', $notice, $order_id, $au);

							if ($notice) {

								wc_add_notice($notice, 'success');
							}

							do_action('WC_sn_Return_from_Gateway_Success', $order_id, $au);

							wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
							exit;

						} else {

							$note = sprintf(__('خطایی رخ داده است:<br/>شرح خطا: %s<br/>شماره پیگیری: %s', 'woocommerce'), $message, $trans_id);
							$note = apply_filters('WC_sn_Return_from_Gateway_Failed_Note', $note, $order_id, $trans_id, $fault);

							if ($note) {

								$order->add_order_note($note, 1);
							}

							$notice = wpautop(wptexturize($this->failed_massage));
							$notice = str_replace('{transaction_id}', $trans_id, $notice);
							$notice = str_replace('{fault}', $message, $notice);
							$notice = apply_filters('WC_sn_Return_from_Gateway_Failed_Notice', $notice, $order_id, $trans_id, $fault);

							if ($notice)

							wc_add_notice($notice, 'error');
						
							do_action('WC_sn_Return_from_Gateway_Failed', $order_id, $trans_id, $fault);

							wp_redirect($woocommerce->cart->get_checkout_url());
							exit;
						}

					} else {

						$notice = wpautop(wptexturize($this->success_massage));
						$notice = str_replace('{transaction_id}', $trans_id, $notice);
						$notice = apply_filters('WC_sn_Return_from_Gateway_ReSuccess_Notice', $notice, $order_id, $trans_id);

						if ($notice) {

							wc_add_notice($notice, 'success');
						}

						do_action('WC_sn_Return_from_Gateway_ReSuccess', $order_id, $trans_id);

						wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
						exit;
					}

				} else {

					$fault = __('شماره سفارش وجود ندارد و یا سفارش منقضی شده است', 'woocommerce');

					$notice = wpautop(wptexturize($this->failed_massage));
					$notice = str_replace('{fault}', $fault, $notice);
					$notice = apply_filters('WC_sn_Return_from_Gateway_No_Order_ID_Notice', $notice, $order_id, $fault);

					if ($notice) {

						wc_add_notice($notice, 'error');
					}

					do_action('WC_sn_Return_from_Gateway_No_Order_ID', $order_id, null, $fault);

					wp_redirect($woocommerce->cart->get_checkout_url());
					exit;
				}
			}

			
		}
	}
}

add_action('plugins_loaded', 'Load_sn_Gateway', 0);
