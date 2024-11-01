<?php
/**
 * ZT_ED_Frontend_Action Class
 *
 * Zestard Easy Donation the Frontend functionality.
 *
 * @package WordPress
 * @subpackage Zestard Easy Donation Frontend
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ZT_ED_Frontend_Action' ) ) {

	/**
	 * The ZT_ED_Frontend_Action Class
	 */
	class ZT_ED_Frontend_Action {

		function __construct() {

			global $wpdb, $input_field_data;

			$table_name = $wpdb->prefix . 'zted_donation_config';
			$donation_table_data = $wpdb->get_results("SELECT * FROM $table_name");

			if(!empty($donation_table_data)){
				$this->dashboard_info_data 	= json_decode($donation_table_data[0]->dashboard_info);
				$this->email_editor_content	= $donation_table_data[0]->email_content;
				$this->email_suite			= json_decode($donation_table_data[0]->email_templates);
				$this->cart_status_val 		= esc_html__($this->dashboard_info_data->cart->cart_status, ZT_ED_TEXTDOMAIN);
				$this->checkout_status_val 	= esc_html__($this->dashboard_info_data->checkout->checkout_status, ZT_ED_TEXTDOMAIN);
				$this->checkout_inner_status= esc_html__($this->dashboard_info_data->checkout->after_checkout_form, ZT_ED_TEXTDOMAIN);
				
				$donate_now_button = esc_html__($this->dashboard_info_data->donation_button, ZT_ED_TEXTDOMAIN);
			}
			//Create donations cookie
			if (isset($_POST['zted_donation_submit_action']))  {  

				$selected_donation_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['donation_name'] ) );

				//Selected donation details
				$selected_donation_arr = array();
				foreach ($selected_donation_data as $selected_donation_key => $selected_donation_value) {
					$selected_donation_arr[$selected_donation_key] = sanitize_text_field($selected_donation_value);
				}

				foreach ($selected_donation_arr as $selected_donation_arr_key => $selected_donation_arr_value) {
					setcookie($selected_donation_arr_key."_form", sanitize_text_field($_POST['zted_submit_donation_val']), time()+1800, "/","", 0); 
					setcookie($selected_donation_arr_key, sanitize_text_field($selected_donation_arr[$selected_donation_arr_key]), time()+1800, "/","", 0);
				}

		        // refresh current page
		        header('Location: ' . $_SERVER['REQUEST_URI']);
		        exit;
			}

			if (isset($_POST['zted_donation_remove_action'])) {
				
				//Remove set cookie
			    $remove_selected_donation = array_map( 'sanitize_text_field', wp_unslash( $_POST['donation_name'] ) );

			    $remove_donation_arr = array();
				foreach ($remove_selected_donation as $remove_selected_donation_key => $remove_selected_donation_value) {
					$remove_donation_arr[$remove_selected_donation_key] = sanitize_text_field($remove_selected_donation_value);
				}
			    
			   	foreach ($remove_donation_arr as $remove_donation_key => $remove_donation_value) {
			   		unset($_COOKIE[$remove_donation_key.'_form']);
			   		setcookie($remove_donation_key.'_form', null, -1, '/'); 
			   		unset($_COOKIE[$remove_donation_key]);
			   		setcookie($remove_donation_key, null, -1, '/'); 
				}
				// refresh current page
		        header('Location: ' . $_SERVER['REQUEST_URI']);
		        exit;
		        
			}

			add_action ('wp_head', array($this, 'zted_donation_frontend_style'));
			add_action( 'woocommerce_proceed_to_checkout', array($this, 'zted_add_donation_on_cart_page_data'));
			add_action( 'woocommerce_cart_calculate_fees', array($this, 'zted_prefix_add_discount_line') );
			add_action( 'woocommerce_before_checkout_form', array($this, 'zted_add_donation_on_checkout_page'));

			add_action('woocommerce_after_checkout_form', array($this, 'zted_add_donation_before_order_notes'));

			add_action( 'woocommerce_thankyou', array($this, 'zted_donation_email_thankyou') );
			/* Donation frontend additional script code */
			add_action('wp_footer', array($this, 'zted_donation_frontend_script'));
		}

		/**
		* Function for displaying post's accordion on frontend 
		**/

		public function donation_details_data(){
			$this->input_field_data = [];
			$posttype_data = get_posts(array(
			    'post_type'   => 'donation_option',
			    'post_status' => 'publish',
			    'posts_per_page' => -1,
			    'fields' => 'ids'
			    )
			);

			$donation_posts_count = count($posttype_data);

			if(!empty($donation_posts_count)){

				$checkout_wrapper_cls = (is_checkout())? 'zted_checkout_wrap' : 'zted_cart_wrap';

				$accordion = '<div id="zted_donation_front" class="'.esc_attr($checkout_wrapper_cls).'"><h3>'.esc_html__($this->dashboard_info_data->donation_name, ZT_ED_TEXTDOMAIN).'</h3>';

				$active_cls = "";
				$display_cls = "";
				foreach ($posttype_data as $posttype_details) {
					$accordion .= '<form action="" method="post" class="zted_donation_front_form">';

					$post_donation_front = json_decode(get_post_meta( $posttype_details, 'zted_post_dropdown', true ));
									
					$input_field_value = (isset($post_donation_front) && !empty($post_donation_front->input_field)) ? $post_donation_front->input_field : "";
					
					if(!empty($input_field_value)){
						$this->input_field_data[$posttype_details] = $input_field_value;
					}
					
					$image = wp_get_attachment_image_src( get_post_thumbnail_id( $posttype_details ), 'single-post-thumbnail' );

					// Donation post view based on post's count
					if($donation_posts_count == 1){ $active_cls = 'zted_active'; $display_cls = 'block'; }

					$accordion .= '<p class="zted_accordion '.esc_attr($active_cls).'">'.get_the_title($posttype_details).'</p>';
					$accordion .= '<div class="zted_panel" style="display: '. esc_attr($display_cls) .'">';
					$accordion .= '<div class="zted_donation_description">';
					if(!empty($image[0])){
						$accordion .= '<img src="'.esc_url($image[0]).'" width="100" height="50">';
					}
					//$accordion .= '<p>'.get_the_excerpt($posttype_details).'</p>';
					$accordion .= get_the_content("","",$posttype_details);
					$accordion .= '</div>';

					/* check donation type for display */
					foreach ($post_donation_front as $donation_info_key => $donation_info_value) {

						if($post_donation_front->dropdown_status == 'checked' && $donation_info_key == 'dropdown_price'){

							$accordion .= '<label for="zted_choose_donation_amt">'.__("Please choose donation amount", ZT_ED_TEXTDOMAIN).'</label>';
							$accordion .= '<select name="zted_donation_view_type">';
							$accordion .= '<option value="">'.__('Select donation amount', ZT_ED_TEXTDOMAIN).'</option>';

							foreach($donation_info_value as $id => $option) { 
								$accordion .= '<option value="'.esc_attr($option).'">'.get_woocommerce_currency_symbol().esc_html__($option, ZT_ED_TEXTDOMAIN).'</option>';
							}
							
							/*Check other amount field status and display this option on front*/
							$other_amount_status = ($post_donation_front->other_amount == "checked")? "block" : "none";

							$other_amount_text = ($post_donation_front->other_amount_text != "")? $post_donation_front->other_amount_text : "Other";

							$accordion .= '<option value="other" style="display: '.esc_attr($other_amount_status).'">'.esc_html__($other_amount_text, ZT_ED_TEXTDOMAIN).'</option>';
							$accordion .= '</select>';
							$accordion .= '<label style="display:none;" id="zted_other_amount_input" for="zted_other_amount_input"><input type="number" name="custom_selected_amt" min="1" value="" placeholder="'.__('Enter any amount to donate', ZT_ED_TEXTDOMAIN).'"></input></label>';

						}else if($post_donation_front->price_bar_status == 'checked' && $donation_info_key == 'price_bar'){
							$accordion .= '<div class="zted_slidecontainer"> <input type="range" min="'.esc_attr($donation_info_value->min_range).'" max="'.esc_attr($donation_info_value->max_range).'" value="'.esc_attr($donation_info_value->min_range).'" class="zted_slider" class="zted_scroll_range">
					  			<p>Donation Amount: '.get_woocommerce_currency_symbol().' <span class="zted_scrolled_value"></span></p></div>';
						}else if($post_donation_front->input_field_status == 'checked' && $donation_info_key == 'input_field'){
							$accordion .= '<label for="zted_donation_input_amt">'.__('Please enter ', ZT_ED_TEXTDOMAIN).get_woocommerce_currency_symbol().esc_html__($post_donation_front->input_field, ZT_ED_TEXTDOMAIN).__(' or bigger amount ', ZT_ED_TEXTDOMAIN).'</label>';
							$accordion .= '<span class="zted_currencyinput">'.get_woocommerce_currency_symbol().'</span><input type="number" name="donation_input_amt" min="1" value="'.esc_attr($post_donation_front->input_field).'" min-amount="'.esc_attr($post_donation_front->input_field).'" donation-id="'.esc_attr($posttype_details).'">';
						}
					}

					$accordion .= '<div class="zted_submit_option">';
					$accordion .= '<input type="hidden" name="donation_name[option_name_'.$posttype_details.']" donation-data="donation_option_name" value="">';
					$accordion .= '<input type="hidden" name="zted_submit_donation_val" value=""><input type="submit" name="zted_donation_submit_action" class="zted_submit_donation" value="'.esc_attr($this->dashboard_info_data->donation_button).'">';
					if(isset($_COOKIE['option_name_'.$posttype_details.'_form'])) {
						$accordion .= '<input type="submit" name="zted_donation_remove_action" class="zted_remove_donation" value="'.__('Remove Donation', ZT_ED_TEXTDOMAIN).'" />';
					}
					$accordion .= '</div></div>';
					$accordion .= '</form>';
				}
				$accordion .= '</div>';

				echo $accordion;
			}
		}

		function zted_donation_frontend_style(){

			wp_register_style( ZT_ED_PREFIX . '_frontend_style', ZT_ED_URL.'assets/css/zted_style.css', array(), ZT_ED_VERSION, false);
            wp_enqueue_style( ZT_ED_PREFIX . '_frontend_style' );
		}

		function zted_add_donation_on_cart_page_data(){
			if(isset($this->cart_status_val) && !empty($this->cart_status_val)){			
				if($this->cart_status_val == 'checked'){
					$this->donation_details_data();
				}
			}
		}

		/*Insert discount/donation amount in cart total */
		function zted_prefix_add_discount_line( $cart ) {

			$posttype_data = get_posts(array(
			    'post_type'   => 'donation_option',
			    'post_status' => 'publish',
			    'posts_per_page' => -1,
			    'fields' => 'ids'
			    )
			);

			foreach ($posttype_data as $posttype_details) {

				$cookie_donation_name = 'option_name_'.$posttype_details;
				$cookie_donation_amount = 'option_name_'.$posttype_details.'_form';
				
				if(isset($_COOKIE[$cookie_donation_amount])){ 
					$donation_amount = sanitize_text_field($_COOKIE[$cookie_donation_amount]);
				}

			  	if(!empty($donation_amount)){

			  		//checked option_name_POSTT_ID cookies set here.
			  		if(!isset($_COOKIE[$cookie_donation_name])) {
						    $postTitle = get_the_title($posttype_details) ;
						    setcookie($cookie_donation_name,$postTitle , time()+1800); 
						  	$cart->add_fee( esc_html__( $_COOKIE[$cookie_donation_name], ZT_ED_TEXTDOMAIN ) , (float)$donation_amount );  
						}else{
							$cart->add_fee( esc_html__( $_COOKIE[$cookie_donation_name], ZT_ED_TEXTDOMAIN ) , (float)$donation_amount );
						}
			  	}
			}
		  	
		}

		/* Donation option on checkout page */
		function zted_add_donation_on_checkout_page(){
			if(isset($this->checkout_status_val) && !empty($this->checkout_status_val)){			
				if($this->checkout_status_val == 'checked'){
					$this->donation_details_data();
				}
			}
		}

		/* Donation option before order notes on checkout page */
		function zted_add_donation_before_order_notes( $checkout ) {
			if(isset($this->checkout_inner_status) && !empty($this->checkout_inner_status)){
		    if($this->checkout_inner_status == 'checked'){
					$this->donation_details_data();
				}
			}
		}

		/* Donation email template suite on thank you page */
		function zted_donation_email_thankyou($order_id) {

			//Remove all donations cookies after creating order
			foreach ($_COOKIE as $remove_cookie_key => $remove_cookie_val) {
			    if (substr($remove_cookie_key, 0, 12) == "option_name_") {
			        unset($_COOKIE[$remove_cookie_key]);
			   		setcookie($remove_cookie_key, null, -1, '/');
			    }
			}
			
			//getting order object
		    $order = wc_get_order($order_id);

		    $currency = is_callable( array( $order, 'get_currency' ) ) ? $order->get_currency() : $order->order_currency;

		    $order_donation_table = '<div class="counting-table"><table border="0" cellpadding="10" cellspacing="0" width="100%" style="margin-top: 50px;border: 1.5px solid #ccc;padding-bottom: 10px;">     
                <thead>
                  <tr>
                    <th style="border-right: 2px solid #bcbcbc;background-color: #e0dfdd;color: #34332f; padding: 7px 10px 5px 20px;">Donation Type</th>
                    <th style="background-color: #e0dfdd;color: #34332f;padding: 7px 10px 5px 20px;">Donate Amount</th>
                  </tr>
                </thead>
                <tbody>';
           	$order_total_donations = 0;

		    foreach( $order->get_items('fee') as $item_id => $item_fee ){
		    	
		    	$order_total_donations += (float)$item_fee->get_total();
		    	// The donation name
			    $donation_name = esc_html__($item_fee->get_name(), ZT_ED_TEXTDOMAIN);

				// The donation total amount
				$donation_total = (float)$item_fee->get_total();

			    $donate_amount = wc_price( $donation_total, array( 'currency' => $currency ) );

			    $order_donation_table .= '<tr><td style="color: #34332f;font-weight: 700;font-size: 14px;text-transform: capitalize;padding: 7px 10px 5px 20px;">'.$donation_name.'</td>
	                    <td style="color: #34332f;font-weight: 700;font-size: 14px;padding: 7px 10px 5px 20px; text-align: left;">'.$donate_amount.'</td>
                  	</tr>';
			}

			$order_donation_table .= '<tr><td style="color: #982625;font-weight: 700;font-size: 14px;padding: 7px 10px 5px 20px;">Total Amount</td>
                <td style="color: #982625;font-weight: 700;font-size: 14px;padding: 7px 10px 5px 20px; text-align: left;">'.get_woocommerce_currency_symbol().$order_total_donations.'</td>
              </tr>';
              
			$order_donation_table .= '</tbody></table></div>';

		    $customer_name = $order->get_billing_first_name().' '.$order->get_billing_last_name();
		  	
		  	//checked content data is empty or not 
		  	if(!empty($this->email_editor_content)){
  
					//Email template editor content
		  		$this->email_editor_content = str_replace(
		  			array('{{first_name}}', '{{last_name}}', '{{email}}', '{{date}}', '{{donation_name}}', '{{price}}'), 
		  			array($order->get_billing_first_name(), $order->get_billing_last_name(), $order->get_billing_email(), $order->order_date, $donation_name, $donate_amount),
		  			$this->email_editor_content
		  		);

		  		$this->email_editor_content = str_replace('<ul>', '<ul style="list-style-type: none;">', $this->email_editor_content);
		  		$this->email_editor_content = str_replace('{{donation_listing}}', $order_donation_table, $this->email_editor_content);

		  		$this->email_editor_content = html_entity_decode($this->email_editor_content);

		  		$recipient_cc = $this->email_suite->recipient_cc;

		  		$fromName = __('Donation Receipt', ZT_ED_TEXTDOMAIN );
		  		$to = $order->get_billing_email();
		  		$subject = esc_html__( $this->email_suite->subject, ZT_ED_TEXTDOMAIN );

		  		$body = '<div class="zted_donation-main" style="background-color: #fdfdfd;border: 15px solid #b3b3b3;width: 100%;max-width: 510px;margin: auto;padding: 30px;">'.$this->email_editor_content.'</div>';

		  		$fromEmail = $this->email_suite->recipient_from;
		  		$headers  = 'Content-type: text/html; charset=UTF-8' . "\r\n";
		  		$headers .= 'From:  ' . $fromName . ' <' . $fromEmail .'>' . " \r\n" .'Reply-To: '.  $fromEmail . "\r\n";
		  		$headers .= "Cc: ".$this->email_suite->recipient_cc;

		  		if($this->email_suite->email_status_val == 'checked'){
		  			wp_mail( $to, $subject, $body, $headers );
		  		}
			} //closed condition checked content data is empty or not 
		}

		/* Donation frontend additional script code */
		function zted_donation_frontend_script(){
		?>
			<script>
			var acc = document.getElementsByClassName("zted_accordion");
			var i;

			for (i = 0; i < acc.length; i++) {
			  acc[i].addEventListener("click", function() {
			    this.classList.toggle("zted_active");
			    var panel = this.nextElementSibling;
			    if (panel.style.display === "block") {
			      panel.style.display = "none";
			    } else {
			      panel.style.display = "block";
			    }
			  });
			}

			/* Get donation type name */
			jQuery(document).on('click', 'form.zted_donation_front_form p.zted_active', function(){

				jQuery('input[donation-data="donation_option_name"]').val(jQuery.trim(jQuery(this).text()));

				var donation_input = jQuery(this).parent().find('input[type="text"]').attr('name');
				var donation_dropdown = jQuery(this).parent().find('select').attr('name');

				if(donation_input == 'donation_input_amt'){
					
					var default_donate_val = jQuery.trim(jQuery(this).parent().find('input[type="text"]').attr('min-amount'));
					jQuery(this).parent().find('input[name="zted_submit_donation_val"]').val(default_donate_val);
				}

				if(donation_dropdown == 'zted_donation_view_type'){

					var selected_amount = jQuery.trim(jQuery(this).find(":selected").val());
					var donate_button = jQuery(this).parent().find('input[name="zted_donation_submit_action"]');

					if(selected_amount===""){ 
				        donate_button.prop('disabled', true); 
				        jQuery('input[name="zted_submit_donation_val"]').val(); 
				    }
				}
			});

			/* get Pricebar range value in a div on scroll */
			function updateLabel() {
			  var limit = this.parentElement.getElementsByClassName("zted_scrolled_value")[0];
			  limit.innerHTML = this.value;
			  var curr_pricebar_amount = jQuery.trim(this.value);
			  jQuery(this).parent().parent().find('input[name="zted_submit_donation_val"]').val(curr_pricebar_amount);
			}

			var slideContainers = document.getElementsByClassName("zted_slidecontainer");

			for (var i = 0; i < slideContainers.length; i++) {
			  var slider = slideContainers[i].getElementsByClassName("zted_slider")[0];
			  updateLabel.call(slider);
			  slider.oninput = updateLabel;
			}

			/* Get dropdown select option value on change option */
			jQuery(document).on('change', 'form.zted_donation_front_form select[name="zted_donation_view_type"]', function(){
				var selected_amount = jQuery.trim(jQuery(this).find(":selected").val());
				var donate_button = jQuery(this).parent().find('input[name="zted_donation_submit_action"]');
				
				if(selected_amount == "other"){
					donate_button.prop('disabled', false); 
		        	jQuery('label#zted_other_amount_input').show();
		        	jQuery(document).on('mouseout', 'label#zted_other_amount_input input[name="custom_selected_amt"]', function() {
						var other_amount = jQuery.trim(jQuery(this).val());
		        		jQuery('input[name="zted_submit_donation_val"]').val(other_amount);
		        	});
		        	
		        }else{
		        	jQuery('label').hide();
		        	if(selected_amount===""){ 
				        alert('Please select the amount');
				        donate_button.prop('disabled', true);  
				    }else{
				    	jQuery('input[name="zted_submit_donation_val"]').val(selected_amount);
				    	donate_button.prop('disabled', false); 
				    }
		        }
			});

			/* Get input field option value on insert value in field */
			jQuery(document).on('change', 'form.zted_donation_front_form input[name="donation_input_amt"]', function(){

				var donate_button = jQuery(this).parent().find('input[name="zted_donation_submit_action"]');
				var donate_currency = jQuery(this).parent().find('span.zted_currencyinput').text();
				var selected_amount = parseInt(jQuery.trim(jQuery(this).val()));

				var donation_id = parseInt(jQuery.trim(jQuery(this).attr('donation-id')));
				
				var input_field_arr = '<?php echo ( isset($this->input_field_data) && !empty($this->input_field_data) ) ? json_encode($this->input_field_data) : ""; ?>';

				// jQuery.each( input_field_arr, function( input_field_arr_key, input_field_arr_val ) {
				jQuery.each( JSON.parse(input_field_arr), function( input_field_arr_key, input_field_arr_val ) {
					if(parseInt(input_field_arr_key) === donation_id){
						if(selected_amount < input_field_arr_val){

							alert('Please enter '+donate_currency+input_field_arr_val+' or bigger amount');
							donate_button.prop('disabled', true);
		              		jQuery('input[name="zted_submit_donation_val"]').val(parseInt(input_field_arr_val));

						} else if (selected_amount >= parseInt(input_field_arr_val)){
							donate_button.prop('disabled', false);
			              	jQuery('input[name="zted_submit_donation_val"]').val(selected_amount);
			          	}
					}
				});    
			});	
		</script>
		<?php } 

	}

	add_action( 'plugins_loaded', function() {
		ZT_ED()->frontend = new ZT_ED_Frontend_Action;
	} );
}