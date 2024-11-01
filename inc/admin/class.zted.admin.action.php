<?php
/**
 * ZT_ED_Admin_Action Class
 *
 * Zestard Easy Donation functionality.
 *
 * @package WordPress
 * @subpackage Zestard Easy Donation Admin Actions
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ZT_ED_Admin_Action' ) ){

	/**
	 *  The ZT_ED_Admin_Action Class
	 */
	class ZT_ED_Admin_Action {

		function __construct()  {

			//action to trigger option in sidebar menu
			add_action( 'admin_menu', array($this, 'zted_menu') );

			// Hooking up our function to easy donation plugin setup
			add_action( 'init', array($this, 'zted_donation_option_posttype') );
			add_filter( 'manage_edit-donation_option_columns',array($this, 'zted_donation_option_custom_columns_head'), 10 );
			add_action( 'manage_donation_option_posts_custom_column', array($this, 'zted_donation_option_custom_columns_content'), 10, 2 );
			add_action( 'add_meta_boxes', array($this, 'zted_add_donation_option_info_metabox'));
			add_action( 'save_post', array($this, 'zted_save_donation_option_info'));

			//shop order columns hook
			add_filter( 'manage_edit-shop_order_columns',  array($this,'zted_new_order_donation_amount_column') );
			add_action( 'manage_shop_order_posts_custom_column', array($this, 'zted_add_order_donation_amount_column_content') );
		}
		/*
		   ###     ######  ######## ####  #######  ##    ##  ######
		  ## ##   ##    ##    ##     ##  ##     ## ###   ## ##    ##
		 ##   ##  ##          ##     ##  ##     ## ####  ## ##
		##     ## ##          ##     ##  ##     ## ## ## ##  ######
		######### ##          ##     ##  ##     ## ##  ####       ##
		##     ## ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##     ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/*
		*Create donation plugin custom post type
		*/
		function zted_donation_option_posttype() {

		    $post_type_arr = array('donation_option');

		    foreach($post_type_arr as $curr_post_type){

		        $post_type_name = sanitize_text_field(ucwords(str_replace('_', ' ', $curr_post_type)));

		        register_post_type( $curr_post_type,
		            // CPT Options
		            array(
		                'labels' => array(
		                    'name' => __( 'Donations', ZT_ED_TEXTDOMAIN ),
		                    'singular_name' => __( 'Donation', ZT_ED_TEXTDOMAIN ),
		                    'add_new_item'  =>  __( 'Add '.$post_type_name, ZT_ED_TEXTDOMAIN ),
		                    'edit_item'     =>  __( 'Edit '.$post_type_name, ZT_ED_TEXTDOMAIN),
		                    'new_item'      =>  __( 'New '.$post_type_name, ZT_ED_TEXTDOMAIN),
		                    'search_items' => __('Search '.$post_type_name, ZT_ED_TEXTDOMAIN),
		                    'not_found' =>  __('Nothing found'),
		                ),
		                'public' => true,
		                'has_archive' => true,
		                'rewrite' => array('slug' => $curr_post_type),
		                'show_ui' => true,
		                'show_in_menu' => false,
		                'supports'  =>  array( 'title', 'editor', 'thumbnail')
		            )
		        );
		    }
		}

		/* Donation_option post type columns data */
		function zted_donation_option_custom_columns_head( $defaults ) {
		    //unset( $defaults['date'] );
		    $columns_order = array();
		    $defaults['date'] = __( 'Published', 'donation_option' );
		    $defaults['title'] = __( 'Donation Name', 'donation_option' );

		    foreach($defaults as $column_order_key=>$column_order_value) {
		        if($column_order_key=='date') {  // when we find the date column
		           $columns_order['donation_thumbnail']   = __( 'Image', 'donation_option' );  // put the tags column before it
		           $columns_order['donation_view_mode']   = __( 'Donation View', 'donation_option' );
		           $columns_order['donation_description'] = __( 'Description', 'donation_option' );  // put the tags column before it
		        }    
		        $columns_order[$column_order_key] = $column_order_value;
		    }
		        
		    return $columns_order;
		}

		function zted_donation_option_custom_columns_content( $column_name, $post_id ) {
		    if ( 'donation_description' == $column_name ) {
		        echo wp_trim_words( get_the_content($post_id), 25, '...' );
		    }
		    if ($column_name == 'donation_thumbnail') {
		        $post_featured_image = get_the_post_thumbnail_url($post_id, array( 25, 25));
		        if ($post_featured_image) {
		            echo '<img src="' . esc_url($post_featured_image) . '" />';
		        }
		    }

		    if ($column_name == 'donation_view_mode') {
		        $curr_option_view = json_decode(get_post_meta( $post_id, 'zted_post_dropdown', true ));

		        if(!empty($curr_option_view->dropdown_status)){
					_e('Dropdown', ZT_ED_TEXTDOMAIN);

				} else if(!empty($curr_option_view->price_bar_status)){
					_e('Price Bar', ZT_ED_TEXTDOMAIN);

				} else if(!empty($curr_option_view->input_field_status)){
					_e('Input Field', ZT_ED_TEXTDOMAIN);
				}
		    }
		}

		/* Create metaboxes on current donation option with save the values */
		function zted_add_donation_option_info_metabox() {
		    add_meta_box(
		        'zted-donation-option-info-metabox',
		        __( 'Donation View', 'donation_option' ),
		        array($this, 'zted_render_donation_option_info_metabox'),
		        'donation_option',
		        'normal',
		        'core'
		    );
		}

		function zted_render_donation_option_info_metabox( $post ){
		    //get previously saved meta values (if any)
		    $post_donation_data = json_decode(get_post_meta( $post->ID, 'zted_post_dropdown', true ));
		?>

			<div class="zted_donation_view_type">
	        	<ul>
		        	<li>
		        		<input type="radio" name="donation_view_data" donation-view="donation_dropdown" id="zted_donation_dropdown_sec" <?php echo (!empty($post_donation_data))? $post_donation_data->dropdown_status : 'checked'; ?>>
		        		<label for="zted_donation_dropdown_sec"><?php _e("Dropdown", ZT_ED_TEXTDOMAIN); ?></label>
		        		<input type="hidden" name="zted_post_donation[dropdown_status]" value="<?php echo esc_attr((!empty($post_donation_data))? $post_donation_data->dropdown_status : 'checked'); ?>">
		        	</li>
		        	<li>
		        		<input type="radio" name="donation_view_data" donation-view="donation_pricebar" id="zted_donation_pricebar_sec" <?php echo (!empty($post_donation_data->price_bar_status) == 'checked') ? esc_attr($post_donation_data->price_bar_status) : ""; ?> >
		        		<label for="zted_donation_pricebar_sec"><?php _e("Price Bar", ZT_ED_TEXTDOMAIN); ?></label>
		        		<input type="hidden" name="zted_post_donation[price_bar_status]" value="<?php echo (!empty($post_donation_data->price_bar_status) == 'checked') ? esc_attr($post_donation_data->price_bar_status) : ""; ?>">
		        	</li>
		        	<li>
		        		<input type="radio" name="donation_view_data" donation-view="donation_input_field" id="zted_donation_inputfield_sec" <?php echo (!empty($post_donation_data->input_field_status) == 'checked') ? esc_attr($post_donation_data->input_field_status) : ""; ?>>
		        		<label for="zted_donation_inputfield_sec"><?php _e("Input Field", ZT_ED_TEXTDOMAIN); ?></label>
		        		<input type="hidden" name="zted_post_donation[input_field_status]" value="<?php echo (!empty($post_donation_data->input_field_status) == 'checked') ? esc_attr($post_donation_data->input_field_status) : ""; ?>">
		        	</li>
	        	</ul>
        	</div>

			<div class="zted_donation_mode" id="zted_donation_dropdown" style="display: <?php echo (empty($post_donation_data) || $post_donation_data->dropdown_status == 'checked')? 'block' : 'none'; ?>;">
        		
        		<?php 
        			if(!empty($post_donation_data->dropdown_price)){
						foreach ($post_donation_data->dropdown_price as $dropdown_price_key => $dropdown_price_value) {

							echo '<div class="zted_donation_dropdown_pricing"><span class="zted_add_amount">'. __("Add Amount ", ZT_ED_TEXTDOMAIN).'</span><input type="number" name="zted_post_donation['."dropdown_price".']['.$dropdown_price_key.']" value="'.esc_attr($dropdown_price_value).'" data-msg="'.__('Dropdown should not be empty', ZT_ED_TEXTDOMAIN).'"><a href="#" class="zted_delete">-</a></div>';
						}
					}else{
        			
						echo '<div class="zted_donation_dropdown_pricing"><span class="zted_add_amount">'. __("Add Amount ", ZT_ED_TEXTDOMAIN).'</span><input type="number" name="zted_post_donation[dropdown_price][]" data-msg="'.__('Dropdown should not be empty', ZT_ED_TEXTDOMAIN).'"></div>';
					}
				?>
				<button class="zted_add_price_field"><?php _e("Add Amount Option", ZT_ED_TEXTDOMAIN); ?></button>

				<div id="zted_other_amt_sec">
					<input type="checkbox" name="zted_post_donation[other_amount]" id="zted_other_amt_field" value="<?php echo (!empty($post_donation_data->other_amount) && ($post_donation_data->other_amount == 'checked') ) ? esc_attr($post_donation_data->other_amount) : ""; ?>" <?php echo ( !empty($post_donation_data->other_amount) ) ? esc_attr($post_donation_data->other_amount) : ""; ?>>
					<label for="zted_other_amt_field"><?php _e('Display Other Amount box', ZT_ED_TEXTDOMAIN); ?></label>
					<input type="text" name="zted_post_donation[other_amount_text]" class="zted_other_amt_field" placeholder="Enter other amount..." value="<?php echo (isset($post_donation_data->other_amount_text) && !empty($post_donation_data->other_amount_text) ) ? esc_attr($post_donation_data->other_amount_text) : ""; ?> <?php //echo ( !empty($post_donation_data->other_amount_text) ) ? esc_attr($post_donation_data->other_amount_text) : ""; ?>" style="display: <?php echo ( !empty($post_donation_data->other_amount) && ($post_donation_data->other_amount == 'checked' ) )? 'block' : 'none'; ?>;">
				</div>
            </div>

			<div class="zted_donation_mode" id="zted_donation_pricebar" style="display: <?php echo (!empty($post_donation_data->price_bar_status) == 'checked')? 'block' : 'none'; ?>;">
                <p>
                  <label for="amount"><?php _e("Price range:", ZT_ED_TEXTDOMAIN); ?></label><br>
                  <input class="zted_min" name="zted_post_donation[price_bar][min_range]" type="number" value="<?php echo (isset($post_donation_data->price_bar->min_range) && !empty($post_donation_data->price_bar->min_range)) ? esc_attr($post_donation_data->price_bar->min_range): ""; ?>" placeholder="Enter minimum price..." data-msg="<?php _e('Pricebar minimum range should not be empty', ZT_ED_TEXTDOMAIN); ?>" /> - <input class="zted_max" name="zted_post_donation[price_bar][max_range]" type="number" value="<?php echo (isset($post_donation_data->price_bar->max_range) && !empty($post_donation_data->price_bar->max_range)) ? esc_attr($post_donation_data->price_bar->max_range): ""; ?>" placeholder="Enter maximum price..." data-msg="<?php _e('Pricebar maximum range should not be empty', ZT_ED_TEXTDOMAIN); ?>" />
                </p>
            </div>

            <div class="zted_donation_mode" id="zted_donation_input_field" style="display: <?php echo (!empty($post_donation_data->input_field_status) == 'checked')? 'block' : 'none'; ?>;">
                <p>
                  <label for="input_amount"><?php _e("Restrict donation minimum amount:", ZT_ED_TEXTDOMAIN); ?></label><br>
                  <input type="number" id="zted_input_amount" name="zted_post_donation[input_field]" placeholder="Enter the donate amount..." value="<?php echo (isset($post_donation_data->input_field) && !empty($post_donation_data->input_field)) ? esc_attr($post_donation_data->input_field): ""; ?>" data-msg="<?php _e('Input Field value should not be empty', ZT_ED_TEXTDOMAIN); ?>">
                </p>
                <p class="zted_input_amount_cntnt"><?php echo '<span>'.__('Notice: ', ZT_ED_TEXTDOMAIN).'</span> '.__('This field will render text box on cart/checkout page on frontend.', ZT_ED_TEXTDOMAIN); ?> </p>
            </div>

			<?php
		}

		function zted_save_donation_option_info( $post_id ) {
		    //checking if the post being saved is an 'donation_option',
		    //if not, then return
				$post_type_value = (isset( $_POST['post_type'] ) && !empty( $_POST['post_type'] )) ? $_POST['post_type'] : "";
		    if ( 'donation_option' != $post_type_value ) {
		        return;
		    }

		    //checking for the 'save' status
		    $is_autosave = wp_is_post_autosave( $post_id );
		    $is_revision = wp_is_post_revision( $post_id );
		    //exit depending on the save status or if the nonce is not valid
		    if ( $is_autosave || $is_revision ) {
		        return;
		    }

		    //checking for the values and performing necessary actions
		    
		    if ( isset( $_POST['zted_post_donation'] ) ) {

		    	$zted_post_donation= map_deep( wp_unslash( $_POST['zted_post_donation'] ), 'sanitize_text_field' );;

		    	$dropdown_status 	= sanitize_text_field($zted_post_donation['dropdown_status']);
		    	$price_bar_status 	= sanitize_text_field($zted_post_donation['price_bar_status']);
		    	$input_field_status = sanitize_text_field($zted_post_donation['input_field_status']);
		    	$dropdown_price_arr = array_map( 'sanitize_text_field', wp_unslash( $zted_post_donation['dropdown_price']));

		    	$dropdown_price_data = array();
		    	foreach ($dropdown_price_arr as $dropdown_price_key => $dropdown_price_value) {

		    		array_push($dropdown_price_data, sanitize_text_field($dropdown_price_value));
		    	}

		    	$other_amount = sanitize_text_field($zted_post_donation['other_amount']);
		    	$other_amount_text 	= sanitize_text_field($zted_post_donation['other_amount_text']);

		    	$price_bar_arr = array_map( 'sanitize_text_field', wp_unslash( $zted_post_donation['price_bar']));

		    	$price_bar_data = array();
		    	foreach ($price_bar_arr as $price_bar_key => $price_bar_value) {

		    		$price_bar_data[$price_bar_key] = sanitize_text_field($price_bar_value);
		
		    	}

		    	$input_field = sanitize_text_field($zted_post_donation['input_field']);

		    	$post_donation_arr = array(
		    		'dropdown_status' 	=> $dropdown_status, 
		    		'price_bar_status'  => $price_bar_status, 
		    		'input_field_status'=> $input_field_status, 
		    		'dropdown_price'    => $dropdown_price_data, 
		    		'other_amount'      => $other_amount, 
		    		'other_amount_text' => $other_amount_text, 
		    		'price_bar'         => $price_bar_data, 
		    		'input_field'       => $input_field
		    	);

		        update_post_meta( $post_id, 'zted_post_dropdown', json_encode($post_donation_arr));
		    }
		    if ( isset( $_POST['zted_donation_view_title'] ) ) {
		        update_post_meta( $post_id, 'donation_view_type', sanitize_text_field($_POST['zted_donation_view_title']) );
		    }    
		}

		/*
		*Add donation amount column with price details in orders list table
		*/
		function zted_new_order_donation_amount_column( $columns ) {
		    $columns['donation_amount'] = __('Donation', ZT_ED_TEXTDOMAIN);
		    return $columns;
		}

		function zted_add_order_donation_amount_column_content( $column ) {
		    global $post;

		    if ( 'donation_amount' === $column ) {

		        $order    = wc_get_order( $post->ID );
		      	$currency = is_callable( array( $order, 'get_currency' ) ) ? $order->get_currency() : $order->order_currency;

		      	$donation_total = 0;
				// Iterating through order fee items ONLY
				foreach( $order->get_items('fee') as $donation_item_id => $donation_item ){

				    // The donation name
				    $donation_name = $donation_item->get_name();

				    // The donation total amount
				    $donation_total = (int)$donation_total + (int)$donation_item->get_total();    
				}
				echo wc_price( $donation_total, array( 'currency' => $currency ) );
		    }
		}

		//triggers option in sidebar menu
		function zted_menu() {

		    add_menu_page(__( 'Zestard Easy Donation', ZT_ED_TEXTDOMAIN ), __( 'Donation Settings', ZT_ED_TEXTDOMAIN ), 'manage_options', 'zestard-easy-donation', array($this, 'zted_options_page'), ZT_ED_URL.'assets/images/donation_img.png');

            add_submenu_page('zestard-easy-donation', __( 'Donations', ZT_ED_TEXTDOMAIN ), __( 'Donations', ZT_ED_TEXTDOMAIN ), 'manage_options', 'edit.php?post_type=donation_option', NULL );
            add_submenu_page('zestard-easy-donation', __( 'Track Donation', ZT_ED_TEXTDOMAIN ), __( 'Track Donation', ZT_ED_TEXTDOMAIN ), 'manage_options', 'zted_track_donation', array($this, 'zted_track_donation') );
		}

		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/


		//triggers option in sidebar menu callback function
		function zted_track_donation() {
		?>
			<h3><?php _e("Track Donation", ZT_ED_TEXTDOMAIN); ?></h3>
			
			<div id="zted_track_donation_form">
				<?php
					$orders_year = array();
	    			$donation_orders = wc_get_orders( array('numberposts' => -1) );

					$posttype_data = get_posts(array(
					    'post_type'   => 'donation_option',
					    'post_status' => 'publish',
					    'posts_per_page' => -1,
					    'fields' => 'ids'
					    )
					);
	        		$donation_posts_count = count($posttype_data);
	        		if(!empty($donation_posts_count) && !empty($donation_orders)){
				?>
		        	<select name="zted_select_track_donation" id="zted_select_track_donation">
		        	<?php
						foreach ($posttype_data as $posttype_details) {
							echo '<option value="'.get_the_title($posttype_details).'">'.get_the_title($posttype_details).'</option>';
						}	
		        	?>
		        	</select>
	        	<?php 
	        	} 
	        	
				if(!empty($donation_orders)){
	        	?>

		        	<select name="zted_select_donation_year" id="zted_select_donation_year">
		        		<?php

			                foreach( $donation_orders as $donation_curr_order ){
			                	$order_created_date = $donation_curr_order->get_date_created()->format ('Y');

			                	if(!empty($order_created_date)){

				                	if (array_key_exists($order_created_date, $orders_year)) {

				                        $orders_year[$order_created_date] = $order_created_date;
				                    }else{

				                        $orders_year[$order_created_date] = $order_created_date;
				                    }
				                }
			                }

			                foreach ($orders_year as $year_key => $year_value) {
			                	echo '<option value="'.esc_attr($year_value).'">'.esc_html__($year_value, ZT_ED_TEXTDOMAIN).'</option>';
			                }
			    		?>	
		        	</select>
		        	<a href="javascript: void(0)" name="zted_tracking_form_submit" id="zted_tracking_form_submit"><?php _e('Track', ZT_ED_TEXTDOMAIN);?></a>
		        <?php } ?>
	        	
        	</div>
        	<figure class="zted_highcharts-figure">
			    <canvas id="zted_tracking_data" width="1060" height="400"></canvas>
			</figure>
		<?php
		}

		function zted_options_page() {
			settings_errors();

			global $wpdb;
			$table_name = $wpdb->prefix . 'zted_donation_config';

			$donation_config_result = $wpdb->get_results("SELECT * from $table_name WHERE 'id' IS NOT NULL");
    			
			if (isset($_POST['submit'])) {

				//Get Dashboard details data
			    $dashboard_info = array_map('sanitize_text_field', wp_unslash( $_POST['zted_donation']['dashboard_tab']));

			    //Cart status on backend
			    $dashboard_info_cart = array_map('sanitize_text_field', wp_unslash( $_POST['zted_donation']['dashboard_tab']['cart'] ));

			    //Checkout status on backend
			    $dashboard_info_checkout = array_map( 'sanitize_text_field', wp_unslash( $_POST['zted_donation']['dashboard_tab']['checkout'] ) );

			    $dashboard_donation_name 		= sanitize_text_field($_POST['zted_donation']['dashboard_tab']['donation_name']);
			    $dashboard_donation_button 		= sanitize_text_field($_POST['zted_donation']['dashboard_tab']['donation_button']);
			    $dashboard_donation_description = sanitize_textarea_field($_POST['zted_donation']['dashboard_tab']['donation_description']);

			    $dashbord_details_data = array(
			    	'cart' 					=> $dashboard_info_cart,
			    	'checkout' 				=> $dashboard_info_checkout,
			    	'donation_name' 		=> $dashboard_donation_name,
			    	'donation_button' 		=> $dashboard_donation_button,
			    	'donation_description' 	=> $dashboard_donation_description,
			    );

			    //Get Email template details data
			    $email_template_info 	= array_map('sanitize_text_field', wp_unslash( $_POST['zted_donation']['email_templates']));
			    //$email_template_status 	= sanitize_text_field($email_template_info['email_template_status']);
			    if ( isset( $email_template_info['email_template_status'] ) &&  !empty($email_template_info['email_template_status']) ) {
 						$email_template_status 	= sanitize_text_field($email_template_info['email_template_status']);
					}else{
						$email_template_status 	=  '';
					}
			    $email_status_val 		= sanitize_text_field($email_template_info['email_status_val']);
			    $recipient_from 		= sanitize_email($email_template_info['recipient_from']);
			    $recipient_cc 			= sanitize_email($email_template_info['recipient_cc']);
			    $subject 				= sanitize_text_field($email_template_info['subject']);

			    $email_template_data = array(
			    	'email_template_status' => $email_template_status,
			    	'email_status_val' 		=> $email_status_val,
			    	'recipient_from' 		=> $recipient_from,
			    	'recipient_cc' 			=> $recipient_cc,
			    	'subject' 				=> $subject,
			    );

			    //Get Email template editor data
			    $submit_email_editor_content = sanitize_text_field(htmlentities($_POST['zted_email_editor_content']));

			    
			    if(count($donation_config_result) == 0){

				    $result = $wpdb->insert($table_name, array(
				        'dashboard_info' => json_encode($dashbord_details_data),
				        'email_templates'=> json_encode($email_template_data),
				        'email_content'  => $submit_email_editor_content,
				    ));
			    } else {
				    $result = $wpdb->update(
				    	$table_name, 
					    array( 
					        'dashboard_info' => json_encode($dashbord_details_data),
					        'email_templates'=> json_encode($email_template_data),
					        'email_content'  => $submit_email_editor_content,
					    ), 
					    array(
					        "id" => 1
					    ) 
					);
				}

				if( FALSE === $result ) {
				    echo '<div class="notice notice-error is-dismissible"><p>'.__('Something went wrong', ZT_ED_TEXTDOMAIN).'</p></div>';
				} else {
				    echo '<div class="updated notice is-dismissible"><p>'.__('Your donation configuration details successfully updated', ZT_ED_TEXTDOMAIN).'</p></div>';
				}
			}

			$donation_table_data = $wpdb->get_results("SELECT * FROM $table_name");
			
			if(!empty($donation_table_data)){
				$dashboard_info 	= json_decode($donation_table_data[0]->dashboard_info);
				$email_templates 	= json_decode($donation_table_data[0]->email_templates);
				$email_editor_cntnt = html_entity_decode($donation_table_data[0]->email_content);
				//$other_amount_status= esc_html__($donation_table_data[0]->other_amount, ZT_ED_TEXTDOMAIN);
			}else{
				$dashboard_info 	= "";
				$email_templates 	= "";
				$email_editor_cntnt = "";
				//$other_amount_status= "";
			}
		?>
		<!--admin layout page html-->
		<div class="zted_admin_layout">
			<div class="zted_tabs zted_backend_config_tabs">
			    <ul class="zted_tabs-list">
			        <li class="zted_active"><a href="#zted_dashboard_tab"><?php _e("Settings", ZT_ED_TEXTDOMAIN); ?></a></li>
			        <li><a href="#zted_email_notification_tab"><?php _e("Email Receipt", ZT_ED_TEXTDOMAIN); ?></a></li>
			        <li><a href="#zted_support_tab"><?php _e("Support", ZT_ED_TEXTDOMAIN); ?></a></li>
			    </ul>

			    <form method="post" action="" id="zted_admin_layout_form">
				    <div id="zted_dashboard_tab" class="zted_tab zted_active">
				     	<p>
				     		<ul class="zted_dashboard_details">
				     			<li data-gateway_id="zted_donation_on_cart" id="zted_donation_on_cart">
				     				<span class="zted_dashboard_details_title"><?php _e("Add to cart page", ZT_ED_TEXTDOMAIN); ?></span>
				     				<!-- Payment configuration status Enable/Disable switch -->
				                    <div class="zted_donation_switch">
				                        <input type="checkbox" name="zted_donation[dashboard_tab][cart][status]" class="zted_donation_switch-checkbox" id="zted_cart_status" value="checked" <?php echo (isset( $dashboard_info->cart->cart_status ) && !empty( $dashboard_info->cart->cart_status )) ? esc_attr($dashboard_info->cart->cart_status) : ""; ?>>
				                        <label class="zted_donation_switch-label" for="zted_cart_status">
				                            <span class="zted_donation_switch-inner"></span>
				                            <span class="zted_donation_switch-switch"></span>
				                        </label>
				                        <input type="hidden" name="zted_donation[dashboard_tab][cart][cart_status]" value="<?php echo (isset( $dashboard_info->cart->cart_status ) && !empty( $dashboard_info->cart->cart_status )) ? esc_attr($dashboard_info->cart->cart_status) : ""; ?>">
				                    </div>
				                    <!-- Payment configuration status Enable/Disable switch -->
				     			</li>
				     			<li data-gateway_id="zted_donation_on_checkout" id="zted_donation_on_checkout">
				     				<span class="zted_dashboard_details_title"><?php _e("Add before checkout form", ZT_ED_TEXTDOMAIN); ?></span>
				     				<!-- Payment configuration status Enable/Disable switch -->
				                    <div class="zted_donation_switch">
				                        <input type="checkbox" name="zted_donation[dashboard_tab][checkout][status]" class="zted_donation_switch-checkbox" id="zted_checkout_status" value="checked" <?php echo (isset( $dashboard_info->checkout->checkout_status ) && !empty( $dashboard_info->checkout->checkout_status )) ? esc_attr($dashboard_info->checkout->checkout_status) : "";?> >
				                        <label class="zted_donation_switch-label" for="zted_checkout_status">
				                            <span class="zted_donation_switch-inner"></span>
				                            <span class="zted_donation_switch-switch"></span>
				                        </label>
				                        <input type="hidden" name="zted_donation[dashboard_tab][checkout][checkout_status]" value="<?php echo (isset( $dashboard_info->checkout->checkout_status ) && !empty( $dashboard_info->checkout->checkout_status )) ? esc_attr($dashboard_info->checkout->checkout_status) : "";?>">
				                    </div>
				                    <!-- Payment configuration status Enable/Disable switch -->
				     			</li>
				     			<li data-gateway_id="zted_donation_on_checkout_inner" id="zted_donation_on_checkout_inner">
				     				<span class="zted_dashboard_details_title"><?php _e("Add after checkout form", ZT_ED_TEXTDOMAIN); ?></span>
				     				<!-- Payment configuration status Enable/Disable switch -->
				                    <div class="zted_donation_switch">
				                        <input type="checkbox" name="zted_donation[dashboard_tab][checkout][after_checkout_form]" class="zted_donation_switch-checkbox" id="zted_after_checkout_form" value="checked" <?php echo (isset( $dashboard_info->checkout->after_checkout_form ) && !empty( $dashboard_info->checkout->after_checkout_form )) ? esc_attr($dashboard_info->checkout->after_checkout_form) : ""; ?>>
				                        <label class="zted_donation_switch-label" for="zted_after_checkout_form">
				                            <span class="zted_donation_switch-inner"></span>
				                            <span class="zted_donation_switch-switch"></span>
				                        </label>
				                        <input type="hidden" name="zted_donation[dashboard_tab][checkout][after_checkout_form]" value="<?php echo (isset( $dashboard_info->checkout->after_checkout_form ) && !empty( $dashboard_info->checkout->after_checkout_form )) ?  esc_attr($dashboard_info->checkout->after_checkout_form) : ""; ?>">
				                    </div>
				                    <!-- Payment configuration status Enable/Disable switch -->
				     			</li>
				     			<li>
				     				<h4><?php _e("Donation Name:", ZT_ED_TEXTDOMAIN); ?></h4>
				     				<input type="text" name="zted_donation[dashboard_tab][donation_name]" placeholder="Enter the donation name..." value="<?php echo (isset( $dashboard_info->donation_name ) && !empty( $dashboard_info->donation_name )) ?  esc_attr($dashboard_info->donation_name): "";?>">
				     			</li>
				     			<li>
				     				<h4><?php _e("Donation Button Text:", ZT_ED_TEXTDOMAIN); ?></h4>
				     				<input type="text" name="zted_donation[dashboard_tab][donation_button]" placeholder="Enter the donation button text..." value="<?php echo (isset( $dashboard_info->donation_button ) && !empty( $dashboard_info->donation_button )) ? esc_attr($dashboard_info->donation_button) : "";?>">
				     			</li>
				     			<li>
				     				<h4><?php _e("Additional Label:", ZT_ED_TEXTDOMAIN); ?></h4>
				     				<textarea name="zted_donation[dashboard_tab][donation_description]" rows="4" cols="40" placeholder="Add additional message here..."><?php echo (isset( $dashboard_info->donation_description ) && !empty( $dashboard_info->donation_description )) ? esc_textarea($dashboard_info->donation_description) : "";?></textarea>
				     			</li>
				     		</ul>
				     	</p>
				 	</div>

				    <div id="zted_email_notification_tab" class="zted_tab">
				    	
				        <table class="zted_form-table">
			                <tbody>
			                    <tr valign="top">
			                        <th scope="row" class="zted_titledesc">
			                            <label for="donation_email_enabled"><?php _e("Enable/Disable", ZT_ED_TEXTDOMAIN); ?> </label>
			                        </th>
			                        <td class="zted_forminp">
			                            <fieldset>
			                                <legend class="zted_screen-reader-text">
			                                	<span><?php _e("Enable/Disable", ZT_ED_TEXTDOMAIN); ?></span>
			                                </legend>
			                                
			                                <input type="checkbox" name="zted_donation[email_templates][email_template_status]" id="zted_donation_email_enabled" value="<?php echo (isset( $email_templates->email_template_status ) && !empty( $email_templates->email_template_status )) ? esc_attr($email_templates->email_template_status) : "unchecked"; ?>" <?php echo (isset( $email_templates->email_status_val ) && !empty( $email_templates->email_status_val )) ? esc_attr($email_templates->email_status_val) : "unchecked"; ?>> 
			                                <label for="zted_donation_email_enabled"><?php _e("Enable this email notification", ZT_ED_TEXTDOMAIN); ?></label>
		                                	<input type="hidden" name="zted_donation[email_templates][email_status_val]" id="zted_email_status_val" value="<?php echo (isset( $email_templates->email_status_val ) && !empty( $email_templates->email_status_val )) ? esc_attr($email_templates->email_status_val) : "unchecked"; ?>">
			                            </fieldset>
			                        </td>
			                    </tr>
			                    <tr valign="top">
			                        <th scope="row" class="zted_titledesc">
			                            <label for="donation_recipient"><?php _e("Recipient(s)", ZT_ED_TEXTDOMAIN); ?> <span class="zted_help-tip"></span></label>
			                        </th>
			                        <td class="zted_forminp" id="zted_recipient_info">
			                            <fieldset>
			                                <legend class="zted_screen-reader-text"><span><?php _e("Recipient(s)", ZT_ED_TEXTDOMAIN); ?></span></legend>
			                                <span><span class="zted_recipient_email_sec"><?php _e("From: ", ZT_ED_TEXTDOMAIN); ?></span><input type="email" name="zted_donation[email_templates][recipient_from]" id="zted_donation_recipient_from" value="<?php echo (isset( $email_templates->recipient_from ) && !empty( $email_templates->recipient_from )) ? esc_attr($email_templates->recipient_from): ""; ?>" placeholder="">
			                                </span>

			                                <span><span class="zted_recipient_email_sec"><?php _e("CC: ", ZT_ED_TEXTDOMAIN); ?></span><input type="email" name="zted_donation[email_templates][recipient_cc]" id="zted_donation_recipient_cc" value="<?php echo (isset( $email_templates->recipient_cc ) && !empty( $email_templates->recipient_cc )) ? esc_attr($email_templates->recipient_cc): ""; ?>" placeholder="">
			                                </span>
			                            </fieldset>
			                        </td>
			                    </tr>
			                    <tr valign="top">
			                        <th scope="row" class="zted_titledesc">
			                            <label for="template_subject"><?php _e("Subject", ZT_ED_TEXTDOMAIN); ?> <span class="zted_help-tip"></span></label>
			                        </th>
			                        <td class="zted_forminp">
			                            <fieldset>
			                                <legend class="zted_screen-reader-text"><span><?php _e("Subject", ZT_ED_TEXTDOMAIN); ?></span></legend>
			                                <input type="text" name="zted_donation[email_templates][subject]" id="zted_template_subject" value="<?php echo (isset( $email_templates->subject ) && !empty( $email_templates->subject )) ? esc_attr($email_templates->subject) : ""; ?>" placeholder="Enter email subject...">
			                            </fieldset>
			                        </td>
			                    </tr>
			                    <tr valign="top">
			                        <th scope="row" class="zted_titledesc">
			                            <label for="donation_addi_contnt"><?php _e("Additional content", ZT_ED_TEXTDOMAIN); ?> <span class="cp-help-tip"></span></label>
			                        </th>
			                        <td class="zted_forminp">
			                            <fieldset>
			                                <legend class="zted_screen-reader-text"><span><?php _e("Additional content", ZT_ED_TEXTDOMAIN); ?></span></legend>
			                                <?php
			                                	$default_content = '<h4>Donation Receipt</h4>
														<ul>
														 	<li><strong>First Name: </strong>{{first_name}}</li>
														 	<li><strong>Last Name: </strong>{{last_name}}</li>
														 	<li><strong>Email: </strong>{{email}}</li>
														 	<li><strong>Date: </strong>{{date}}</li>
														</ul>
														{{donation_listing}}
														<h4>Thanks for donation!</h4>';
									    		$content   = (!empty($email_editor_cntnt))? $email_editor_cntnt : $default_content;
									    		$allowed_tags = array(
									    			'h4' => array(),
									    			'ul' => array(
												    	'id' => array(),
												    	'class' => array(),
												  	),
												  	'li' => array(
												    	'id' => array(),
												    	'class' => array(),
												  	),
												  	'p' => array(
												    	'id' => array(),
												    	'class' => array(),
												  	),
												  	'a' => array(
												    	'href' => array(),
												    	'title' => array()
												  	),
												  	'strong' => array(),
												);

												$sanitized_content = wp_kses( $content, $allowed_tags );
												
												$editor_id = 'zted_email_editor_content';
												$settings  = array( 'editor_height' => 300, 'media_buttons' => false );
												 
												wp_editor( $sanitized_content, $editor_id, $settings );

												echo '<p><b>'.__('Email Placeholders List: ',ZT_ED_TEXTDOMAIN).' </b>{{first_name}}, {{last_name}}, {{email}}, {{date}}, {{donation_listing}}</p>';
									    	?>
			                            </fieldset>
			                        </td>
			                    </tr>
			                </tbody>
			            </table>
				    </div>
				    <div id="zted_support_tab" class="zted_tab">
				        <div>
				        	<h4><?php _e("Need Help ?", ZT_ED_TEXTDOMAIN); ?></h4>
				        	<ul>
				        		<li>
				        			<span><?php _e("Developer: ", ZT_ED_TEXTDOMAIN); ?></span><a href="https://www.zestard.com/contact-us/" target="_blank"><?php _e("Zestard Technologies Pvt Ltd", ZT_ED_TEXTDOMAIN); ?></a>
				        		</li>
				        		<li>
				        			<span><?php _e("Email: ", ZT_ED_TEXTDOMAIN); ?></span><a href="mailto:support@zestard.com">support@zestard.com</a>
				        		</li>
				        		<li>
				        			<span><?php _e("Website: ", ZT_ED_TEXTDOMAIN); ?></span><a href="https://www.zestard.com/" target="_blank">https://www.zestard.com</a>
				        		</li>
				        	</ul>
				        </div>
				    </div>
				    <?php submit_button(); ?>
				</form>
	     	</div>
	     </div>

		<?php
		}
	}

	add_action( 'plugins_loaded' , function() {
		ZT_ED()->admin->action = new ZT_ED_Admin_Action;
	} );
}