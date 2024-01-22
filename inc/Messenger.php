<?php


namespace Inc;

class Messenger {
	public function init() {
		add_action( 'wp_head', array( $this, 'messenger_button_integration' ) );
	}

	public function messenger_button_integration() {
		?>
	<div id="fb-root"></div>
	<div id="fb-customer-chat" class="fb-customerchat"></div>
		<script>
		var chatbox = document.getElementById('fb-customer-chat');
		chatbox.setAttribute("page_id", <?php echo carbon_get_theme_option( 'fb_page_id' ); ?>);
		chatbox.setAttribute("attribution", "biz_inbox");
		</script>

		<!-- Your SDK code -->
		<script>
		window.fbAsyncInit = function() {
			FB.init({
			xfbml            : true,
			version          : 'v18.0'
			});
		};

		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = 'https://connect.facebook.net/ar_AR/sdk/xfbml.customerchat.js';
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
		</script>
		<?php
	}
}
