<?php
/*
  Plugin Name: NICEPay Virtual Account V2 Payment Gateway
  Plugin URI: http://nicepay.co.id
  Description: NICEPay Virtual Account V2 Payment Gateway
  Version: 5.1
  Author: NICEPay <codeNinja>
  Author URI: http://nicepay.co.id
*/
require('NicepayLibVAV2/NicepayConfigVAV2.php');
use libraryVAV2\Config as VAV2Config;

if (!defined('ABSPATH')) {
    exit;
}

add_action('plugins_loaded', 'woocommerce_nicepay_vav2_init', 0);

function woocommerce_nicepay_vav2_init() {

    // Validation class payment gateway woocommerce
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    class WC_Gateway_NICEPay_VAV2 extends WC_Payment_Gateway {

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
            $this->id = 'nicepay_vav2';
            // Payment Gateway title
            $this->method_title = 'NICEPay Virtual Account PG';
            // true only in case of direct payment method, false in our case
            $this->has_fields = false;
            // payment gateway logo

            $this->icon = plugins_url('/logobank.png', __FILE__);
            // redirect URL
            $this->redirect_url = str_replace('https:', 'http:', add_query_arg('wc-api', 'WC_Gateway_NICEPay_VAV2', home_url('/')));

            //Load settings
            $this->init_form_fields(); // load function init_form_fields()
            $this->init_settings();

            // Define user set variables
            $this->enabled = $this->settings['enabled'];
            $this->title = $this->settings['title']."\r\n";
            $this->description = $this->settings['description'];
            // $this->merchantID = $this->settings['merchantID'];
            $this->XCLIENTKEY = $this->settings['X-CLIENT-KEY'];
            // $this->apikey = $this->settings['apikey'];
            $this->privateKey = $this->settings['privateKey'];
            $this->secretClient = $this->settings['secretClient'];
            $this->mKey = $this->settings['mKey'];
            //---------------------------------------------//
            $this->logoUrl = $this->settings['logoUrl'];
            $this->reduceStock   = $this->get_option( 'reduceStock' );
            //---------------------------------------------//
            $this->bankMandiri = $this->settings['bankMandiri'];
            $this->bankMaybank = $this->settings['bankMaybank'];
            $this->bankPermata = $this->settings['bankPermata'];
            $this->bankBCA = $this->settings['bankBCA'];
            $this->bankBNI = $this->settings['bankBNI'];
            $this->bankKEBHana = $this->settings['bankKEBHana'];
            $this->bankBRI = $this->settings['bankBRI'];
            $this->bankCIMB = $this->settings['bankCIMB'];
            $this->bankDanamon = $this->settings['bankDanamon'];
            $this->bankDKI = $this->settings['bankDKI'];
            $this->bankATMBersama = $this->settings['bankATMBersama'];

            // Actions
            $this->includes();
            // start sequence
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
            // execute receipt_page function as woocommerce_receipt_nicepay_va
            add_action('woocommerce_receipt_nicepay_vav2', array(&$this, 'receipt_page'));
            // execute add_description_payment_success function as woocommerce_thankyou
            add_action('woocommerce_thankyou', array($this, 'add_description_payment_success'), 1);
            // execute add_content function as woocommerce_email_after_order_table (credit card)
            // add_action('woocommerce_email_after_order_table', array($this, 'add_content' ));
            // execute notification_handler function as woocommerce_api_wc_gateway_nicepay_va
            add_action('woocommerce_api_wc_gateway_nicepay_vav2', array($this, 'notification_handler'));
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
                    'default' => __('Pembayaran NICEPay Virtual Payment', 'woocommerce'),
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('', 'woocommerce'),
                    'default' => 'Sistem pembayaran menggunakan NICEPay Virtual Payment.',
                ),
                'X-CLIENT-KEY' => array(
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
                'secretClient' => array(
                  'title' => __('Secret Client Key', 'woocommerce'),
                  'type' => 'text',
                  'description' => __('<small>Isikan dengan Secret Client Key dari NICEPay</small>.', 'woocommerce'),
                  'default' => '33F49GnCMS1mFYlGXisbUDzVf2ATWCl9k3R',
              ),
                'privateKey' => array(
                    'title' => __('Private Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('<small>Isikan dengan Private Key dari NICEPay</small>.', 'woocommerce'),
                    'default' => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAInJe1G22R2fMchIE6BjtYRqyMj6lurP/zq6vy79WaiGKt0Fxs4q3Ab4ifmOXd97ynS5f0JRfIqakXDcV/e2rx9bFdsS2HORY7o5At7D5E3tkyNM9smI/7dk8d3O0fyeZyrmPMySghzgkR3oMEDW1TCD5q63Hh/oq0LKZ/4Jjcb9AgMBAAECgYA4Boz2NPsjaE+9uFECrohoR2NNFVe4Msr8/mIuoSWLuMJFDMxBmHvO+dBggNr6vEMeIy7zsF6LnT32PiImv0mFRY5fRD5iLAAlIdh8ux9NXDIHgyera/PW4nyMaz2uC67MRm7uhCTKfDAJK7LXqrNVDlIBFdweH5uzmrPBn77foQJBAMPCnCzR9vIfqbk7gQaA0hVnXL3qBQPMmHaeIk0BMAfXTVq37PUfryo+80XXgEP1mN/e7f10GDUPFiVw6Wfwz38CQQC0L+xoxraftGnwFcVN1cK/MwqGS+DYNXnddo7Hu3+RShUjCz5E5NzVWH5yHu0E0Zt3sdYD2t7u7HSr9wn96OeDAkEApzB6eb0JD1kDd3PeilNTGXyhtIE9rzT5sbT0zpeJEelL44LaGa/pxkblNm0K2v/ShMC8uY6Bbi9oVqnMbj04uQJAJDIgTmfkla5bPZRR/zG6nkf1jEa/0w7i/R7szaiXlqsIFfMTPimvRtgxBmG6ASbOETxTHpEgCWTMhyLoCe54WwJATmPDSXk4APUQNvX5rr5OSfGWEOo67cKBvp5Wst+tpvc6AbIJeiRFlKF4fXYTb6HtiuulgwQNePuvlzlt2Q8hqQ==',
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
                'bankMandiri' => array(
                    'title' => __('Bank Mandiri', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankMaybank' => array(
                    'title' => __('Bank Maybank', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankPermata' => array(
                    'title' => __('Bank Permata', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankBCA' => array(
                    'title' => __('Bank BCA', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'no',
                ),
                'bankBNI' => array(
                    'title' => __('Bank BNI', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankKEBHana' => array(
                    'title' => __('Bank KEB Hana', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankBRI' => array(
                    'title' => __('Bank BRI', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankCIMB' => array(
                    'title' => __('Bank CIMB Niaga', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                ),
                'bankDanamon' => array(
                    'title' => __('Bank Danamon', 'woocommerce'),
                    'type' => 'checkbox',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default' => 'yes',
                  ),

                    'bankDKI' => array(
                      'title' => __('Bank DKI', 'woocommerce'),
                      'type' => 'checkbox',
                      'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                      'default' => 'yes',
                ),
                'bankATMBersama' => array(
                    'title'       => __('ATM Bersama', 'woocommerce'),
                    'type'        => 'select',
                    'description' => __('<small>Check to enable. Please confirm to NICEPay before enabled this Bank.</small>', 'woocommerce'),
                    'default'     => 'HNBN',
                    'options'     => array(
                        'BMRI' => __('Bank Mandiri', 'woocommerce'),
                        'IBBK' => __('Bank Maybank', 'woocommerce'),
                        'BBBA' => __('Bank Permata', 'woocommerce'),
                        'CENA' => __('Bank BCA', 'woocommerce'),
                        'BNIN' => __('Bank BNI', 'woocommerce'),
                        'HNBN' => __('Bank KEB Hana', 'woocommerce'),
                        'BRIN' => __('Bank BRI', 'woocommerce'),
                        'BNIA' => __('Bank CIMB Niaga', 'woocommerce'),
                        'BDIN' => __('Bank Danamon', 'woocommerce'),
                        'BDKI' => __('Bank DKI', 'woocommerce'),
                    ),
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
            //Set Logo Size
            if ($this->description) {
                ?>
                <style>
                    label[for=payment_method_nicepay_vav2] img{
                        max-height: 4em !important;
                    }
                </style>
                <?php

                echo wpautop(wptexturize($this->description));
                $bank = $this->bank_list();
                echo "Pilih Bank : ";
                echo '<select name="bankCdVAV2">';
                foreach($bank as $key => $val) {
                    if($bank[$key]["enabled"] == "yes") {
                        echo '<option value="'.$key.'">'.$bank[$key]["label"].'</option>';
                    }
                }
                echo '<option value="'.$this->bankATMBersama.'">ATM Bersama</option>';
                echo '</select>';
            }
        }

        // what this function for?
        public function validate_fields() {
            WC()->session->set('bankCd', $_POST["bankCdVAV2"]);
        }

        function bank_list() {
            $bank = array(
                "BMRI" => array(
                    "label" => "Mandiri",
                    "enabled" => $this->bankMandiri,
                ),
                "IBBK" => array(
                    "label" => "Maybank",
                    "enabled" => $this->bankMaybank,
                ),
                "BBBA" => array(
                    "label" => "Permata Bank",
                    "enabled" => $this->bankPermata,
                ),
                "CENA" => array(
                    "label" => "BCA",
                    "enabled" => $this->bankBCA,
                ),
                "BNIN" => array(
                    "label" => "BNI",
                    "enabled" => $this->bankBNI,
                ),
                "HNBN" => array(
                    "label" => "Bank KEB Hana",
                    "enabled" => $this->bankKEBHana,
                ),
                "BRIN" => array(
                    "label" => "BRI",
                    "enabled" => $this->bankBRI,
                ),
                "BNIA" => array(
                    "label" => "CIMB Niaga",
                    "enabled" => $this->bankCIMB,
                ),
                "BDIN" => array(
                    "label" => "Danamon",
                    "enabled" => $this->bankDanamon,
                ),
                "BDKI" => array(
                  "label" => "Bank DKI",
                  "enabled" => $this->bankDKI,
              ),
            );

            return $bank;
        }

        function petunjuk_payment_va($bankCd) {
            $header = '
        <html>
        <body>

        <style>
        input[type="button"]{
          border : 2px solid;
          width : 100%;
        }
        </style>
      ';

            $footer = '
        <script>
        function atm() {
          var div_atm = document.getElementById("div_atm").style.display;
          if(div_atm == "block"){
            document.getElementById("div_atm").style.display = "none";
          }else{
            document.getElementById("div_atm").style.display = "block";
          }
        }
        function ib() {
          var div_ib = document.getElementById("div_ib").style.display;
          if(div_ib == "block"){
            document.getElementById("div_ib").style.display = "none";
          }else{
            document.getElementById("div_ib").style.display = "block";
          }
        }
        function mb() {
          var div_mb = document.getElementById("div_mb").style.display;
          if(div_mb == "block"){
            document.getElementById("div_mb").style.display = "none";
          }else{
            document.getElementById("div_mb").style.display = "block";
          }
        }
        function sms() {
          var div_sms = document.getElementById("div_sms").style.display;
          if(div_sms == "block"){
            document.getElementById("div_sms").style.display = "none";
          }else{
            document.getElementById("div_sms").style.display = "block";
          }
        }
        </script>
        </body>
        </html>
      ';

            $content = null;

            switch ($bankCd) {
                case "BMRI" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Pilih menu Bayar/Beli</li>
        	    <li>Pilih Lainnya</li>
        	    <li>Pilih Multi Payment</li>
        	    <li>Masukan 70014 sebagai Kode Institusi</li>
        	    <li>Masukan virtual account number, contoh. 70014XXXXXXXXXX</li>
        	    <li>Pilih BENAR</li>
        	    <li>Layar akan menampilkan konfirmasi pembayaran</li>
        	    <li>Pilih YA</li>
        	    <li>Periksa jumlah tagihan pembayaran pada halaman konfirmasi</li>
        	    <li>Pilih YA</li>
        	    <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Login Internet Banking</li>
        	    <li>Pilih Bayar</li>
        	    <li>Pilih Multi Payment</li>
        	    <li>Pilih Pembayaran dengan masukan Transferpay sebagai Penyedia Jasa</li>
        	    <li>Masukan nomor virtual account sebagai Kode Bayar, contoh. 70014XXXXXXXXXX</li>
        	    <li>Klik Lanjutkan</li>
        	    <li>Detail pembayaran akan ditampilkan</li>
        	    <li>Beri tanda centang pada tabel Tagihan</li>
        	    <li>Klik Lanjutkan</li>
        	    <li>Masukan PIN Mandiri dengan kode APPLI 1 dari token</li>
        	    <li>Pilih KIRIM</li>
        	    <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Login Mobile Banking</li>
        	    <li>Pilih Bayar</li>
        	    <li>Pilih Lainnya</li>
        	    <li>Pilih Transferpay sebagai Penyedia Jasa</li>
        	    <li>Masukan virtual account number, contoh. 70014XXXXXXXXXX</li>
        	    <li>Pilih Lanjut</li>
        	    <li>Masukan OTP dan PIN</li>
        	    <li>Pilih OK</li>
        	    <li>Nota pembayaran akan ditampilkan</li>
        			<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "IBBK" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Pilih Menu <b>Pembayaran / Top Up Pulsa</b></li>
              <li>Pilih menu transaksi <b>Virtual Account</b></li>
              <li>Masukan nomor Virtual Account, contoh. 7812XXXXXXXXXXX<br/></li>
              <li>Pilih <b>Benar</b></li>
              <li>Pilih <b>YA</b></li>
              <li>Ambil bukti bayar anda</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Internet Banking</li>
              <li>Pilih menu <b>Rekening dan Transaksi</b></li>
              <li>Pilih menu <b>Maybank Virtual Account</b></li>
              <li>Pilih <b>Sumber Tabungan</b></li>
              <li>Masukan nomor Virtual Account, contoh. 7812XXXXXXXXXXXX</li>
              <li>Masukan nominal pembayaran, contoh. 10000</li>
              <li>Klik <b>Submit</b></li>
              <li>Masukan <b>SMS Token</b> atau <b>TAC</b>, kemudian klik Setuju</li>
              <li>Bukti bayar akan ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="sms();" value="Transfer Via SMS Banking"></strong>
          <div id="div_sms" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Mobile Apps</li>
              <li>Pilih menu Transfer</li>
              <li>Pilih menu Virtual Account</li>
              <li>Masukkan Jumlah Nominal Transaksi</li>
              <li>Masukkan Rekening Tujuan dengan nomor Virtual Account, misal 7812XXXXXXXXXXXX</li>
              <li>Klik Kirim</li>
              <li>Masukkan Perintah yang diberikan lewat SMS, kemudian klik Reply</li>
              <li>Bukti bayar akan ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "BBBA" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Pilih menu Transaksi Lainnya</li>
        	    <li>Pilih menu PEMBAYARAN</li>
        	    <li>Pilih Pembayaran Lain - Lain</li>
        	    <li>Pilih Virtual Account</li>
        	    <li>Masukan nomor Virtual Account, contoh : 8625XXXXXXXXXX</li>
        	    <li>Pilih BENAR untuk konfirmasi pembayaran</li>
        	    <li>Pada layar akan tampil konfirmasi pembayaran</li>
        	    <li>Pilih YA untuk konfirmasi pembayaran.</li>
        	    <li>Struk/Bukti transaksi akan keluar</li>
        			<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Login Internet Banking</li>
        	    <li>Pilih menu Pembayaran</li>
        			<li>Pilih sub-menu Pembayaran Tagihan</li>
        			<li>Pilih Virtual Account</li>
        			<li>Pilih Rekening Anda</li>
        	    <li>Masukan nomor virtual account sebagai No. Tagihan, contoh. 8625XXXXXXXXXX</li>
        	    <li>Klik Lanjut</li>
        			<li>Masukkan Jumlah Nominal Tagihan sebagai Total Pembayaran</li>
        			<li>Pilih Submit</li>
        			<li>Masukkan kode sesuai yang dikirimkan melalui SMS ke nomor yang terdaftar</li>
        			<li>Bukti pembayaran akan tampil</li>
        	    <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
        	    <li>Login Mobile Banking</li>
        	    <li>Pilih menu Pembayaran Tagihan</li>
        	    <li>Pilih menu Virtual Account</li>
        			<li>Pilih Tagihan Anda</li>
        			<li>Pilih Daftar Tagihan Baru</li>
        	    <li>Masukan virtual account number, contoh. 8625XXXXXXXXXX sebagai No. Tagihan</li>
        	    <li>Pilih Konfirmasi</li>
        			<li>Masukkan Nama Pengingat</li>
        			<li>Pilih Lanjut</li>
        			<li>Pilih Konfirmasi</li>
        			<li>Masukkan jumlah nominal Tagihan, kemudian konfirmasi</li>
        			<li>Masukkan response Code, kemudian Konfirmasi</li>
        			<li>Bukti pembayaran akan tampil</li>
        	    <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "CENA" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Pilih Menu <b>Transaksi Lainnya</b></li>
              <li>Pilih Menu <b>Transfer</b></li>
              <li>Pilih Ke rekening BCA Virtual Account<br/></li>
              <li>Input Nomor Virtual Account, misal. <b>123456789012XXXX</b></li>
              <li>Pilih <b>Benar</b></li>
              <li>Muncul konfirmasi pembayaran, Pilih <b>Ya</b></li>
              <li>Ambil bukti bayar anda</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Internet Banking</li>
              <li>Pilih menu <b>Transaksi Dana/Fund Transfer</b></li>
              <li>Pilih sub-menu <b>Transfer Ke BCA Virtual Account</b></li>
              <li>Masukkan Nomor Virtual Account, misal.  <br><b>123456789012XXXX</b>sebagai <b>No. Virtual Account</b><br/></li>
              <li>Klik <b>Lanjutkan</b></li>
              <li>Masukkan <b>Respon KeyBCA Appli 1</b></li>
              <li>Klik <b>Submit</b></li>
              <li>Bukti bayar ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Mobile Banking</li>
              <li>Pilih Menu <b>m-Transfer</b></li>
              <li>Pilih Menu <b>BCA Virtual Account</b></li>
              <li>Input Nomor Virtual Account, misal. <br><b>123456789012XXXX</b>sebagai <b>No. Virtual Account</b><br/></li>
              <li>Klik <b>OK</b></li>
              <li>Informasi pembayaran VA akan ditampilkan</li>
              <li>Klik <b>Ok</b></li>
              <li>Input <b>PIN</b> Mobile banking</li>
              <li>Bukti bayar ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "BNIN" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
      				<li>Pilih menu <b>MENU LAIN</b></li>
      				<li>Pilih menu <b>TRANSFER</b></li>
      				<li>Pilih menu <b>DARI REKENING TABUNGAN</b></li>
      				<li>Pilih menu <b>KE REKENING BNI</b></li>
      				<li>Masukkan nomor tujuan dengan nomor virtual account, misal <b>8848XXXXXXXXXXXX</b> kemudian tekan BENAR</li>
      				<li>Masukkan jumlah tagihan kemudian tekan BENAR</li>
      				<li>Pilih <b>YA</b> untuk konfirmasi pembayaran</li>
      				<li>Struk/Bukti transaksi akan tercetak</li>
      				<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
  						<li>Login Internet Banking</li>
  						<li>Pilih menu <b>Transaksi</b></li>
              <li>Pilih menu <b>Info dan Administrasi Transfer</b></li>
              <li>Pilih <b>Atur Rekening Tujuan</b>, kemudian Pilih OK</li>
  						<li>Pilih menu <b>Transfer</b></li>
  						<li>Pilih menu <b>Transfer Antar Rek. BNI</b></li>
  						<li>Lengkapi detail transaksi dengan No. Virtual Account, misal 8848XXXXXXXXXX sebagai rekening Tujuan</li>
  						<li>Pilih Lanjutkan</li>
  						<li>Bukti pembayaran akan ditampilkan</li>
  						<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
							<li>Login Mobile Banking</li>
		  				<li>Pilih menu <b>Transfer</b></li>
              <li>Pilih menu <b>Within Bank</b></li>
							<li>Isi kolom Debit Account, kemudian klik Menu to Account</li>
              <li>Pilih menu <b>Adhoc Beneficiary</b></li>
							<li>Lengkapi Detail dengan Nickname, No. Virtual Account, dan Beneficiary Email Address</li>
							<li>Konfirmasi isi Password, lalu klik Continue</li>
							<li>Detail konfirmasi akan muncul</li>
							<li>Isi Password Transaksi</li>
							<li>Klik Continue</li>
							<li>Bukti pembayaran akan ditampilkan</li>
            	<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="sms();" value="Transfer Via SMS Banking"></strong>
          <div id="div_sms" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
			  			<li>Pilih menu <b>Transfer</b></li>
              <li>Pilih <b>Trf rekening BNI</b></li>
              <li>Masukan nomor virtual account, misal. <b>8848XXXXXXXXXX</b> sebagai No. Rekening Tujuan</li>
              <li>Masukan jumlah tagihan, misal. 10000</li>
              <li>Pilih <b>Proses</b></li>
              <li>Pada pop up message, Pilih <b>Setuju</b></li>
							<li>Anda akan mendapatkan sms konfirmasi</li>
							<li>Masukan 2 angka dari PIN sms banking sesuai petunjuk, kemudian Kirim</li>
            	<li>Bukti pembayaran akan ditampilkan</li>
            	<li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "HNBN" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
            	<li>Pilih Menu <b>Pembayaran</b></li>
            	<li>Pilih Menu <b>Lainnya</b></li>
            	<li>Pilih Menu <b>Virtual Account</b></li>
            	<li>Input Nomor Virtual Account, misal <b>484XXXXXXXXXXXXX</b></li>
            	<li>Informasi pembayaran VA akan ditampilkan</li>
            	<li>Pilih <b>Benar</b></li>
            	<li>Ambil bukti bayar anda</li>
            	<li>Transaksi selesai</li>
            </ul>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
            	<li>Login Internet Banking</li>
            	<li>Pilih Menu <b>Pembayaran</b></li>
            	<li>Pilih Menu <b>Biller Others</b> dan Provider <b>IONPAY</b></li>
            	<li>Masukkan Nomor Virtual Account, misal <b>484XXXXXXXXXXXXX</b></li>
            	<li>Pilih <b>Inquiry</b></li>
            	<li>Pilih No. Rekening yang akan digunakan</li>
            	<li>Masukkan Password di kolom Debit Card Pin No</li>
            	<li>Klik <b>Submit</b></li>
            	<li>Informasi pembayaran VA akan ditampilkan</li>
            	<li>Pilih <b>Submit</b></li>
            	<li>Bukti bayar ditampilkan</li>
            	<li>Transaksi selesai</li>
            </ul>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "BRIN" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
            	<li>Pilih Menu <b>Transaksi Lain</b></li>
            	<li>Pilih Menu <b>Pembayaran</b></li>
            	<li>Pilih Menu <b>Lainnya</b></li>
            	<li>Pilih Menu <b>BRIVA</b></li>
            	<li>Masukkan Nomor Virtual Account, misal. <b>88788XXXXXXXXXXX</b>, kemudian Pilih <b>BENAR</b></li>
            	<li>Informasi pembayaran VA akan ditampilkan</li>
            	<li>Pilih <b>Ya</b></li>
            	<li>Ambil bukti pembayaran Anda dan transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
  						<li>Login Internet Banking</li>
  						<li>Pilih Menu <b>Pembayaran</b></li>
  						<li>Pilih Sub-menu <b>BRIVA</b></li>
  						<li>Masukkan Nomor Virtual Account, misal. <b>88788XXXXXXXXXXX</b>, Klik Kirim</li>
  						<li>Masukkan Password dan mToken Internet banking, Klik Kirim</li>
  						<li>Bukti pembayaran akan ditampilkan dan transaksi selesai</li>
  					</ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
  						<li>Login Mobile Banking</li>
  						<li>Pilih Menu <b>Pembayaran</b></li>
  						<li>Pilih Menu <b>BRIVA</b></li>
  						<li>Masukkan Nomor Virtual Account, misal. <b>88788XXXXXXXXXXX</b></li>
  						<li>Masukkan Nominal misal. 10000</li>
  						<li>Masukkan PIN Mobile, Klik Kirim</li>
  						<li>Bukti pembayaran akan dikirimkan melalui sms dan transaksi selesai</li>
  					</ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "BNIA" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Pilih Menu <b>Pembayaran</b></li>
              <li>Pilih Menu <b>Lanjut</b></li>
              <li>Pilih Menu <b>Virtual Account</b></li>
              <li>Input Nomor Virtual Account, misal. <b>5919XXXXXXXXX</b></li>
              <li>Pilih <b>Proses</b></li>
              <li>Muncul konfirmasi pembayaran, Pilih <b>Proses</b></li>
              <li>Ambil bukti bayar anda</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="ib();" value="Transfer Via Internet Banking"></strong>
          <div id="div_ib" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Internet Banking</li>
              <li>Pilih menu <b>Bayar Tagihan</b></li>
              <li>Pilih Rekening yang ingin digunakan</li>
              <li>Pilih jenis pembayaran <b>Virtual Account</b></li>
              <li>Masukkan Nomor Virtual Account, misal <b>5919XXXXXXXXX</b></li>
              <li>Isi Remark(Jika Diperlukan)</li>
              <li>Pilih <b>Lanjut</b></li>
              <li>Informasi pembayaran VA akan ditampilkan</li>
              <li>Masukkan PIN Mobile</li>
              <li>Pilih <b>Kirim</b></li>
              <li>Bukti bayar ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
              <li>Login Go Mobile</li>
              <li>Pilih Menu <b>Transfer</b></li>
              <li>Pilih Menu <b>Rekening Ponsel/CIMB Niaga Lain</b></li>
              <li>Pilih sumber dana yang ingin digunakan</li>
              <li>Pilih <b>CASA</b></li>
              <li>Input Nomor Virtual Account, misal. <b>5919XXXXXXXXX</b></li>
              <li>Masukkan jumlah nominal tagihan</li>
              <li>Pilih <b>Lanjut</b></li>
              <li>Informasi pembayaran VA akan ditampilkan</li>
              <li>Masukkan PIN Mobile</li>
              <li>Pilih <b>Konfirmasi</b></li>
              <li>Bukti bayar ditampilkan</li>
              <li>Transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;

                case "BDIN" :
                    $body = '
          <strong id="h4thanks"><input type="button" onclick="atm();" value="Transfer Via ATM"></strong>
          <div id="div_atm" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
            	<li>Pilih Menu <b>Pembayaran</b></li>
            	<li>Pilih Menu <b>Lainnya</b></li>
            	<li>Pilih Menu <b>Virtual Account</b></li>
            	<li>Masukkan Nomor Virtual Account, misal. <b>7915XXXXXXXXXXXX</b>, kemudian Pilih <b>BENAR</b></li>
            	<li>Informasi pembayaran VA akan ditampilkan</li>
            	<li>Pilih <b>Ya</b></li>
            	<li>Ambil bukti pembayaran Anda dan transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
          <strong id="h4thanks"><input type="button" onclick="mb();" value="Transfer Via Mobile Banking"></strong>
          <div id="div_mb" style="border:2px solid #cccccc;padding:10px 30px 0; display:none">
            <ul style="list-style-type: disc">
            	<li>Login D-Mobile</li>
            	<li>Pilih Menu <b>Pembayaran</b></li>
            	<li>Pilih Menu <b>Virtual Account</b></li>
            	<li>Pilih <b>Tambah Biller Baru Pembayaran</b></li>
            	<li>Pilih <b>Lanjut</b></li>
            	<li>Masukkan Nomor Virtual Account, misal. <b>7915XXXXXXXXXXXX</b></li>
            	<li>Tekan <b>Ajukan</b></li>
            	<li>Data Virtual Account akan tampil</li>
            	<li>Masukkan <b>mPin</b></li>
            	<li>Pilih <b>Konfirmasi</b></li>
            	<li>Bukti pembayaran akan dikirimkan melalui sms dan transaksi selesai</li>
            </ul>
            <small>*Minimum pembayaran menggunakan Bank Transfer adalah Rp 10.000<br/>*1 (Satu) Nomor Virtual Account hanya berlaku untuk 1 (Satu) Nomor Pesanan</small>
          </div>

          <br />
        ';
                    $content = "$header$body$footer";
                    break;
            }

            return $content;
        }

        function receipt_page($order) {
            echo $this->generate_nicepay_form($order);
        }

        function includes() {
            // Validation class payment gateway woocommerce
            if (!class_exists( 'NicepayLibVAV2' )) {
                include_once "NicepayLibVAV2/NicepayLibVAV2.php";
            }
        }

        function konversi($nilai, $currency) {
            if ($currency == 'USD') {
                return $nilai*(int)13500;
            } else {
                return $nilai*(int)1;
            }
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

                    $item_name = $item->get_name();
                    $item_cost_non_discount = $item->get_subtotal();

                    $pro = new WC_Product($item["product_id"]);
                    $image = wp_get_attachment_image_src( get_post_thumbnail_id( $pro->get_id() ), 'single-post-thumbnail' );
                    $img_url = $image[0];

                    $orderInfo[] = array(
                        'img_url' => $img_url,
                        'goods_name' => $this->nicepay_item_name($item_name),
                        'goods_detail' => $this->nicepay_item_name($item_name),
                        'goods_amt' => $item_cost_non_discount
                    );

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
                    foreach ( $fees as $fees_key => $fee ) {
                        $name = $fee['name'];
                        $total = $fee['total'];
                        if($total != 0){
                            $orderInfo[] = array(
                                'img_url' => "http://www.jamgora.com/media/avatar/noimage.png",
                                'goods_name' => clean($name),
                                'goods_detail' => clean($name)." for ".$order_id,
                                'goods_amt' => wc_float_to_string($total),
                                'goods_quantity' => "1",
                                'goods_url' => "https://".$_SERVER['SERVER_NAME']
                            );
                    }
                }
                }

                if($order->calculate_shipping() > 0){
                    $orderInfo[] = array(
                        'img_url' => "http://www.jamgora.com/media/avatar/noimage.png",
                        'goods_name' => "SHIPPING",
                        'goods_detail' => $order->get_shipping_method(),
                        'goods_amt' => $order->calculate_shipping()
                    );
                }

                $coupons = $order->get_coupon_codes();
                if(!empty($coupons)){
                    foreach( $coupons as $coupon_code ) {
                        $coupon = new WC_Coupon($coupon_code);
                        $couponName = $coupon_code;

                        $orderInfo[] = array(
                            'img_url' => plugins_url()."/nicepay_cc/icons/coupon.png",
                            'goods_name' => "COUPON",
                            'goods_detail' => $couponName,
                            'goods_amt' => "-".$coupon->get_amount()
                        );
                    }
                }

                $cartData = array(
                    'count' => count($orderInfo),
                    'item' => $orderInfo
                );
            }


            //Order Total
            if($currency == 'USD'){
                $order_total = $this->get_order_total()*(int)14852;
            }else{
                $order_total = $this->get_order_total();
            }

            // echo json_decode($order_id);return;
            $payment_page = $order->get_checkout_payment_url();

            //Get current user
            $current_user = wp_get_current_user();

            //Get Address customer
            $billingFirstName = ($current_user->ID == 0) ? $order->billing_first_name : get_user_meta($current_user->ID, "billing_first_name", true);
            $billingLastName = ($current_user->ID == 0) ? $order->billing_last_name : get_user_meta($current_user->ID, "billing_last_name", true);
            $virtualAccountName = $billingFirstName." ".$billingLastName;
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
            $nicepay = new NicepayLibVAV2();

            $dateNow        = date('Ymd');
            $vaExpiryDate   = date('Ymd', strtotime($dateNow . ' +1 day'));
            // Populate Mandatory parameters to send
            // $nicepay->set('payMethod', '02');
            // $nicepay->set('currency', 'IDR');
            // $nicepay->set('cartData', json_encode($cartData));
            // $nicepay->set('amt', $order_total); // Total gross amount //
            // $nicepay->set('referenceNo', $order_id);
            // $nicepay->set('description', 'Payment of invoice No '.$order_id); // Transaction description
            // $nicepay->set("bankCd",WC()->session->get('bankCd'));
            $myaccount_page_id = get_option('woocommerce_myaccount_page_id');
            $myaccount_page_url = null;
            if ($myaccount_page_id) {
                $myaccount_page_url = get_permalink($myaccount_page_id);
            }
            // $nicepay->callBackUrl = $myaccount_page_url."view-order/".$order_id;
            // $nicepay->dbProcessUrl = WC()->api_request_url( 'WC_Gateway_NICEPay_VA' );// Transaction description
            // $nicepay->set('billingNm', $billingNm); // Customer name
            // $nicepay->set('billingPhone', $billingPhone); // Customer phone number
            // $nicepay->set('billingEmail', $billingEmail); //
            // $nicepay->set('billingAddr', $billingAddr);
            // $nicepay->set('billingCity', $billingCity);
            // $nicepay->set('billingState', $billingState);
            // $nicepay->set('billingPostCd', $billingPostCd);
            // $nicepay->set('billingCountry', $billingCountry);
            // $nicepay->set('deliveryNm', $deliveryNm); // Delivery name
            // $nicepay->set('deliveryPhone', $deliveryPhone);
            // $nicepay->set('deliveryEmail', $deliveryEmail);
            // $nicepay->set('deliveryAddr', $deliveryAddr);
            // $nicepay->set('deliveryCity', $deliveryCity);
            // $nicepay->set('deliveryState', $deliveryState);
            // $nicepay->set('deliveryPostCd', $deliveryPostCd);
            // $nicepay->set('deliveryCountry', $deliveryCountry);
            // $nicepay->set('vacctVaildDt', $vaExpiryDate); // Set VA expiry date example: +1 day
            // $nicepay->set('vacctVaildTm', date('His')); // Set VA Expiry Time
            // $nicepay->set('dbProcessUrl', WC()->api_request_url( 'WC_Gateway_NICEPay_VA' ));

            $nicepay->set('privateKey', $this->privateKey);
            $nicepay->set('mKey', $this->mKey);
            $nicepay->set('secretClient', $this->secretClient);
            date_default_timezone_set('Asia/Jakarta');
            $nicepay->set('X-TIMESTAMP', date('c'));
            $nicepay->set('X-CLIENT-KEY', $this->XCLIENTKEY);
            // $nicepay->set('payMethod', '02');
            $nicepay->set('currency', 'IDR');
            $nicepay->set('amt', $order_total);
            $nicepay->set('trxId', $order_id);
           // $nicepay->set('goodsNm', 'Payment of invoice No '.$order_id);
            $nicepay->set('virtualAccountName', $virtualAccountName);
            // $nicepay->set('billingPhone', $billingPhone);
            // $nicepay->set('billingEmail', $billingEmail);
            // $nicepay->set('billingAddr', $billingAddr);
            // $nicepay->set('billingCity', $billingCity);
            // $nicepay->set('billingState', $billingState);
            // $nicepay->set('billingPostCd', $billingPostCd);
            // $nicepay->set('billingCountry', $billingCountry);
            // $nicepay->set('deliveryNm', $deliveryNm);
            // $nicepay->set('deliveryPhone', $deliveryPhone);
            // $nicepay->set('deliveryAddr', $deliveryAddr);
            // $nicepay->set('deliveryCity', $deliveryCity);
            // $nicepay->set('deliveryState', $deliveryState);
            // $nicepay->set('deliveryPostCd', $deliveryPostCd);
            // $nicepay->set('deliveryCountry', $deliveryCountry);
            $nicepay->set('dbProcessUrl', WC()->api_request_url('WC_Gateway_NICEPay_VAV2'));
            $nicepay->set('vat', '0');
            $nicepay->set('fee', '0');
            $nicepay->set('notaxAmt', '0');
            $nicepay->set('description', 'Payment of invoice No '.$order_id.$virtualAccountName);
            // $nicepay->set('merchantToken', $nicepay->merchantToken());
            $nicepay->set('reqDt', date('Ymd'));
            $nicepay->set('reqTm', date('His'));
            // $nicepay->set('reqDomain', '');
            // $nicepay->set('reqServerIP', '');
            // $nicepay->set('reqClientVer', '');
            $nicepay->set('userIP', $nicepay->getUserIP());
            // $nicepay->set('userSessionID', '');
            // $nicepay->set('userAgent', '');
            // $nicepay->set('userLanguage', '');
            $nicepay->set('cartData', json_encode($cartData));
            // $nicepay->set('instmntType', '2'); // Credit Card (CC)
            // $nicepay->set('instmntMon', '1'); // Credit Card (CC)
            // $nicepay->set('recurrOpt', '2'); // Credit Card (CC)
            $nicepay->set('bankCd', WC()->session->get('bankCd')); // Virtual Account (VA)
            $nicepay->set('vacctValidDt', $vaExpiryDate); // Virtual Account (VA)
            $nicepay->set('vacctValidTm', date('His')); // Virtual Account (VA)
            // $nicepay->set('merFixAcctId', ''); // Virtual Account (VA)
            // $nicepay->set('mitraCd', ''); // Convenience Store (CVS)

            unset($nicepay->requestData['privateKey']);
            

            $validate = $nicepay->requestVAV2();
            // Create Access Token
            $X_CLIENT_KEY = $this->XCLIENTKEY;
            $requestToken = VAV2Config::NICEPAY_ACCESS_TOKEN_URL;
            // date_default_timezone_set('Asia/Jakarta');
            // $X_TIMESTAMP = date('c');
            $stringToSign = $X_CLIENT_KEY."|".$nicepay->get('X-TIMESTAMP');
            $privatekey = "-----BEGIN RSA PRIVATE KEY-----" . "\r\n" .
            $this->privateKey . // string private key
            "\r\n" .
            "-----END RSA PRIVATE KEY-----";
            $binary_signature = "";
            $pKey = openssl_pkey_get_private($privatekey);
            
            // print_r($stringToSign);exit();
            openssl_sign($stringToSign, $binary_signature, $pKey, OPENSSL_ALGO_SHA256);
            
            openssl_pkey_free($pKey);
            $signature = base64_encode($binary_signature);
            // print_r($requestToken);exit();
            $jsonData = array(
              "grantType" => "client_credentials",
            "additionalInfo" => ""
          );


            $jsonDataEncode = json_encode($jsonData);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestToken);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncode);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'X-SIGNATURE: '.base64_encode($binary_signature),
                'X-CLIENT-KEY: '.$X_CLIENT_KEY,
                'X-TIMESTAMP: '.$nicepay->get("X-TIMESTAMP")
            ));

            $output = curl_exec($ch);
            $data = json_decode($output);
            $accessToken = $data->accessToken;
            // print_r($data);exit();


            // Create VA
            // $X_CLIENT_KEY = $this->XCLIENTKEY;
            $X_TIMESTAMP = $nicepay->get("X-TIMESTAMP");
            $timestamp = date('YmdHis');
            $CreateVA = VAV2Config::NICEPAY_GENERATE_VA_URL;
            $authorization = "Bearer ".$accessToken;
            $channel = $X_CLIENT_KEY."01";
            $external = $timestamp.rand();
            $partner = $X_CLIENT_KEY;
            $amt = $nicepay->get("amt");
            $secretClient = $this->secretClient;
            // "33F49GnCMS1mFYlGXisbUDzVf2ATWCl9k3R++d5hDd3Frmuos/XLx8XhXpe+LDYAbpGKZYSwtlyyLOtS/8aD7A==";
            $additionalInfo = [
              "bankCd" => $nicepay->get("bankCd"),
              "goodsNm" => $nicepay->get("virtualAccountName"),
              "dbProcessUrl" => $nicepay->get("dbProcessUrl"),
              "vacctValidDt" => $nicepay->get("vacctValidDt"),
              "vacctValidTm" => $nicepay->get("vacctValidTm"),
              "msId" => "",
              "msFee" => "",
              "msFeeType" => "",
              "mbFee" => "",
              "mbFeeType" => ""
            ];
            // print_r($additionalInfo);exit();

            $TotalAmount = [
              "value"=> $nicepay->get("amt").".00",
              "currency" => $nicepay->get("currency")
            ];


            $newBody = [
              "partnerServiceId" => "",
              "customerNo" => "",
              "virtualAccountNo"=>"",
              "virtualAccountName"=> $nicepay->get("virtualAccountName"),
              "trxId"=> $nicepay->get("trxId")."",
              "totalAmount" => $TotalAmount,
              "additionalInfo" => $additionalInfo
            ];
            // print_r($newBody);exit();
            // print_r($nicepay);exit();
            // $data = $nicepay->requestVAV2();
            
            
            $stringBody = json_encode($newBody);
            // print_r($stringBody);exit();
            
            // $body = '{"partnerServiceId":"",
            //   "customerNo":"",
            //   "virtualAccountNo":"",
            //   "virtualAccountName":"John Doe",
            //   "trxId":"2022020100000000000001",
            //   "totalAmount":{"value":"10000.00","currency":"IDR"},
            //   "additionalInfo":
            //   {"bankCd":"BMRI",
            //     "goodsNm":"John Doe",
            //     "dbProcessUrl": "https://nicepay.co.id/",
            //     "vacctValidDt":"",
            //     "vacctValidTm":"",
            //     "msId":"",
            //     "msFee":"",
            //     "msFeeType":"",
            //     "mbFee":"",
            //     "mbFeeType":""}}';
            $hashbody = strtolower(hash("SHA256", $stringBody));

            // print_r($CreateVA);exit();

            $strigSign = "POST:/api/v1.0/transfer-va/create-va:".$accessToken.":".$hashbody.":".$X_TIMESTAMP;
            $bodyHasing = hash_hmac("sha512", $strigSign, $secretClient, true);
            // echo base64_encode($bodyHasing);exit();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$CreateVA);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt ($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $stringBody);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'X-SIGNATURE: '.base64_encode($bodyHasing),
            'X-CLIENT-KEY: '.$X_CLIENT_KEY,
            'X-TIMESTAMP: '.$X_TIMESTAMP,
            'Authorization: '.$authorization,
            'CHANNEL-ID: '.$channel,
            'X-EXTERNAL-ID: '.$external,
            'X-PARTNER-ID: '.$X_CLIENT_KEY
        ));

      $output = curl_exec($ch);
      $data = json_decode($output);
      // $responseMessage = $data->responseMessage;
      // $customerNo = $data->virtualAccountData->customerNo;
      // $virtualAccountNo = $data->virtualAccountData->virtualAccountNo;
      // $virtualAccountName = $data->virtualAccountData->virtualAccountName;
      // $trxId = $data->virtualAccountData->trxId;
      // $totalAmount = $data->virtualAccountData->totalAmount->value;
      // $bankCd = $data->virtualAccountData->additionalInfo->bankCd;
      // $tXidVA = $data->virtualAccountData->additionalInfo->tXidVA;
      // $goodsNm = $data->virtualAccountData->additionalInfo->goodsNm;
      // $vacctValidDt = $data->virtualAccountData->additionalInfo->vacctValidDt;
      // $vacctValidTm = $data->virtualAccountData->additionalInfo->vacctValidTm;
      // print_r($data);exit();

            // Send Data
            // $validate = $nicepay->requestVAV2();
            // print_r($data);exit();
            // Response from NICEPAY
            if (isset($data->responseCode) && $data->responseCode == "2002700") {

                //REDUCE WC Stock
                if ( property_exists($this,'reduceStock') && $this->reduceStock == 'yes' ) {
                  // wc_reduce_stock_levels($order_id);
                  wc_maybe_reduce_stock_levels($order_id);
              }

                $order->add_order_note(__('Menunggu pembayaran melalui NICEPay Virtual Payment Gateway dengan id transaksi '.$data->tXid, 'woocommerce'));
                $bank = $this->bank_list();

                $vacctValidDt = date("Y/m/d", strtotime($data->virtualAccountData->additionalInfo->vacctValidDt));
                $vacctValidTm = date("H:m:s", strtotime($data->virtualAccountData->additionalInfo->vacctValidTm));
                $newDate = $vacctValidDt.' '.$vacctValidTm;
                // print_r($newDate);exit();
                // response
                WC()->session->set('resultCd', $data->responseCode);
                WC()->session->set('resultMsg', $data->responseMessage);
                WC()->session->set('tXid', $data->virtualAccountData->additionalInfo->tXidVA);
                WC()->session->set('trxId', $data->virtualAccountData->trxId); // referenceNo
                // WC()->session->set('payMethod', $response->payMethod);
                WC()->session->set('amt', $data->virtualAccountData->totalAmount->value);
                WC()->session->set('currency', $data->currency);
                WC()->session->set('goodsNm', $data->virtualAccountData->additionalInfo->goodsNm);
                WC()->session->set('virtualAccountName', $data->virtualAccountData->virtualAccountName);
                // WC()->session->set('transDt', $response->transDt);
                // WC()->session->set('transTm', $response->transTm);
                WC()->session->set('description', $nicepay->get("description"));
                WC()->session->set('bankCd', $data->virtualAccountData->additionalInfo->bankCd);
                WC()->session->set('bankName', $bank[$data->virtualAccountData->additionalInfo->bankCd]["label"]); // bankCd
                WC()->session->set('vacctNo', $data->virtualAccountData->virtualAccountNo);
                WC()->session->set('vacctValidDt', $data->virtualAccountData->additionalInfo->vacctValidDt);
                WC()->session->set('vacctValidTm', $data->virtualAccountData->additionalInfo->vacctValidTm);
                // WC()->session->set('mitraCd',$response->mitraCd); // Convenience Store (CVS)
                // WC()->session->set('payNo',$response->payNo); // Convenience Store (CVS)
                // WC()->session->set('payValidDt',$response->payValidDt); // Convenience Store (CVS)
                // WC()->session->set('payValidTm',$response->payValidTm); // Convenience Store (CVS)

                // set custom data
                WC()->session->set('expDate',$newDate);

                $dataTr = array(
                    // response
                    "referenceNo" => $data->trxId,
                    "amt" => $data->virtualAccountData->totalAmount->value,
                    "billingNm" => $data->virtualAccountData->virtualAccountName,
                    "bankCd" => $data->virtualAccountData->additionalInfo->bankCd,
                    "vacctNo" => $data->virtualAccountData->virtualAccountNo,
                    "vacctValidDt" => $data->virtualAccountData->additionalInfo->vacctValidDt,
                    "vacctValidTm" => $data->virtualAccountData->additionalInfo->vacctValidTm,
                    // "mitraCd" => $response->mitraCd, // // Convenience Store (CVS)
                    // "payNo" => $response->payNo, // // Convenience Store (CVS)
                    // "payValidDt" => $response->payValidDt, // // Convenience Store (CVS)
                    // "payValidTm" => $response->payValidTm, // // Convenience Store (CVS)
                    // add custom data
                    "bankName" => $bank[$data->virtualAccountData->additionalInfo->bankCd]["label"],
                    "expDate" => $newDate,
                    "user_name" => $virtualAccountName,
                    "shop_name" => get_option('blogname'),
                    "shop_logo" => $this->logoUrl,
                    "shop_url" => get_permalink( wc_get_page_id( 'shop' ) ),
                    "order_name" => "#".$data->virtualAccountData->trxId,
                );

                $this->add_payment_detail_to_order_email($dataTr);

                header("Location: ".$this->get_return_url($order));
                // please save tXid in your database
                // echo "<pre>";
                // echo "tXid              : $response->tXid\n";
                // echo "API Type          : $response->apiType\n";
                // echo "Request Date      : $response->requestDate\n";
                // echo "Response Date     : $response->requestDate\n";
                // echo "</pre>";
            } elseif(isset($data->responseCode)) {
                // API data not correct or error happened in bank system, you can redirect back to checkout page or echo error message.
                // In this sample, we echo error message
                // header("Location: "."http://example.com/checkout.php");
                echo "<pre>";
                echo "result code: ".$data->responseCode."\n";
                echo "result message: ".$data->responseMessage."\n";
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
            if ($order->get_payment_method() == 'nicepay_vav2') {
                echo '<h2 id="h2thanks">NICEPAY Secure Payment</h2>';
                echo '<table border="0" cellpadding="10">';
                echo '<tr><td><strong>Deskripsi</strong></td><td>'.WC()->session->get('description').'</td></tr>';
                echo '<tr><td><strong>Bank</strong></td><td>'.WC()->session->get('bankName').'</td></tr>';
                echo '<tr><td><strong>Virtual Account</strong></td><td>'.WC()->session->get('vacctNo').'</td></tr>';
                echo '<tr><td><strong>Pembayaran berakhir pada</strong></td><td>'.WC()->session->get('expDate').'</td></tr>';
                echo '</table>';
                echo '<p>Pembayaran melalui Bank Transfer '.WC()->session->get('bankName').' dapat dilakukan dengan mengikuti petunjuk berikut :</p>';
                echo  $this->petunjuk_payment_va(WC()->session->get('bankCd'));
            }
        }

        //Add details to Processing Order email
        public function np_add_payment_email_detail($orderdata, $sent_to_admin, $plain_text, $email){
            $order = new WC_Order($orderdata);
            if ($order->get_payment_method() == 'nicepay_vav2') {
                if ( $email->id == 'customer_processing_order' ) {
                    echo '<h2 class="email-upsell-title">We Have Received Your Payment!</h2>';
                }
            }
        }

        //Change Email Subject for Processing Email
        public function np_change_subject_payment_email_detail($formated_subject, $orderdata){
            $order = new WC_Order($orderdata);
            if ($order->get_payment_method() == 'nicepay_vav2') {
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
                $subject = sprintf( 'Hi %s, Thank you for your payment on %s', $order->billing_first_name, $blogname );
                return $subject;
            }
        }

        public function modify_fee_html_for_taxes( $cart_fee_html, $fees ) {

            if ( 'incl' === get_option( 'woocommerce_tax_display_cart' ) && isset( $fees->tax ) && $fees->tax > 0 && in_array( $fees->name, $this->fees_added, true ) ) {
                $cart_fee_html .= '<small class="includes_tax">' . sprintf( __( '(includes %s Tax)', 'checkout-fees-for-woocommerce' ), wc_price( $fees->tax ) ) . '</small>'; // phpcs:ignore
            }
            return $cart_fee_html;
        }

        public function add_payment_detail_to_order_email($session) {
            $order = new WC_Order($session['referenceNo']);
            //IF GUEST
            $email_to = $order->get_billing_email();
            $admin_email = get_option( 'admin_email' );
            $blogname = get_option( 'blogname' );
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'From: '.$blogname.' <'.$admin_email.'> ' . "\r\n";
            $subject = "Detail Pembayaran order #".$session['referenceNo'];
            $message = $this->petunjuk_payment_va_to_email($session);
            //FINAL SEND
            wp_mail($email_to, $subject, $message, $headers);
        }

        function petunjuk_payment_va_to_email($data) {
            $content = null;
            $bankCd = $data["bankCd"];

            $opts = array(
                'http'=>array(
                    'method'=>"POST",
                    'content' => http_build_query($data),
                )
            );

            $context = stream_context_create($opts);

            switch ($bankCd) {
                case "BMRI" :
                    $content = file_get_contents(plugins_url("email/order_conf_mandiri.php", __FILE__), false, $context);
                    break;

                case "IBBK" :
                    $content = file_get_contents(plugins_url("email/order_conf_bii.php", __FILE__), false, $context);
                    break;

                case "BBBA" :
                    $content = file_get_contents(plugins_url("email/order_conf_permata.php", __FILE__), false, $context);
                    break;

                case "CENA" :
                    $content = file_get_contents(plugins_url("email/order_conf_bca.php", __FILE__), false, $context);
                    break;

                case "BNIN" :
                    $content = file_get_contents(plugins_url("email/order_conf_bni.php", __FILE__), false, $context);
                    break;

                case "HNBN" :
                    $content = file_get_contents(plugins_url("email/order_conf_bersama.php", __FILE__), false, $context);
                    break;

                case "BRIN" :
                    $content = file_get_contents(plugins_url("email/order_conf_bri.php", __FILE__), false, $context);
                    break;

                case "BNIA" :
                    $content = file_get_contents(plugins_url("email/order_conf_cimb_niaga.php", __FILE__), false, $context);
                    break;

                case "BDIN" :
                    $content = file_get_contents(plugins_url("email/order_conf_danamon.php", __FILE__), false, $context);
                    break;

                case "BDKI" :
                    $content = file_get_contents(plugins_url("email/order_conf_BankDKI.php", __FILE__), false, $context);
                    break;

                case "BDKI" :
                    $content = file_get_contents(plugins_url("email/order_conf_BankBNC.php", __FILE__), false, $context);
                    break;
            }

            return $content;
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

            $nicepay = new NicepayLibVAV2();
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
          $status = $nicepay->getNotification('status');
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
            // Listen for parameters passed
            // $pushParameters = array(
            //     'transDt',
            //     'transTm',
            //     'tXid',
            //     'trxId',
            //     'amt',
            //     'merchantToken',
            //     'bankCd',
            //     'status',
            //     'goodsNm',

            // );

            // $nicepay->extractNotification($pushParameters);

            // $transDt = $nicepay->getNotification('transDt');
            // $transTm = $nicepay->getNotification('transTm');
            // $timeStamp = $transDt.$transTm;
            // $XCLIENTKEY = $this->XCLIENTKEY;
            // $tXid = $nicepay->getNotification('tXid');
            // $trxId = $nicepay->getNotification('referenceNo');
            // $amt = $nicepay->getNotification('amt');
            // $privateKey = $this->privateKey;
            // $pushedToken = $nicepay->getNotification('merchantToken');
            // $status = $nicepay->getNotification('status');
            // $mKey = $this->mKey;

            // $nicepay->set('timeStamp', $timeStamp);
            // $nicepay->set('X-CLIENT-KEY', $XCLIENTKEY);
            // $nicepay->set('tXid', $tXid);
            // $nicepay->set('trxId', $trxId);
            // $nicepay->set('amt', $amt);
            // $nicepay->set('privateKey', $privateKey);
            // $nicepay->set('mKey', $mKey);

            // $merchantToken = $nicepay->merchantToken();
            // $nicepay->set('merchantToken', $merchantToken);

            // <RESQUEST to NICEPAY>
            // $paymentStatus = $nicepay->checkPaymentStatus($timeStamp, $XCLIENTKEY, $trxId, $amt, $mKey);

            if($pushedToken == $merchantToken) {
                $order = new WC_Order((int)$referenceNo);
                if ($status =='0') {
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
 

    function add_nicepay_vav2_gateway($methods) {
        $methods[] = 'WC_Gateway_NICEPay_VAV2';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_nicepay_vav2_gateway');

}
?>