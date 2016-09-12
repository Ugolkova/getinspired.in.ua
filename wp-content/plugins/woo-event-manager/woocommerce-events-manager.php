<?php

/*******
 ******************************************
 * Plugin Name: WooCommerce Events Manager
 *
 * Plugin URI: http://cybersourcepk.com
 *
 * Description: A plugin for events managements based on Woo-commerce.
 *
 * Version: 1.1.0
 *
 * Author: CybersourcePK
 *
 * Author URI: http://cybersourcepk.com/
 *
 * Text Domain: wooevents
 *
 * Domain Path: /languages
 *
 * Copyright: © 2010-2016 CybersourcePK.
 *
 * License: GNU General Public License v3.0
 *
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 **************
 ********************************************************/




if ( ! defined( 'ABSPATH' ) ) { 

    exit; // Exit if accessed directly

}


/**
 *
 * Check if WooCommerce is active
 *
 **/

add_action( 'admin_init', 'child_plugin_has_parent_plugin' );
function child_plugin_has_parent_plugin() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'child_plugin_notice' );

        deactivate_plugins( plugin_basename( __FILE__ ) ); 

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}

function child_plugin_notice(){
    ?><div class="error"><p>Sorry, but this Plugin requires WooCommerce plugin to be installed and active.</p></div><?php
}

add_action( 'init', 'wooevent_load_textdomain' );
/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function wooevent_load_textdomain() {
  load_plugin_textdomain( 'wooevents', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}


/**

 * Event Tab Front

 *

 * Creating widget

 */

define( 'CCW_PLUGIN_NAME', 'Custom Calendar Widget' );
define( 'CCW_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'CCW_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'CCW_SITE_BASE_URL',get_bloginfo('url'));
define( 'CCW_TOTAL_MONTHS',24);
define( 'CCW_MONTHS_DEFAULT',3);


require_once CCW_PLUGIN_PATH.'widget/ccw_class.php';



/**

 * Registering the scripts now

 *

 */



function register_scripts(){



	global $typenow;



	// checking for add product and edit product page.

	// include timepicker for product page only



	if (is_edit_page('edit') && "product" == $typenow || is_edit_page('new') && "product" == $typenow){



	    wp_register_script( 'timepicker-script', plugins_url( '/js/timepicker.js', __FILE__ ) , array('jquery'),'', false );
		wp_enqueue_script( 'timepicker-script' );
		// Localize the script with new data
		$translation_array = array(
			'january' => __( 'January', 'wooevents' ),
			'february ' => __( 'February', 'wooevents' ),
			'march' => __( 'March', 'wooevents' ),
			'april' => __( 'April', 'wooevents' ),
			'may' => __( 'May', 'wooevents' ),
			'june' => __( 'June', 'wooevents' ),
			'july' => __( 'July', 'wooevents' ),
			'august' => __( 'August', 'wooevents' ),
			'september' => __( 'September', 'wooevents' ),
			'october' => __( 'October', 'wooevents' ),
			'november' => __( 'November', 'wooevents' ),
			'december' => __( 'December', 'wooevents' ),
			'monday' => __( 'Monday ', 'wooevents' ),
			'tuesday' => __( 'Tuesday ', 'wooevents' ),
			'wednesday' => __( 'Wednesday ', 'wooevents' ),
			'thursday ' => __( 'Thursday', 'wooevents' ),
			'friday' => __( 'Friday ', 'wooevents' ),
			'saturday' => __( 'Saturday', 'wooevents' ),
			'sunday' => __( 'Sunday', 'wooevents' ),
			'hrs' => __( 'hrs', 'wooevents' ),

		);
		
		wp_localize_script( 'timepicker-script', 'object_name', $translation_array );
	    wp_register_script( 'custom-script', plugins_url( '/js/custom.js', __FILE__ ), array('timepicker-script') );
	    wp_register_style(  'timepicker-style', plugins_url( '/css/timepicker.css', __FILE__ ) );

	    wp_enqueue_script( 'custom-script' );
	    wp_enqueue_style( 'timepicker-style' );

	}

}


add_action( 'admin_enqueue_scripts', 'register_scripts' );


/**

 * is_edit_page 

 * function to check if the current page is a product edit or edit add page

 * 

 */

function is_edit_page($new_edit = null){

    global $pagenow;

    //make sure we are on the backend

    if (!is_admin()) return false;





    if($new_edit == "edit")

        return in_array( $pagenow, array( 'post.php',  ) );

    elseif($new_edit == "new") //check for new post page

        return in_array( $pagenow, array( 'post-new.php' ) );

    else //check for either new or edit

        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );

}







