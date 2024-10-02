# NICEPAY WooCommerce - Wordpress Payment Gateway Module

WooCommerce NICEPAY!
Receive online payment on your WooCommerce store with NICEPAY payment gateway integration plugin.

### Description

This plugin will allow secure online payment on your WooCommerce store, without your customer ever need to leave your WooCommerce store!

WooCommerce-NICEPAY is official plugin from NICEPAY. NICEPAY is an online payment gateway. We strive to make payments simple & secure for both the merchant and customers. Support various online payment channel.

### Overview Paymethod : 
Our Woocommerce Library already support on Each paymethod, this library support 

*SNAP
- E-Wallet SNAP.
- Virtual Account SNAP.
- QRIS SNAP.
- Payout (Disbursment) SNAP'

*NON SNAP
- Credit Card
- Virtual Account
- CVS
- Payloan
- Ewallet
- QRIS

## 1. Installation
### 1.1 Minimum Requirements

- WordPress v3.9 or greater (tested up to v5.x)
- WooCommerce v2 or greater (tested up to v3.5.2)

### 1.2 Manual Installation
download plugin in (https://github.com/nicepay-dev/plugs-libs/tree/master/WooCommerce)
 - go to plugin menu on Wordpress
 - chose file in your drive and instal plugin
 - select and active plugin.

1. [Download](../../archive/main.zip) the plugin from this repository.
2. Extract the plugin, then rename the folder modules as **woocommerce-NICEPAY**.
3. Choose menu Plugin.
4. Choose sub-menu Tambah Baru.
5. Click button instal / unggah Plugin.
6. Click button Aktifkan Plugin for using plugin.
7. Install & Activate the plugin from the Plugins menu within the WordPress admin panel.
8. Go to menu **WooCommerce > Settings > Checkout > NICEPAY**, fill the configuration fields.
   - Fill in the **Client ID**, **Client Secret**, **MID**, **Merchant Key** & **Private Key** with your corresonding [NICEPAY&nbsp; account](https://bo.nicepay.co.id/) credentials
   - Click tick Enable
   - Click Save Changes
   - Note: key for Development enviroment & Production enviroment is different, make sure you use the correct one.
   - Other configuration are optional, you may leave it as is.
## 2. Usage
### 2.1 Client Initialization and Configuration

Please follow [this step by step guide](https://docs.nicepay.co.id/woocommerce) for complete configuration.
Configure it from WooCommerce > Settings > Payment > Nicepay paymethod > Manage under configuration field WC Order Status on Payment Paid.

Mandatory parameter on option configure : 
- X-CLIENT-KEY = value Merchant ID from NICEPay, 
- privateKey = value Key private, 
- Client Secret = value client secret.

### 2.2 Access Token
 ```php
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
            
            // print_r($X_CLIENT_KEY);exit();
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
            $AcToken = json_decode($output);
            $accessToken = $AcToken->accessToken;
            // print_r($AcToken);exit();
            WC()->session->set('accessToken', $AcToken->responseCode);
```
### 2.3 Generate VA :
```php
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
            
            
            $stringBody = json_encode($newBody);
            // print_r($stringBody);exit();
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
```
#### Notes
Please Instal Wordpress and Woocomerce before instal Plugin 
This library is meant to be implemented on your backend server using PHP.

#### Get help

- [SNAP-Woocommerce Wiki](https://docs.nicepay.co.id/woocommerce)
- [SNAP documentation](https://docs.nicepay.co.id)
- Can't find answer you looking for? email to [cs@nicepay.co.id](mailto:cs@nicepay.co.id)
