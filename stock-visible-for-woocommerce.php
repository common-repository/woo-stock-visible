<?php
/*
Plugin Name: Stock Visible For WooCommerce
Description: Visible the “stock quantity” value on shop page with each product
Author: Ignite Media Solution
Version: 1.0
Author URI: https://www.ignitemediasolution.com
Text Domain: im-woo-stock-visible
Copyright 2019  Harshal Dhingra  (email : harshal@ignitemediasolution.com)
 * 
 *
 * WC requires at least: 2.6.14
 * WC tested up to: 3.8
*/

if (!defined('ABSPATH')){
    die();
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
define('IM_WOO_STOCK_VISIBLE_VERSION', '1.0' );
define('IM_WOO_STOCK_VISIBLE_DIR_NAME', plugin_basename(dirname(__FILE__)));
define('IM_WOO_STOCK_VISIBLE_BASE_URL', plugins_url() . '/' . IM_WOO_STOCK_VISIBLE_DIR_NAME);

if (is_plugin_active( 'woocommerce/woocommerce.php' )) {

add_action('plugins_loaded', 'imWooStockVisibleStart');
register_activation_hook(__FILE__, 'imWooStockVisibleActivate');

function imWooStockVisibleStart() {
    if(is_admin()){
        add_action('admin_enqueue_scripts','imWooStockVisibleLoadAdminScripts');
      
    }
}


function imWooStockVisibleActivate(){

    update_option('im_stock_visible_low_text_color', '#dd3333');
    update_option('im_stock_visible_normal_text_color', '#81d742');
}


function imWooStockVisibleLoadAdminScripts() {
    
    wp_enqueue_script('im-woo-stock-visible-admin-settings',IM_WOO_STOCK_VISIBLE_BASE_URL .'/admin/assets/js/im-woo-stock-visible-admin-settings.js', array( 'jquery','wp-color-picker' ),IM_WOO_STOCK_VISIBLE_VERSION);
    // Css rules for Color Picker
    wp_enqueue_style( 'wp-color-picker' );    
}


add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'imWooStockVisibleActionlinks' );
function imWooStockVisibleActionlinks( $links ) {
    
   $links[] = '<a href="'. esc_url( get_admin_url(null, '/admin.php?page=wc-settings&tab=products&section=inventory') ) .'">Settings</a>';
   $links[] = '<a href="https://www.ignitemediasolution.com/wordpress-plugins-ignite-media" target="_blank">More plugins by Harshal Dhingra</a>';
   return $links;
   
}

if(get_option('im_stock_visible_always') == 'yes' && get_option('im_stock_visible_always') !== null){
    add_action(get_option('im_stock_visible_where'),'imWooStockVisibleFront', 10);
}

    
function imWooStockVisibleFront() {
    global $product;

    $lowStockNotify = 3;
    $imStockOutput = '';
    // For low stock color 
    if(get_option('im_stock_visible_low_text_color')){
        $lowStockTextColor = get_option('im_stock_visible_low_text_color');
    }else{
        $lowStockTextColor = "#dd3333";
    }
    // For normal stock color 
    if(get_option('im_stock_visible_normal_text_color')){
        $normalStockTextColor = get_option('im_stock_visible_normal_text_color');
    }else{
        $normalStockTextColor = "#81d742";
    }
    
    
    if(get_option('woocommerce_notify_low_stock_amount')){
        $lowStockNotify = get_option('woocommerce_notify_low_stock_amount');
    }
    
    if ( !$product->is_type( 'variable' ) ){
        if( $product->get_stock_quantity() ) { // if manage stock is enabled
            if ( number_format($product->get_stock_quantity(),0,'','') < $lowStockNotify ) { // if stock is low
               echo '<div style="color: '.$lowStockTextColor.'" >'.__('Only '.number_format($product->get_stock_quantity(),0,'','').' left in stock').'</div>';
            } else {
               echo '<div style="color: '.$normalStockTextColor.'" >'.__(number_format($product->get_stock_quantity(),0,'','').' left in stock').'</div>';
            }
         
        }
    } else {
        if( $product->get_stock_quantity() ) { // if manage stock is enabled
            $product_variations = $product->get_available_variations();
            $stock = 0;
            foreach ($product_variations as $variation)  {
              $stock = $stock+$variation['max_qty'];
            }
            if($stock > 0){
                if ( number_format($stock,0,'','') < $lowStockNotify ) { // if stock is low
                    echo '<div style="color: '.$lowStockTextColor.'" class="im-low-stock-remain">'.__('Only '.number_format($stock,0,'','').' left in stock').'</div>';
                } else {
                    echo '<div style="color: '.$normalStockTextColor.'"  >'.__(number_format($stock,0,'','').' left in stock').'</div>';
                }
             
            }
        }
    }
}

/**
 * Add settings to the specific section we created before
 */

add_filter( 'woocommerce_get_settings_products', 'imWooStockVisibleSettings', 10, 2 );
function imWooStockVisibleSettings( $settings, $current_section ) {

    /**
     * Check the current section is what we want
     **/
    if ( $current_section == 'inventory' ) {
        
        $settings[] = array( 
            'name' => __( 'Ignite: Woo Stock Visible Settings:'),
            'type' => 'title',
            'desc' => __( 'The following options are used to configure how to stock visible on shop page' ),
            'id'   => 'im_stock_visible_options' 
        );
        
        $settings[] = array(
            'name' => __( 'Always stock visible'),
            'type' => 'checkbox',
            'desc' => __( 'Always visible available stock on shop page' ),
            'id'   => 'im_stock_visible_always'
        );

        $settings[] = array(
            'name' => __( 'Visible Stock position'),
            'type'    => 'select',
            'options' => array(
                'woocommerce_before_shop_loop_item_title' => __( 'Before title', 'woocommerce' ),
                'woocommerce_after_shop_loop_item_title'       => __( 'After title', 'woocommerce' ),
                'woocommerce_after_shop_loop_item'        => __( 'After shop loop (recommended)', 'woocommerce' )
            ),
            'desc' => __( 'Where the actual stock should be displayed on shop' ),
            'id'   => 'im_stock_visible_where'
        );
        
        $settings[] = array(
            'name' => __( 'Normal stock text color'),
            'type' => 'text',
            'desc' => __( 'You can set normal stock text color' ),
            'id'   => 'im_stock_visible_normal_text_color'
        );
        
        $settings[] = array(
            'name' => __( 'low stock text color'),
            'type' => 'text',
            'desc' => __( 'You can set low stock text color' ),
            'id'   => 'im_stock_visible_low_text_color'
        );
        
        $settings[] = array(
            'type' => 'sectionend',
            'id' => 'im_stock_visible_tab'
        );


    }
    return $settings;

}
   
}else {
    add_action( 'admin_notices', 'imWooStockVisibleInstallWoocommerceNotice');
}