/**

 * Process meta

 *

 * Processes the Events tab options when a post is saved

 */

function process_product_meta_event_tab( $post_id ) {


	if($_POST['event_date']!=''){
		
		// Update Event Date if set
		
		$time = new DateTime($_POST['event_date'].''. $_POST['event_time_start']);
		$stamp = $time->format('Y-m-d H:i');
		update_post_meta( $post_id, 'event_datetime', $stamp);

	}else{
		
		// Update Event Date if not set
		
		update_post_meta( $post_id, 'event_datetime', '');
	}


			// Update Event End Date

		update_post_meta( $post_id, 'event_end_date', $_POST['event_end_date']);

	

			// Update add to cart custom link

		update_post_meta( $post_id, 'custom_add_to_cart', $_POST['custom_add_to_cart']);
	



}

add_action('woocommerce_process_product_meta', 'process_product_meta_event_tab', 10, 2);

  



  

/**

 * Render the Event product tab panel content for the callback 'event_product_tabs_panel_content'

 */

function event_product_tabs_panel_content( $key, $event_tab_options ) {

			_e('<h2>' . $event_tab_options['title'] . '</h2>', 'wooevents');
            _e( $event_tab_options['content'] , 'wooevents');

    }



  

function events_tab_options() { ?>

        <li class="events_tab"><a href="#events_tab"><?php _e('Events Manager', 'wooevents'); ?></a></li>

<?php }



add_action('woocommerce_product_write_panel_tabs', 'events_tab_options');

  

  



/**

 * Event Tab Options

 *

 * Provides the input fields and add/remove buttons for Event tab on the single product page.

 */

function event_tab_options() {

        global $post;
            $event_tab_options = array(   

                    'event_date' => (get_post_meta($post->ID, 'event_datetime', true) != '' ? date('d-m-Y',strtotime(get_post_meta($post->ID, 'event_datetime', true))) : ''),     
                    
                    'event_end_date' => (get_post_meta($post->ID, 'event_end_date', true) != '' ? date('d-m-Y',strtotime(get_post_meta($post->ID, 'event_end_date', true))) : ''),     

                    'event_time' => date('H:i',strtotime(get_post_meta($post->ID, 'event_datetime', true))),        

                    'custom_add_to_cart' => get_post_meta($post->ID, 'custom_add_to_cart', true),     

            );

?>

        <div id="events_tab" class="panel woocommerce_options_panel">

               

                

            <div class="options_group event_tab_options">                                                                        

                    <p class="form-field">

                            <label><?php _e('Select Event Date :', 'wooevents'); ?></label>

                            <input type="text" name="event_date" id="event_date" value="<?php echo @$event_tab_options['event_date']; ?>" placeholder="<?php _e('Enter Product Event Date', 'wooevents'); ?>" />

                    </p>

    		</div>



            <div class="options_group event_tab_options">                                                                        

                    <p class="form-field">

                            <label><?php _e('Select Event End Date (optional) :', 'wooevents'); ?></label>

                            <input type="text" name="event_end_date" id="event_end_date" value="<?php echo @$event_tab_options['event_end_date']; ?>" placeholder="<?php _e('Enter Product Event End Date', 'wooevents'); ?>" />

                    </p>

            </div>




    		<div class="options_group event_tab_options">                                                                        

                    <p class="form-field">

                            <label><?php _e('Event Starting Time :', 'wooevents'); ?></label>

                            <input type="text" name="event_time_start" id="event_time_start" value="<?php echo @$event_tab_options['event_time']; ?>" placeholder="<?php _e('Enter Product Event Starting Time', 'wooevents'); ?>" />

                    </p>

    		</div>





    		<div class="options_group event_tab_options">                                                                        

                    <p class="form-field">

                            <label><?php _e('Custom Add To Cart link :', 'wooevents'); ?></label>

                            <input type="text" name="custom_add_to_cart" id="custom_add_to_cart" value="<?php echo @$event_tab_options['custom_add_to_cart']; ?>" placeholder="<?php _e('Enter Custom Link for Add To Cart button', 'wooevents'); ?>" />

                    </p>

    		</div>





        </div>

<?php

}

add_action('woocommerce_product_write_panels', 'event_tab_options');



/**

 * Event Tab Front

 *

 * Display Event Date on Shop page

 */

