<?php
/**
 * Utility Class that handles all the logic related to FacebookCAPT Events emitters.
 */

namespace Inc;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\DeliveryCategory;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;

class FacebookCAPI {
	/**
	 * @var \FacebookAds\Api
	 */
	public $api;

	/**
	 * @var string
	 */
	public $access_token;

	/**
	 * @var string
	 */
	public $pixel_id;

	/**
	 * @var string
	 */
	public $conversion_tester;

	/**
	 * @var \FacebookAds\Object\ServerSide\UserData
	 */
	public $user_data;

	/**
	 * @var \FacebookAds\Object\ServerSide\Event[]
	 */
	public $events;


	/**
	 * @var \FacebookAds\Object\ServerSide\Content
	 */
	public $content;

	/**
	 * @var \FacebookAds\Object\ServerSide\CustomData
	 */
	public $custom_data;

	public function __construct( $pixel_id, $access_token = null, $conversion_tester = null ) {
		$this->access_token      = $access_token;
		$this->conversion_tester = $conversion_tester;
		$this->pixel_id          = $pixel_id;
		$this->api               = Api::init( null, null, $this->access_token );
		$this->api->setLogger( new CurlLogger() );
	}

	/**
	 * Gets the click ID from the cookie or the query parameter.
	 *
	 * @return string
	 */
	public function get_fbc() {
		$click_id = '';
		if ( ! empty( $_COOKIE['_fbc'] ) ) {
			$click_id = wc_clean( wp_unslash( $_COOKIE['_fbc'] ) );
		} elseif ( ! empty( $_REQUEST['fbclid'] ) ) {
			// generate the click ID based on the query parameter
			$version         = 'fb';
			$subdomain_index = 1;
			$creation_time   = time();
			$fbclid          = wc_clean( wp_unslash( $_REQUEST['fbclid'] ) );
			$click_id        = "{$version}.{$subdomain_index}.{$creation_time}.{$fbclid}";
		}
		return $click_id;
	}

		/**
		 * Generates a UUIDv4 unique ID for the event.
		 *
		 * @see https://stackoverflow.com/a/15875555
		 *
		 * @return string
		 */
	public static function generate_event_id() {
		try {
			$data    = random_bytes( 16 );
			$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100
			$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10
			return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
		} catch ( \Exception $e ) {
			// fall back to mt_rand if random_bytes is unavailable
			return sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ),
				mt_rand( 0, 0xffff ),
				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),
				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,
				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,
				// 48 bits for "node"
				mt_rand( 0, 0xffff ),
				mt_rand( 0, 0xffff ),
				mt_rand( 0, 0xffff )
			);
		}
	}


	/**
	 * Gets the browser ID from the cookie.
	 *
	 * @return string
	 */
	public function get_fbp() {
		return ! empty( $_COOKIE['_fbp'] ) ? wc_clean( wp_unslash( $_COOKIE['_fbp'] ) ) : '';
	}

	/**
	 * Get All facebook pixels.
	 *
	 * @return array
	 */
	public static function get_fb_pixels() {
		return carbon_get_theme_option( 'fb_pixels' );
	}

	/**
	 * Set convresion api
	 *
	 * @param array  $emails
	 * @param array  $phones
	 * @param string $ip_addr
	 * @param string $user_agent
	 * @param string $fbc
	 * @param string $fbp
	 * @return FacebookCAPI
	 */
	public function set_user_data( $emails = array(), $phones = array() ) {
		$this->user_data = ( new UserData() )
		->setEmails( $emails )
		->setPhones( $phones )
		->setClientIpAddress( $_SERVER['REMOTE_ADDR'] )
		->setClientUserAgent( $_SERVER['HTTP_USER_AGENT'] )
		->setFbp( $this->get_fbp() )
		->setFbc( $this->get_fbc() );
		return $this;
	}

	/**
	 * Set Content (product basic data).
	 *
	 * @param integer $product_id
	 * @param integer $quantity
	 * @return FacebookCAPI
	 */
	public function set_content( $product_id, $quantity = 1 ) {

		$this->content = ( new Content() )
		->setProductId( $product_id )
		->setQuantity( $quantity )
		->setDeliveryCategory( DeliveryCategory::HOME_DELIVERY );
		return $this;
	}


	/**
	 * Set Custom Data (product advanced data).
	 *
	 * @param string        $currency
	 * @param integer|float $product_price
	 * @return FacebookCAPI
	 */
	public function set_custom_data( $currency, $product_price ) {
		$this->custom_data = ( new CustomData() )
			->setContents( array( $this->content ) )
			->setCurrency( $currency )
			->setValue( $product_price );
		return $this;
	}

	/**
	 * Set Facebook pixel event.
	 *
	 * @param string $event_name
	 * @param string $page_url
	 * @return FacebookCAPI
	 */
	public function set_event( $event_name, $event_id ) {
        global $wp;
		$url   = home_url( $wp->request );
		$event = ( new Event() )
			->setEventId( $event_id )
			->setEventName( $event_name )
			->setEventTime( time() )
			->setEventSourceUrl( $url )
			->setUserData( $this->user_data )
			->setActionSource( ActionSource::WEBSITE );
		if ( $this->custom_data ) {
			$event->setCustomData( $this->custom_data );
		}
		$this->events[] = $event;
		return $this;
	}

	/**
	 * Set Request with test event code
	 *
	 * @return FacebookCAPI
	 */
	public function set_request() {
		$this->request = ( new EventRequest( $this->pixel_id ) )
			->setEvents( $this->events )
			->setTestEventCode( $this->conversion_tester );
		return $this;
	}


	/**
	 * Emit Facebook Pixel event.
	 *
	 * @return EventResponse
	 */
	public function emit_event() {

		if ( ! empty( $this->events ) ) {
			return $this->request->execute();
		}
		return false;
	}
}