function imWooStockVisibleInstallWoocommerceNotice() {
    echo '<div id="message" class="error fade"><p style="line-height: 150%">';
    _e('<strong>Woo - Stock Visible</strong></a> requires the Woocommerce plugin to work. Please <a href="https://woocommerce.com">install Woocommerce</a> first, or <a href="plugins.php">deactivate Woo Stock Visible</a>.', 'im-woo-stock-visible');
    echo '</p></div>';
}


add_action('wp_ajax_dismiss_im_woo_stock_visible_message', 'imWooStockVisibleDismissMessage' );
function imWooStockVisibleDismissMessage(){
    check_ajax_referer( 'im_woo_stock_visible_security_ajax', 'security');
    update_option('dismiss_im_woo_stock_visible_message',IM_WOO_STOCK_VISIBLE_VERSION);
    die();
}

add_action( 'admin_notices', 'imWooStockVisibleAddonsNotice');
function imWooStockVisibleAddonsNotice() {
    $imSecurity = wp_create_nonce( "im_woo_stock_visible_security_ajax" );
    
    if(get_option('dismiss_im_woo_stock_visible_message') != IM_WOO_STOCK_VISIBLE_VERSION) {
        echo '<div class="im-woo-stock-visible-notice info notice-info notice">';
        echo '<p>' . __('Thanks for using <span style="font-weight: bold;"> Woo Stock Visible!</span> ', 'im-woo-stock-visible');
        echo '<a type="button" class="im-woo-stock-visible-addons-button button button-primary" href="'.esc_url( get_admin_url(null, '/admin.php?page=wc-settings&tab=products&section=inventory') ).'">Settings</a> ';
        echo '<a type="button" class="im-woo-stock-visible-addons-button button button-primary" target="_blank" href="https://profiles.wordpress.org/hdhingra/#content-plugins">Check out our more plugins</a>';
        echo '</p>';
        echo '<p>' . __('<span style="font-weight: bold;">Need Help ? OR Want new site built ?</span> You can hire professional assistance with us: <a target="_blank" href="https://www.ignitemediasolution.com">Ignite Media Solution</a> | <a target="_blank" href="https://www.itechmediasolution.com/">iTech Media Solution</a>', 'im-woo-stock-visible');
        echo '</p>';
         echo '<p>' . __('You can email us as well <a href="mailto:harshal@ignitemediasolution.com">harshal@ignitemediasolution.com</a>', 'im-woo-stock-visible');
        echo '</p>';
        echo '<button type="button" class="im-woo-stock-visible-dismiss-notice notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>';
        echo '</div>';

        echo '  <script>
                    jQuery(function(){
                        jQuery(".im-woo-stock-visible-dismiss-notice").on("click", function(){
                            jQuery(".im-woo-stock-visible-notice").fadeOut();
                            
                            jQuery.ajax({
                                type: "post",
                                url: "'.admin_url( 'admin-ajax.php' ).'",
                                data: {action: "dismiss_im_woo_stock_visible_message", security: "'.$imSecurity.'"}
                            })                        
                                    
                        })                    
                    })
                </script>';
    }
    echo '
    <style>    
            .im-woo-stock-visible-notice{
                background: #333 url("'.IM_WOO_STOCK_VISIBLE_BASE_URL. '/assets/images/pattern.png") no-repeat;
                background-size: cover;
                color: #FFF;
                min-height: 48px;
                border-left-color: #edb44d!important;
            }
            .im-woo-stock-visible-notice a{
                 color: #edb44d!important;
                 font-weight: bold;
            }
            .im-woo-stock-visible-notification {
                float: left !important;
                width: 100% !important;
            }
                       
            .im-woo-stock-visible-notification a{
                display: inline !important;
                padding: 0 !important;
                min-width: 0 !important;
            }
            .im-woo-stock-visible-settings-button:before{
                background: 0 0;
                color: #fff;
                content: "\f111";
                display: block;
                font: 400 16px/20px dashicons;
                speak: none;
                height: 29px;
                text-align: center;
                width: 16px;
                float: left;
                margin-top: 3px;
                margin-right: 4px;
            }
            
            .im-woo-stock-visible-addons-button:before{
                background: 0 0;
                color: #fff;
                content: "\f106";
                display: block;
                font: 400 16px/20px dashicons;
                speak: none;
                height: 29px;
                text-align: center;
                width: 16px;
                float: left;
                margin-top: 3px;
                margin-right: 4px;
            }
            .im-woo-stock-visible-addons-button, .im-woo-stock-visible-addons-button:visited,.im-woo-stock-visible-addons-button:active{
                background: #edb44d !important;
                border-color: #edb44d !important; 
                color: #fff !important;
                text-decoration: none !important;
                text-shadow: none!important;
                box-shadow: none !important;
            }
            
            .im-woo-stock-visible-addons-button:hover{
                background:#46beff !important;
                border-color: #46beff !important; 
            }
            
            .im-woo-stock-visible-dismiss-notice{
                top:5px        
            }
            .im-woo-stock-visible-dismiss-notice:hover:before, .im-woo-stock-visible-dismiss-notice:focus:before, .im-woo-stock-visible-dismiss-notice:visited:before{
                color:#46beff !important;
            }
                        
            .im-woo-stock-visible-notice{
                position:relative
            }
    </style>';

}