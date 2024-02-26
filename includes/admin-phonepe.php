<?php
 /**
 * Phonepe Standard Gateway
 *
 * @package     RPRESS
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2018, MagniGenie
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Phonepe Remove CC Form
 *
 * Phone pe Standard does not need a CC form, so remove it.
 *
 * @access private
 * @since 1.0
 */
add_action( 'rpress_phonepe_cc_form', '__return_false' );

/**
 * Register the Phonepe Standard gateway subsection
 *
 * @since  1.0
 * @param  array $gateway_sections  Current Gateway Tab subsections
 * @return array                    Gateway subsections with PayPal Standard
 */
function rpress_register_phonepe_gateway_section( $gateway_sections ) {
	$gateways = rpress_get_option( 'gateways' );
    if( isset( $gateways['phonepe'] ) ){
        $gateway_sections['phonepe'] = __( 'PhonePe', 'rp-phonepe' );
    }
	return $gateway_sections;
}

add_filter( 'rpress_settings_sections_gateways', 'rpress_register_phonepe_gateway_section', 1, 1 );

/**
 * Registers the  PhonePe Standard settings for the PhonePe Standard subsection
 *
 * @since  1.0
 * @param  array $gateway_settings  Gateway tab settings
 * @return array                    Gateway tab settings with the PhonePe Standard settings
 */