function get_woo_event_dates( $product_id = ''){
	
	global $post,$woocommerce,$product;
	
	if( '' != $product_id )
		$product_id = $product_id;
	else
		$product_id = get_the_ID();
	
	
    $event_Datetime = get_post_meta( $product_id ,'event_datetime',true);
	$event_end_Date = get_post_meta( $product_id ,'event_end_date',true);
	$end_Date = get_post_meta( $product_id ,'event_end_date',true);


    if($event_Datetime && $event_Datetime !=''):

        if($event_end_Date && $event_end_Date !=''){

            $event_end_Date = ' - '. date_i18n("l, j F Y",strtotime($event_end_Date) );
            $time =  '';
        }else{
			//$hrs = __('u');
			$time = date_i18n(", H:i ",strtotime($event_Datetime)) ;
            $event_end_Date = '';

        }
		//strtotime($end_Date). strtotime(date('d-m-Y'));
        $start_date =  date_i18n("l, j F Y",strtotime($event_Datetime));
		if($end_Date!='' and strtotime($end_Date)<strtotime(date('d-m-Y'))){
			$color='red';
		}
        return '<span class="event_datetime" style="font-size: 12px; color:'.$color.'">'.date_i18n("l, j F Y",strtotime($event_Datetime)).$event_end_Date.$time. '</span>' ;
       
    endif;
}

add_action( 'woocommerce_before_shop_loop_item_title','display_event_date',0 );

function display_event_date(){
	
     echo get_woo_event_dates();
}

function woo_event_order_item_name( $name, $item ){
    
    $product_id = $item['product_id'];
	$name .= get_woo_event_dates( $product_id );
	return $name  ;

}
add_filter( 'woocommerce_order_item_name', 'woo_event_order_item_name', 10, 2 );
/**

 * Event Tab Front

 *

 * Display Event Date on Single Product

 */



add_action( 'woocommerce_single_product_summary','event_on_single_page',1 );



function event_on_single_page(){
	
    global $post,$woocommerce;
	echo get_woo_event_dates();
	
}


/**

 * Event Tab Front
 *
 * Display Buy Tickets Button Product Listing Page
 */



/*STEP 1 - REMOVE ADD TO CART BUTTON ON PRODUCT ARCHIVE (SHOP) */



function remove_loop_button(){

	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
	
}

add_action('init','remove_loop_button');



/*STEP 2 -ADD NEW BUTTON THAT LINKS TO PRODUCT PAGE FOR EACH PRODUCT */



add_action('woocommerce_after_shop_loop_item','replace_add_to_cart');



function replace_add_to_cart() {

	global $product;

	$link = $product->get_permalink();
 
   $event_add_to_cart_custom_url = get_post_meta($product->id,'custom_add_to_cart',true);
	$event_date = get_post_meta($product->id,'event_datetime',true);
//echo $product->id; exit;
   if($event_add_to_cart_custom_url != '') {
   		// now add new button with custom link
		_e('<a href="'.$event_add_to_cart_custom_url.'" class="button addtocartbutton">TICKETS</a>', 'wooevents');
    }else{

	//if the product is in stock
		if($event_date!=''){
		_e( do_shortcode('<a href="'.$link.'" class="button addtocartbutton">TICKETS </a>'), 'wooevents');
		} else{
			_e( do_shortcode('<a rel="nofollow" href="/cf_test/?add-to-cart='.$product->id.'" data-quantity="1" data-product_id="'.$product->id.'" data-product_sku="" class="button product_type_simple add_to_cart_button ajax_add_to_cart">ADD TO CART</a>'), 'wooevents');
			
		}

    }
}

add_filter( 'woocommerce_product_single_add_to_cart_text', 'woo_custom_cart_button_text' );    // < 2.1

function woo_custom_cart_button_text() {
 global $product;
 $event_date = get_post_meta($product->id,'event_datetime',true);
 if($event_date!=''){
        return __( 'Book Now', 'wooevents' );
 } else{
	 return __( 'Add to cart', 'wooevents' );
 }
 
}

/**

 * Event Tab Front
 *
 * Order Shop page by Event Date and time

 */


add_filter('woocommerce_get_catalog_ordering_args', 'event_woocommerce_catalog_orderby');

function event_woocommerce_catalog_orderby( $args ) {

    $args['meta_key'] = 'event_datetime';

    $args['orderby'] = ('meta_value');
    $args['order'] = 'asc';
    return $args;

}




/**
 * Event Tab Front
 *
 * Modifying shop page query to show the events only if the event date is greater than today date

 */



add_action( 'pre_get_posts', 'rc_modify_query_get_posts_by_date' );

