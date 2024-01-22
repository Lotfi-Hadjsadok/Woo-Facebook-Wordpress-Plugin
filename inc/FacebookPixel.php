<?php
/**
 * Utility Class that handles all the logic related to Facebook Pixel Events emitters.
 */

namespace Inc;

class FacebookPixel extends FacebookCAPI {
	public $pixel_id;
	public function __construct( $pixel_id ) {
		$this->pixel_id = $pixel_id;
	}

	/**
	 * Inject Fb pixel base code.
	 *
	 * @return void
	 */
	public function fb_pixel_base() {
		?>
			<!-- Facebook Pixel Code -->
			<script>
			!function(f,b,e,v,n,t,s)
			{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s)}(window, document,'script',
			'https://connect.facebook.net/en_US/fbevents.js');
			fbq('init', '<?php echo $this->pixel_id; ?>');
			fbq('track','PageView',{},{eventID:"<?php echo $this->generate_event_id(); ?>"})
			</script>
			<!-- End Facebook Pixel Code -->
		<?php
	}


	public function trigger_event( $event_name, $data, $event_id ) {
		?>
		<script>
			fbq('trackSingleCustom',"<?php echo $this->pixel_id; ?>","<?php echo $event_name; ?>",<?php echo json_encode( $data ); ?>,{
				eventID:"<?php echo $event_id; ?>"
			});
		</script>
		<?php
	}
}