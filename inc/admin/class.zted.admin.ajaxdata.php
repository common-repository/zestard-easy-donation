<?php
/**
 * ZT_ED_Admin_DB_Action Class
 *
 * Zestard Easy Donation functionality.
 *
 * @package WordPress
 * @subpackage Zestard Easy Donation Admin Actions
 * @since 1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'ZT_ED_Admin_DB_Action' ) ){

    /**
     *  The ZT_ED_Admin_DB_Action Class
     */
    class ZT_ED_Admin_DB_Action {

        function __construct()  {
            add_action( 'wp_ajax_select_track_donation', array($this, 'select_track_donation'));
            add_action( 'wp_ajax_nopriv_select_track_donation', array($this, 'select_track_donation') );
        }
        
        function select_track_donation(){

            $selected_option = sanitize_text_field($_POST['selected_donation_name']);
            $selected_year = (isset( $_POST['selected_donation_year'] ) && !empty( $_POST['selected_donation_year'] )) ? sanitize_text_field($_POST['selected_donation_year']) : "";

            if(!empty($selected_option)){
                $donation_orders = wc_get_orders( 
                    array(
                        'numberposts' => -1,
                    ) 
                );
            } else {
                $donation_orders = wc_get_orders( 
                    array(
                        'numberposts' => -1,
                        'date_query' => array(
                            'after' => date('Y-m-d', strtotime('first day of january this year')),
                            'before' => date('Y-m-d', strtotime(' +1 day')) 
                        )
                    ) 
                );
            }

            $x_y_array = array();
            // Loop through each Order object
            foreach( $donation_orders as $donation_curr_order ){
                
                /* Get donation amount of current order */
                $order_meta = $donation_curr_order->get_items('fee');

                $order_date = date("F", strtotime($donation_curr_order->get_date_created()));

                $order_created_date = $donation_curr_order->get_date_created()->format ('Y');

                foreach( $order_meta as $item_id => $item_fee ){
                    if(!empty($selected_option)){
                        
                        if($item_fee['name'] == $selected_option && $order_created_date == $selected_year){       

                            if (array_key_exists($order_date, $x_y_array)) {
                                //Sum of total donation amount if key match in array like month/date
                                $x_y_array[$order_date] = (int)$x_y_array[$order_date] + (int)$item_fee->get_total();
                            }else{
                                //Insert donation amount if key not match in array like month/date
                                //$x_y_array[$order_date] = (int)$x_y_array[$order_date] + (int)$item_fee->get_total();
                                $x_y_array[$order_date] = (int)$item_fee->get_total();
                            }
                        }

                    } else {

                        if (array_key_exists($order_date, $x_y_array)) {
                            //Sum of total donation amount if key match in array like month/date
                            $x_y_array[$order_date] = (int)$x_y_array[$order_date] + (int)$item_fee->get_total();
                        }else{
                            //Insert donation amount if key not match in array like month/date
                            //$x_y_array[$order_date] = (int)$x_y_array[$order_date] + (int)$item_fee->get_total();
                            $x_y_array[$order_date] = (int)$item_fee->get_total();
                        }
                    }
                }
            }

            /* Create datapoints for track donation */
            $dataPoints = array();
            
            $month_array = array('January' => 0, 'February' => 0, 'March' => 0, 'April' => 0, 'May' => 0, 'June' => 0, 'July' => 0, 'August' => 0, 'September' => 0, 'October' => 0, 'November' => 0, 'December' => 0);
            
            $merge_arr_result=array_merge($month_array,$x_y_array);

            foreach ($merge_arr_result as $combine_arr_key => $combine_arr_value) {
                $dataPoints[$combine_arr_key] = $combine_arr_value;
            }
            
            if(!empty($dataPoints)){
                print_r(json_encode($dataPoints));
            } else {
                echo '<span class="zted_tracking_status">'.__('Track donation data not available!', ZT_ED_TEXTDOMAIN).'</span>';
            }

            wp_die();
        } 
    }

    add_action( 'plugins_loaded' , function() {
        ZT_ED()->admin->dbaction = new ZT_ED_Admin_DB_Action;
    } );
}
?>