function rc_modify_query_get_posts_by_date( $query ) {

	// Check if on frontend and main query is modified

	if( ! is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'product' && is_shop() ) {

        $query->set( 'order', 'desc' );

        add_filter( 'posts_where', 'rc_filter_where' );

    }

   if( ! is_admin() && $query->is_main_query() && $query->query_vars['post_type'] == 'product' && is_page('passed-events') ) {

        $query->set( 'order', 'desc' );

        add_filter( 'posts_where', 'rc_filter_where' );

    }

    return $query;

}


/**
 * Event Tab Front
 *
 * Joining postmeta in query to get post by event_date meta key

 */



function custom_posts_join($join){

	 global $wpdb;



	 if(is_shop() && !is_admin()){

	 $join .= " LEFT JOIN $wpdb->postmeta as meta_1 ON $wpdb->posts.ID = meta_1.post_id LEFT JOIN $wpdb->postmeta as meta_2 ON $wpdb->posts.ID = meta_2.post_id";
    
	}
	
	if(is_page('passed-events') && !is_admin() ){
		// echo 'passed events '; exit;

	 $join .= " LEFT JOIN $wpdb->postmeta as meta_1 ON $wpdb->posts.ID = meta_1.post_id LEFT JOIN $wpdb->postmeta as meta_2 ON $wpdb->posts.ID = meta_2.post_id";
    }

	 return $join;

}



add_filter( 'posts_join' , 'custom_posts_join');





/**

 * Event Tab Front

 *

 * Filtering now. Adding query String

 */

function rc_filter_where( $where = '' ) {



    if(is_shop()){

    $where .= " AND meta_1.meta_key='event_datetime' AND meta_1.meta_value >= NOW()";

    }

	if(is_page('passed-events')){

    $where .= " AND meta_1.meta_key='event_datetime' AND meta_1.meta_value < NOW()";

    }

    return $where;

}



/**
 * Event Tab Front
 *
 * Add event date time to cart and checkout page
 */

function woo_event_dates_on_checkout( $product_link, $cart_item, $cart_item_key ) {
	
	$product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
	$product_link .= ' ';
    $product_link .= get_woo_event_dates( $cart_item['product_id'] );
	return   $product_link;
	
}

add_filter( 'woocommerce_cart_item_name', 'woo_event_dates_on_checkout', 10, 3 );


/**
 * Event Tab Front
 *
 * Disable Add to cart purchasing if the custom link for product is available
 */

function is_extrnal_link_to_event( $purchasable, $product ){
    global $post;

    $event_add_to_cart_custom_url = get_post_meta($post->ID,'custom_add_to_cart',true);
	if($event_add_to_cart_custom_url && $event_add_to_cart_custom_url != '')
        $purchasable = false;

    return $purchasable;
}
 add_filter( 'woocommerce_is_purchasable', 'is_extrnal_link_to_event', 10, 2 );



/**
 * Event Tab Front
 *
 * Adding custom button to single page if the custom add to cart link is available
 */

add_action( 'woocommerce_single_product_summary', 'event_custom_url_button_on_product_page', 30 );

function event_custom_url_button_on_product_page() {
  global $post;
 
   $event_add_to_cart_custom_url = get_post_meta($post->ID,'custom_add_to_cart',true);
   
   if($event_add_to_cart_custom_url != '') {
   		// now add new button with custom link
		_e( '<a href="'.$event_add_to_cart_custom_url.'" class="button addtocartbutton">TICKETS</a>', 'wooevents');
    }

}


if ( ! function_exists( 'product_attribute' ) ) {
 function product_attribute( $atts ) {
    $atts = shortcode_atts( array(
      'per_page'  => '12',
      'columns'   => '4',
      'orderby'   => 'title',
      'order'     => 'asc',
      'attribute' => '',
      'filter'    => ''
    ), $atts );

    $query_args = array(
      'post_type'           => 'product',
      'post_status'         => 'publish',
      'ignore_sticky_posts' => 1,
      'posts_per_page'      => $atts['per_page'],
      'orderby'             => $atts['orderby'],
      'order'               => $atts['order'],
      'meta_query'          => WC()->query->get_meta_query(),
      'tax_query'           => array(
        array(
          'taxonomy' => strstr( $atts['attribute'], 'pa_' ) ? sanitize_title( $atts['attribute'] ) : 'pa_' . sanitize_title( $atts['attribute'] ),
          'terms'    => array_map( 'sanitize_title', explode( ',', $atts['filter'] ) ),
          'field'    => 'slug'
        )
      )
    );

   // return self::product_loop( $query_args, $atts, 'product_attribute' );
    return $this->product_loop( $query_args, $atts, 'product_attribute' );
  }
}