function rpress_register_phonepe_gateway_settings( $gateway_settings ) {

	$phonepe_settings = array (
		'phonepe_settings' => array(
			'id'   => 'phonepe_settings',
			'name' => '<strong>' . __( 'PhonePe   Settings', 'rp-phonepe' ) . '</strong>',
			'type' => 'header',
		),
		'phoneoe_test_merchant_id' => array(
			'id'   => 'phoneoe_test_merchant_id',
			'name' =>   __( ' Phonepe test merchant Id', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		'phoneoe_test_api_key' => array(
			'id'   => 'phoneoe_test_api_key',
			'name' =>   __( 'Phonepe test api key', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		'phoneoe_test_api_key_index' => array(
			'id'   => 'phoneoe_test_api_key_index',
			'name' =>   __( 'Phonepe test api key / salt key index', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		'phoneoe_merchant_id' => array(
			'id'   => 'phoneoe_merchant_id',
			'name' =>   __( ' Phonepe merchant Id', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		'phoneoe_api_key' => array(
			'id'   => 'phoneoe_api_key',
			'name' =>   __( 'Phonepe api key', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		'phoneoe_api_key_index' => array(
			'id'   => 'phoneoe_api_key_index',
			'name' =>   __( 'Phonepe api key / salt key index', 'rp-phonepe' )  ,
			'type' => 'text',
			'size' => 'regular',
		),
		 
	);	 
	$phonepe_settings            = apply_filters( 'rpress_phonepe_settings', $phonepe_settings );
	$gateway_settings['phonepe'] = $phonepe_settings;

	return $gateway_settings;
}
add_filter( 'rpress_settings_gateways', 'rpress_register_phonepe_gateway_settings', 1, 1 );


function set_phonepe_default_dropdown($gateways){
    $gateways['phonepe'] = array(
        'admin_label'    => __( 'PhonePe', 'restropress' ),
        'checkout_label' => __( 'PhonePe', 'restropress' )
    );
    return $gateways;
}
add_filter( 'rpress_payment_gateways', 'set_phonepe_default_dropdown', 1, 1 );

 
/**
 * Process PhonePe Purchase
 *
 * @since 1.0
 * @param array   $purchase_data Purchase Data
 * @return void
 */
function rpress_process_phonepe_purchase( $purchase_data ) {

	// Collect payment data
	$payment_data = array(
		'price'         => $purchase_data['price'],
		'date'          => $purchase_data['date'],
		'user_email'    => $purchase_data['user_email'],
		'purchase_key'  => $purchase_data['purchase_key'],
		'currency'      => rpress_get_currency(),
		'fooditems'     => $purchase_data['fooditems'],
		'user_info'     => $purchase_data['user_info'],
		'cart_details'  => $purchase_data['cart_details'],
		'gateway'       => 'phonepe',
		'status'        => ! empty( $purchase_data['buy_now'] ) ? 'private' : 'pending'
	);
	$payment = rpress_insert_payment( $payment_data );
	$test_mode = rpress_get_option( 'test_mode' );

	if ( ! $payment ) {
 		rpress_record_gateway_error( __( 'Payment Error', 'rp-phonepe' ), sprintf( __( 'Payment creation failed before sending buyer to PhonePe. Payment data: %s', 'rp-phonepe' ), json_encode( $payment_data ) ), $payment );
 		rpress_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['rpress-gateway'] );
	}
	else{
		$merchant_id = rpress_get_option( 'phoneoe_merchant_id' );
		$salt_key = rpress_get_option( 'phoneoe_api_key' );
		$endpoint = 'https://api.phonepe.com/apis/hermes/pg/v1/pay';
		$salt_key_index = rpress_get_option( 'phoneoe_api_key_index' );
		$transaction_id      = "Rp".time(); 
		if( $test_mode ){
			$merchant_id = rpress_get_option( 'phoneoe_test_merchant_id' );
			$salt_key = rpress_get_option( 'phoneoe_test_api_key' );
			$endpoint = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';
			$salt_key_index = rpress_get_option( 'phoneoe_test_api_key_index' );
		}

		$customer_id = get_post_meta( $payment,'_rpress_payment_customer_id',true );
		 
		$call_backurl        =get_site_url().'?order_id='.$payment.'&transaction_id='.$transaction_id;

		$resirect_url = add_query_arg( array(
			'payment-confirmation' => 'phonepe',
			'payment-id' => $payment,
			'order_id'=>$payment,
			'transaction_id'=>$transaction_id,

		), get_permalink( rpress_get_option( 'success_page', false ) ) );


		 /*
		* Array with parameters for API interaction
		*/
		$data = array(

			"merchantId" 	    => sanitize_text_field( $merchant_id ),
			"merchantTransactionId"	    =>  sanitize_text_field( $transaction_id ) ,
			"amount"            => sanitize_text_field( $purchase_data['price']*100 ),// in paisa
			"email"             => sanitize_email( $purchase_data['user_email'] ),
			"merchantUserId"    =>  sanitize_text_field( $customer_id ), 
			"redirectUrl"       =>  $resirect_url,
			"redirectMode"      =>  "REDIRECT",
			"callbackUrl"       => $call_backurl,
			"paymentInstrument"    =>  array( 'type'=> "PAY_PAGE" )  
		);


		$jsonencode = json_encode( $data );
		$payloadMain = base64_encode( $jsonencode );
		$payload = $payloadMain . "/pg/v1/pay" . $salt_key;
		$sha256 = hash( "sha256", $payload );
		$final_x_header = $sha256 . '###' . $salt_key_index;
		$request = json_encode( array( 'request'=>$payloadMain ) );
		$curl = curl_init();
		curl_setopt_array( $curl, [
		CURLOPT_URL => $endpoint,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => $request,
		CURLOPT_HTTPHEADER => [
			"Content-Type: application/json",
			"X-VERIFY: " . $final_x_header,
			"accept: application/json"
		],
		] );

		$response = curl_exec( $curl );
		$err = curl_error( $curl );
		
    	curl_close( $curl );
		if( !is_wp_error( $response ) ) {
		$res = json_decode( $response );
         if( isset( $res->success ) && $res->success=='1' ){
        	$paymentCode=$res->code;
         
            $paymentMsg=$res->message;
            $payUrl=$res->data->instrumentResponse->redirectInfo->url;
			wp_redirect( $payUrl );
			exit;
        }
	
	   } else {
 		   return;
	   }		 
	}	 
}
add_action( 'rpress_gateway_phonepe', 'rpress_process_phonepe_purchase' );


/**
 * Process PhonePe Purchase
 *
 * @since 1.0
 * @param array   $purchase_data Purchase Data
 * @return void
 */
function rpress_check_pay_status(){
 
 	$order_id = isset($_REQUEST['order_id']) ? sanitize_text_field($_REQUEST['order_id']) : null;
    if (is_null($order_id)) return;

    $transaction_id = isset($_REQUEST['transaction_id']) ? sanitize_text_field($_REQUEST['transaction_id']) : null;
    if (is_null($transaction_id)) return;

	$payment_status = rpress_get_payment_status( $order_id, true );
	if ( $payment_status =='paid') return;

	

	$merchant_id = rpress_get_option( 'phoneoe_merchant_id' );
	$salt_key = rpress_get_option( 'phoneoe_api_key' );
	$endpoint =  'https://api.phonepe.com/apis/hermes/pg/v1/status/'.$merchant_id."/".$transaction_id; 
	$salt_key_index = rpress_get_option( 'phoneoe_api_key_index' );
 	$test_mode = rpress_get_option( 'test_mode' );
	if( $test_mode ){
		$merchant_id = rpress_get_option( 'phoneoe_test_merchant_id' );
		$salt_key = rpress_get_option( 'phoneoe_test_api_key' );
		$endpoint = 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/status/'.$merchant_id."/".$transaction_id;
		$salt_key_index = rpress_get_option( 'phoneoe_test_api_key_index' );
	}

	$xvarify = hash('sha256', "/pg/v1/status/" . $merchant_id  . "/" . $transaction_id.$salt_key)."###" . $salt_key_index; 
	$options = array(
        'method'      =>    'GET',
        'sslverify'   =>    false,
        'user-agent'  =>    'woo-plugin',
        'cookies'     => array(),
        'headers'     => array(
            'Content-Type'          => 'application/json',
            'X-VERIFY'              => $xvarify,
            'X-MERCHANT-ID' => $merchant_id,
        ),
      );
    
	 $response = wp_remote_get( $endpoint,$options );
	  
     $body = json_decode( $response['body'], true );
	 if( $body['success']==true ){
        // Payment transaction is successfull.
        if ( $body['code'] == 'PAYMENT_SUCCESS' ) {
            rpress_update_payment_status( $order_id, 'publish' );			
            rpress_empty_cart();
        }
        }else{
        
      }  

}

add_action( 'init', 'rpress_check_pay_status' );








 