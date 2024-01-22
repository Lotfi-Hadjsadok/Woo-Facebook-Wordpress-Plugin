<?php

namespace Inc;

class FacebookPixelTrigger {

	/**
	 * Fbtrace ID.
	 *
	 * @var string
	 */
	public $event_id;


	public function init() {
		$this->event_id = FacebookCAPI::generate_event_id();
		add_action(
			'wp_head',
			function () {
				$this->pixel_looper( array( $this, 'fb_pixel_base_injector' ) );
			}
		);

		add_action(
			'woocommerce_after_single_product',
			function () {
				$this->pixel_looper( array( $this, 'trigger_product_views' ) );
			}
		);

		add_action(
			'woocommerce_thankyou',
			function ( $order_id ) {
				$this->pixel_looper(
					function ( $pixel ) use ( $order_id ) {
						$this->trigger_purchase( $pixel, $order_id );
					}
				);
			},
			40
		);
	}

	/**
	 * Pixel Looper with callback.
	 *
	 * @param function $fun
	 * @return void
	 */
	public function pixel_looper( $fun ) {
		$pixels = FacebookCAPI::get_fb_pixels();
		if ( ! empty( $pixels ) ) {
			foreach ( $pixels as $pixel ) {
				$fun( $pixel );
			}
		}
	}

	public function trigger_product_views( $pixel ) {
		$product_id = get_the_ID();
		$currency   = get_woocommerce_currency();
		$product    = wc_get_product( $product_id );
		$price      = $product->get_price();
		$event_name = 'ViewContent';

		( new FacebookCAPI( $pixel['pixel_id'], $pixel['conversion_token'], $pixel['conversion_tester'] ) )
			->set_user_data( array(), array() )
			->set_content( $product_id, 1 )
			->set_custom_data( $currency, $price )
			->set_event( $event_name, $this->event_id )
			->set_request()
			->emit_event();

		( new FacebookPixel( $pixel['pixel_id'] ) )->trigger_event(
			$event_name,
			array(
				'value'        => $price,
				'currency'     => $currency,
				'contents'     => json_encode(
					array(
						'id'       => $product_id,
						'quantity' => 1,
					)
				),
				'content_type' => 'product',
			),
			$this->event_id
		);
	}

	public function fb_pixel_base_injector( $pixel ) {
		( new FacebookPixel( $pixel['pixel_id'] ) )->fb_pixel_base();
	}


	public function trigger_purchase( $pixel, $order_id ) {

		$order       = wc_get_order( $order_id );
		$currency    = get_woocommerce_currency();
		$event_name  = 'Purchase';
		$product     = array_values( $order->get_items() )[0]->get_product();
		$price       = $product->get_price();
		$product_id  = $product->get_ID();
		$_60_minutes = time() + 60 * 60;

		$pixel_purchase_product_event_cookie = '_purchase_' . $pixel['pixel_id'] . '_triggered_' . $product_id;

		$pixel_purchase_order_event_transient = $order_id . '_purchase_' . $pixel['pixel_id'] . '_triggered';
		if ( ! $order
        || get_transient($pixel_purchase_order_event_transient)
		|| isset( $_COOKIE[ $pixel_purchase_product_event_cookie ] )
		) {
			return;
		}

		( new FacebookCAPI( $pixel['pixel_id'], $pixel['conversion_token'], $pixel['conversion_tester'] ) )
		->set_user_data( array(), array() )
		->set_content( $product_id, 1 )
		->set_custom_data( $currency, $price )
		->set_event( $event_name, $this->event_id )
		->set_request()
		->emit_event();

		( new FacebookPixel( $pixel['pixel_id'] ) )->trigger_event(
			$event_name,
			array(
				'value'        => $price,
				'currency'     => $currency,
				'contents'     => json_encode(
					array(
						'id'       => $product_id,
						'quantity' => 1,
					)
				),
				'content_type' => 'product',
			),
			$this->event_id
		);

		set_transient( $pixel_purchase_order_event_transient, true, $_60_minutes );
		// Expire in 60 minutes to prevent duplicate purchase event of the same product.
		setcookie( $pixel_purchase_product_event_cookie, true, $_60_minutes,'/' );
	}
}
