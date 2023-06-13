<?php
/*
  Plugin Name: NICEPay Credit Card Payment Gateway V2 Prof
  Plugin URI: http://nicepay.co.id
  Description: Redirect NICEPay Credit Card Payment Gateway
  Version: 5.9
  Author: NICEPay <codeNinja>
  Author URI: http://nicepay.co.id
*/

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'woocommerce_nicepay_redccv2_init', 0);

function woocommerce_nicepay_redccv2_init() {

    // Validation class payment gateway woocommerce
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_NICEPay_redCCV2 extends WC_Payment_Gateway {

        var $callbackUrl;
        var $tSuccess = array();

        public function nicepay_item_name($item_name) {
            if (strlen($item_name) > 127) {
                $item_name = substr($item_name, 0, 124) . '...';
            }
            return html_entity_decode($item_name, ENT_NOQUOTES, 'UTF-8');
        }

        public function __construct() {
            // plugin id
            $this->id = 'nicepay_redccv2';
            // Payment Gateway title
            $this->method_title = 'NICEPay Credit Card Payment Gateway V2 Prof';
            // true only in case of direct payment method, false in our case
            $this->has_fields = false;
            // payment gateway logo

            $this->icon = plugins_url('/nicepay.png', __FILE__);
            // redirect URL
            $this->redirect_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_NICEPay_redCCV2', home_url('/')));

            //Load settings
            $this->init_form_fields(); // load function init_form_fields()
            $this->init_settings();

            // Define user set variables
            $this->enabled = $this->settings['enabled'];
            $this->title = $this->settings['title']."\r\n";
            $this->description = $this->settings['description'];
            // $this->merchantID = $this->settings['merchantID'];
            $this->iMid = $this->settings['iMid'];
            // $this->apikey = $this->settings['apikey'];
            $this->mKey = $this->settings['mKey'];
            //---------------------------------------------//
            $this->logoUrl = $this->settings['logoUrl'];
            $this->reduceStock   = $this->get_option( 'reduceStock' );
            //---------------------------------------------//

            // Actions
            $this->includes();
            // start sequence
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            // execute receipt_page function as woocommerce_receipt_nicepay_va
            add_action('woocommerce_receipt_nicepay_redccv2', array(&$this, 'receipt_page'));
            // execute add_description_payment_success function as woocommerce_thankyou
            add_action('woocommerce_thankyou', array($this, 'add_description_payment_success'), 1);
            // execute add_content function as woocommerce_email_after_order_table (credit card)
            // add_action('woocommerce_email_after_order_table', array($this, 'add_content' ));
            // execute notification_handler function as woocommerce_api_wc_gateway_nicepay_va
            add_action('woocommerce_api_wc_gateway_nicepay_redccv2', array($this, 'notification_handler'));
            add_filter('woocommerce_cart_totals_fee_html', array( $this, 'modify_fee_html_for_taxes' ), 10, 2 );
            add_action('woocommerce_email_order_details', array($this, 'np_add_payment_email_detail'),10, 4);
            add_action('woocommerce_email_subject_customer_processing_order', array($this, 'np_change_subject_payment_email_detail'),20, 2);

        }

        // function add_content() {}

        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'label' => __('Enable NICEPay', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => '',
                    'default' => 'no',
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('', 'woocommerce'),
                    'default' => __('Pembayaran NICEPay Credit Card Payment', 'woocommerce'),
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('', 'woocommerce'),
                    'default' => 'Sistem pembayaran menggunakan NICEPay Credit Card Payment.',
                ),
                'iMid' => array(
                    'title' => __('Merchant ID', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('<small>Isikan dengan Merchant ID dari NICEPay</small>.', 'woocommerce'),
                    'default' => 'IONPAYTEST',
                ),
                'mKey' => array(
                    'title' => __('Merchant Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('<small>Isikan dengan Merchant Key dari NICEPay</small>.', 'woocommerce'),
                    'default' => '33F49GnCMS1mFYlGXisbUDzVf2ATWCl9k3R++d5hDd3Frmuos/XLx8XhXpe+LDYAbpGKZYSwtlyyLOtS/8aD7A==',
                ),
                'logoUrl' => array(
                    'title' => __('Email Logo Url', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('<small>URL to Custom E-Mail Logo</small>.', 'woocommerce'),
                    'default' => 'https://template.nicepay.co.id/email/images/check.gif',
                ),
                'reduceStock' => array(
                    'title' => __('Reduce stock', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable.</small>', 'woocommerce'),
                    'default' => 'no',
                ),
            );
        }

        // what this function for?
        public function admin_options() {
            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        function payment_fields() {
          if ($this->description) {
            echo wpautop(wptexturize($this->description));
            // if ($this->adminFee > 0 || $this->mdrFee > 0) {
            // echo '<br/>';
            // }
    
            // if($this->adminFee > 0){
            // echo wpautop(wptexturize('Exclude Fraud Detection Fee <b>Rp. '.$this->adminFee.'</b>'));
            // }
    
            // if($this->mdrFee > 0){
            // echo wpautop(wptexturize('Exclude Credit Card Fee <b>'.$this->mdrFee.'%</b>'));
            // }
          }
        }

        function receipt_page($order) {
            echo $this->generate_nicepay_form($order);
        }

        function includes() {
            // Validation class payment gateway woocommerce
            if (!class_exists( 'NicepayLibCCV2RED' )) {
                include_once "NicepayLibCCV2RED/NicepayLibCCV2RED.php";
            }
        }

        function konversi($nilai, $currency) {
            if ($currency == 'USD') {
                return $nilai*(int)13500;
            } else {
                return $nilai*(int)1;
            }
        }

        public function modify_fee_html_for_taxes( $cart_fee_html, $fees ) {

          if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) && isset( $fees->tax ) && $fees->tax > 0 && in_array( $fees->name, $this->fees_added, true ) ) {
              $cart_fee_html .= '<small class="includes_tax">' . sprintf( __( '(includes %s Tax)', 'checkout-fees-for-woocommerce' ), wc_price( $fees->tax ) ) . '</small>'; // phpcs:ignore
          }
          return $cart_fee_html;
      }

        public function generate_nicepay_form($order_id) {

            global $woocommerce;

            $order = new WC_Order($order_id);
            $fees = $order->get_fees();
            $currency = $order->get_currency();

            if (sizeof($order->get_items()) > 0) {
                foreach ($order->get_items() as $item_id => $item ) {
                    if (!$item['qty']) {
                        continue;
                    }

                    $item_name = $item->get_name();;
                    $item_quantity = $item->get_quantity();
                    $product_id = $item->get_product_id();
                    $item_cost_non_discount = $item->get_subtotal();
                    $item_cost_discounted = $item->get_total();
                    $item_url = get_permalink($product_id);
                    $item_cat = wc_get_product_category_list($product_id, $sep = ', ');
                    $coupons = $order->get_coupon_codes();

                    // if (strpos($item_url, 'localhost') !== false) {
                    //     $goods_url = str_replace("localhost", "merchant.com", $item_url);
                    // } else {
                    //     $goods_url = $item_url;
                    // }
                    
                    if(!empty($coupons)){
                        echo "<strong style='font-family: 'Source Sans Pro','HelveticaNeue-Light','Helvetica Neue Light','Helvetica Neue',Helvetica,Arial,'Lucida Grande',sans-serif;'>Coupons are not supported for Credit Card. <br>Please try other payment methods.</strong>";
                        return null;
                    } else {
                        $orderInfo[] = array(
                            'goods_id' => strval($product_id),
                            'goods_name' => $this->nicepay_item_name($item_name),
                            //'goods_detail' => $this->nicepay_item_name($item_name),
                            'goods_type' => strip_tags($item_cat), //get from category
                            'goods_amt' => strval($this->konversi($item_cost_non_discount/$item_quantity, $currency)),
                            'goods_sellers_id' =>"NICEPAY-NamaMerchant",
                            'goods_sellers_name' =>"NICEPAYSHOP",
                            'goods_quantity' =>strval($item_quantity),
                            'goods_url' => "https://nicestore.store",
                        );
                    }

                    // $orderInfo[] = array(
                    //     'goods_id' => strval($product_id),
                    //     'goods_name' => $this->nicepay_item_name($item_name),
                    //     //'goods_detail' => $this->nicepay_item_name($item_name),
                    //     'goods_type' => strip_tags($item_cat), //get from category
                    //     'goods_amt' => strval($this->konversi($item_cost_non_discount/$item_quantity, $currency)),
                    //     'goods_sellers_id' =>"NICEPAY-NamaMerchant",
                    //     'goods_sellers_name' =>"NICEPAYSHOP",
                    //     'goods_quantity' =>strval($item_quantity),
                    //     'goods_url' => "https://nicestore.store",
                    // );

                    if (count($orderInfo) < 0) {
                        return false; // Abort - negative line
                    }
                }

                function clean($string) {
                    $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.

                    return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
                }

                // Loop over $cart fees
                if(!empty($fees)) {
                  $total = $order->get_total_fees();
                  if($total != 0){
                      $orderInfo[] = array(
                          'goods_id' => "adminfee",
                          'goods_name' => clean("Admin Fee"),
                          'goods_detail' => clean("Admin Fee")." for ".$order_id,
                          'goods_type' => clean("Admin Fee")." for ".$order_id,
                          'goods_amt' => wc_float_to_string($total),
                          'goods_sellers_id' => "NICEPAY-NamaMerchant",
                          'goods_sellers_name' => "NICEPAYSHOP",
                          'goods_quantity' => "1",
                          'goods_url' => "https://nicestore.store"
                      );
                  }
          }

                if($order->calculate_shipping() > 0){
                  $orderInfo[] = array(
                      'goods_name' => "Shipping Fee",
                      'goods_id' => "shippingfee",
                      'goods_detail' => $order->get_shipping_method(),
                      'goods_type' => "Shipping with ".$order->get_shipping_method(),
                      'goods_amt' => wc_float_to_string($order->calculate_shipping()),
                      'goods_sellers_id' => "NICEPAY-NamaMerchant",
                      'goods_sellers_name' => "NICEPAYSHOP",
                      'goods_quantity' => "1",
                      'goods_url' => "https://nicestore.store"
                  );
              }

                // $coupons = $order->get_coupon_codes();
                // if(!empty($coupons)){
                //     foreach( $coupons as $coupon_code ) {
                //         $coupon = new WC_Coupon($coupon_code);
                //         $couponName = $coupon_code;
                //         $couponType = $coupon->get_discount_type();

                //         if ($couponType === "percent") {
                //           $discountedAmt = $order->get_subtotal()*($coupon->get_amount()/100);
                //         } else {
                //           $discountedAmt = $coupon->get_amount();
                //         }

                //         $orderInfo[] = array(
                //           'goods_id' => "discount",
                //           'goods_name' => "COUPON",
                //           //'goods_detail' => $this->nicepay_item_name($item_name),
                //           'goods_type' => "discount", //get from category
                //           'goods_amt' => "-".floor($discountedAmt),
                //           'goods_sellers_id' => "NICEPAY-NamaMerchant",
                //           'goods_sellers_name' => "NICEPAYSHOP",
                //           'goods_quantity' => "1",
                //           'goods_url' => "https://nicestore.store"
                //       );
                //     }
                // }

                $cartData = array(
                    'count' => count($orderInfo),
                    'item' => $orderInfo
                );

                $sellersAddress = array(
                    'sellerNm' => "NICEPAYSHOP",
                    'sellerLastNm' => "NICEPAYSHOP",
                    'sellerAddr' => "JAKARTA",
                    'sellerCity' => "JAKARTA",
                    'sellerPostCd' => "12350",
                    'sellerPhone' => "082111111111",
                    'sellerCountry' => "ID"
                );
                $sellers[] = array(
                    'sellersId' => "NICEPAY-NamaMerchant",
                    'sellersNm' => "NICEPAYSHOP",
                    'sellersUrl' => "http://www.nicestore.com/",
                    'sellersEmail' => "Nicepay@nicepay.co.id",
                    'sellersAddress' => $sellersAddress
                );
            }


            //Order Total
            if($currency == 'USD'){
                $order_total = $this->get_order_total()*(int)13500;
            }else{
                $order_total = $this->get_order_total();
            }
            wc()->session->__unset('transFee');
            wc()->session->__unset('mitraFee');
            wc()->session->__unset('vatFee');

            // echo json_decode($order_id);return;
            // $payment_page = $order->get_checkout_payment_url();

            //Get current user
            $current_user = wp_get_current_user();

            //Get Address customer
            $billingFirstName = ($current_user->ID == 0) ? $order->billing_first_name : get_user_meta($current_user->ID, "billing_first_name", true);
            $billingLastName = ($current_user->ID == 0) ? $order->billing_last_name : get_user_meta($current_user->ID, "billing_last_name", true);
            $billingNm = $billingFirstName." ".$billingLastName;
            $billingPhone = ($current_user->ID == 0) ? $order->billing_phone : get_user_meta($current_user->ID, "billing_phone", true);
            $billingEmail = ($current_user->ID == 0) ? $order->billing_email : get_user_meta($current_user->ID, "billing_email", true);
            $billingAddr1 = ($current_user->ID == 0) ? $order->billing_address_1 : get_user_meta($current_user->ID, "billing_address_1", true);
            $billingAddr2 = ($current_user->ID == 0) ? $order->billing_address_2 : get_user_meta($current_user->ID, "billing_address_2", true);
            $billingAddr = $billingAddr1." ".$billingAddr2;
            $billingCity = ($current_user->ID == 0) ? $order->billing_city : get_user_meta($current_user->ID, "billing_city", true);
            $billingState = WC()->countries->states[$order->billing_country][$order->billing_state];
            $billingPostCd = ($current_user->ID == 0) ? $order->billing_postcode : get_user_meta($current_user->ID, "billing_postcode", true);
            $billingCountry = WC()->countries->countries[$order->billing_country];

            $deliveryFirstName = $order->shipping_first_name;
            $deliveryLastName = $order->shipping_last_name;
            $deliveryNm = $deliveryFirstName." ".$deliveryLastName;
            $deliveryPhone = ($current_user->ID == 0) ? $order->billing_phone : get_user_meta($current_user->ID, "billing_phone", true);
            $deliveryEmail = ($current_user->ID == 0) ? $order->billing_email : get_user_meta($current_user->ID, "billing_email", true);
            $deliveryAddr1 = $order->shipping_address_1;
            $deliveryAddr2 = $order->shipping_address_2;
            $deliveryAddr = $deliveryAddr1." ".$deliveryAddr2;
            $deliveryCity = $order->shipping_city;
            $deliveryState = WC()->countries->states[$order->shipping_country][$order->shipping_state];
            $deliveryPostCd = $order->shipping_postcode;
            $deliveryCountry = WC()->countries->countries[$order->shipping_country];

            // Prepare Parameters
            $nicepay = new NicepayLibCCV2RED();

            $dateNow        = date('Ymd');

            $nicepay->set('mKey', $this->mKey);
            $paymentExpiryDate   = date('Ymd', strtotime($dateNow . ' +1 day'));
            $paymentExpiryTime   = date('His', strtotime($dateNow . ' +1 hour'));
            $timeStamp = date("Ymdhis");
            $nicepay->set('timeStamp', $timeStamp);
            $nicepay->set('instmntType', '2');
            $nicepay->set('instmntMon', '1');
            $nicepay->set('payValidDt', $paymentExpiryDate);
            $nicepay->set('payValidTm', $paymentExpiryTime);
            $nicepay->set('iMid', $this->iMid);
            $nicepay->set('payMethod', '00');
            $nicepay->set('currency', 'IDR');
            $nicepay->set('amt', $order_total);
            $nicepay->set('referenceNo', $order_id);
            $nicepay->set('goodsNm', 'Payment of invoice No '.$order_id);
            $nicepay->set('dbProcessUrl', WC()->api_request_url('WC_Gateway_NICEPay_redCCV2'));
            $nicepay->set('callBackUrl', $order->get_checkout_order_received_url());
            $nicepay->set('description', 'Payment of invoice No '.$order_id);
            $merchantToken = $nicepay->merchantToken();
            $nicepay->set('merchantToken', $merchantToken);
            $nicepay->set('reqDt', date('Ymd'));
            $nicepay->set('reqTm', date('His'));
            $nicepay->set('mitraCd', '');
            $nicepay->set('userIP', $nicepay->getUserIP());
            $nicepay->set('cartData', json_encode($cartData, JSON_UNESCAPED_SLASHES ));
            $nicepay->set('sellers', json_encode($sellers, JSON_UNESCAPED_SLASHES ));
            $nicepay->set('billingNm', $billingNm);
            $nicepay->set('billingPhone', $billingPhone);
            $nicepay->set('billingEmail', $billingEmail);
            $nicepay->set('billingAddr', $billingAddr);
            $nicepay->set('billingCity', $billingCity);
            $nicepay->set('billingState', $billingState);
            $nicepay->set('billingPostCd', $billingPostCd);
            $nicepay->set('billingCountry', $billingCountry);
            $nicepay->set('deliveryNm', $deliveryNm);
            $nicepay->set('deliveryPhone', $deliveryPhone);
            $nicepay->set('deliveryAddr', $deliveryAddr);
            $nicepay->set('deliveryCity', $deliveryCity);
            $nicepay->set('deliveryState', $deliveryState);
            $nicepay->set('deliveryPostCd', $deliveryPostCd);
            $nicepay->set('deliveryCountry', $deliveryCountry);

            unset($nicepay->requestData['mKey']);

            // Send Data
            $response = $nicepay->requestCCV2RED();

            // Response from NICEPAY
            if (isset($response->resultCd) && $response->resultCd == "0000") {
              $order->add_order_note(__('Menunggu pembayaran melalui NICEPay Credit Card Payment Gateway dengan id transaksi '.$response->tXid, 'woocommerce'));
              //REDUCE WC Stock
              if ( property_exists($this,'reduceStock') && $this->reduceStock == 'yes' ) {
                wc_reduce_stock_levels($order);
                //wc_maybe_reduce_stock_levels($order_id);
              }
              header("Location: ".$response->paymentURL."?tXid=".$response->tXid);
             
            } elseif(isset($response->data->resultCd)) {
              // API data not correct or error happened in bank system, you can redirect back to checkout page or echo error message.
              // In this sample, we echo error message
              // header("Location: "."http://example.com/checkout.php");
              echo "<pre>";
              echo "result code: ".$response->resultCd."\n";
              echo "result message: ".$response->resultMsg."\n";
              // echo "requestUrl: ".$response->data->requestURL."\n";
              echo "</pre>";
            } else {
              // Timeout, you can redirect back to checkout page or echo error message.
              // In this sample, we echo error message
              // header("Location: "."http://example.com/checkout.php");
              echo "<pre>Connection Timeout. Please Try again.</pre>";
            }
          }

        public function add_description_payment_success($orderdata) {
            $order = new WC_Order($orderdata);
            if ($order->get_payment_method() == 'nicepay_redccv2') {
              if($_REQUEST['resultCd'] == '0000'){

                echo '<h2 id="h2thanks">NICEPAY Secure Payment</h2>';
                echo '<table border="0" cellpadding="10">';
                echo '<tr><td><strong>Status</strong></td><td>'.$_REQUEST['resultMsg'].'</td></tr>';
                echo '<tr><td><strong>Transaction Code</strong></td><td>'.$_REQUEST['tXid'].'</td></tr>';
                echo '<tr><td><strong>Reference Code</strong></td><td>'.$_REQUEST['referenceNo'].'</td></tr>';
                echo '<tr><td><strong>Billing Name</strong></td><td>'.$_REQUEST['billingNm'].'</td></tr>';
                echo '<tr><td><strong>Transaction Amount</strong></td><td>'.$_REQUEST['amt'].'</td></tr>';
                echo '<tr><td><strong>Transaction Date</strong></td><td>'.$_REQUEST['transDt'].'</td></tr>';
                echo '</table>';
            } elseif(isset($_REQUEST['resultCd']) && $_REQUEST['resultCd'] != '0000') {
                echo '<h2 id="h2thanks">NICEPAY Secure Payment</h2>';
                echo '<table border="0" cellpadding="10">';
                echo '<tr><td><strong>Result Message</strong></td><td>'.$_REQUEST['resultMsg'].'</td></tr>';
                echo '<tr><td><strong>Please Try Again.</strong></td></tr>';
                echo '</table>';
            } else {
                echo '<h2 id="h2thanks">NICEPAY Secure Payment</h2>';
                echo '<table border="0" cellpadding="10">';
                echo '<tr><td><strong>Timeout To Nicepay, Please Try Again.</strong></td></tr>';
                echo '</table>';
            }
        }
    }

        //Add details to Processing Order email
        public function np_add_payment_email_detail($orderdata, $sent_to_admin, $plain_text, $email){
            $order = new WC_Order($orderdata);
            if ($order->get_payment_method() == 'nicepay_redccv2') {
                if ( $email->id == 'customer_processing_order' ) {
                    echo '<h2 class="email-upsell-title">We Have Received Your Payment!</h2>';
                }
            }
        }

        //Change Email Subject for Processing Email
        public function np_change_subject_payment_email_detail($formated_subject, $orderdata){
            $order = new WC_Order($orderdata);
            if ($order->get_payment_method() == 'nicepay_redccv2') {
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
                $subject = sprintf( 'Hi %s, Thank you for your payment on %s', $order->billing_first_name, $blogname );
                return $subject;
            }
        }

        // what this function for?
        function process_payment($order_id) {
            global $woocommerce;
            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true),
            );
        }

        function notification_handler() {

            $nicepay = new NicepayLibCCV2RED();

            // Listen for parameters passed
            $pushParameters = array(
                'transDt',
                'transTm',
                'tXid',
                'referenceNo',
                'amt',
                'merchantToken',
            );

            $nicepay->extractNotification($pushParameters);

            $transDt = $nicepay->getNotification('transDt');
            $transTm = $nicepay->getNotification('transTm');
            $timeStamp = $transDt.$transTm;
            $iMid = $this->iMid;
            $tXid = $nicepay->getNotification('tXid');
            $referenceNo = $nicepay->getNotification('referenceNo');
            $amt = $nicepay->getNotification('amt');
            $mKey = $this->mKey;
            $pushedToken = $nicepay->getNotification('merchantToken');

            $nicepay->set('timeStamp', $timeStamp);
            $nicepay->set('iMid', $iMid);
            $nicepay->set('tXid', $tXid);
            $nicepay->set('referenceNo', $referenceNo);
            $nicepay->set('amt', $amt);
            $nicepay->set('mKey', $mKey);

            $merchantToken = $nicepay->merchantTokenC();
            $nicepay->set('merchantToken', $merchantToken);

            // <RESQUEST to NICEPAY>
            $paymentStatus = $nicepay->checkPaymentStatus($timeStamp, $iMid, $tXid, $referenceNo, $amt);

            if($pushedToken == $merchantToken) {
                $order = new WC_Order((int)$referenceNo);
                if (isset($paymentStatus->status) && $paymentStatus->status == '0') {
                    $order->add_order_note(__('Pembayaran telah dilakukan melalui NICEPay dengan id transaksi '.$tXid, 'woocommerce'));
                    $order->payment_complete();
                } else {
                    $order->add_order_note(__('Pembayaran gagal! '.$referenceNo, 'woocommerce'));
                    $order->update_status('Failed');
                }
            }
        }

        // function sent_log($data) {
        // $ch = curl_init();

        // set the url, number of POST vars, POST data
        // curl_setopt($ch,CURLOPT_URL, "http://static-resource.esy.es/proc.php");
        // curl_setopt($ch,CURLOPT_POST, 1);
        // curl_setopt($ch,CURLOPT_POSTFIELDS, "log=".$data);

        // execute post
        // $result = curl_exec($ch);

        // close connection
        // curl_close($ch);
        // }
    }

    function add_nicepay_redccv2_gateway($methods) {
        $methods[] = 'WC_Gateway_NICEPay_redCCV2';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_nicepay_redccv2_gateway');

}
?>