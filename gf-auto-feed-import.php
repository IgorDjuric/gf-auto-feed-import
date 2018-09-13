<?php
/**
 * Plugin Name
 *
 * @package     PluginPackage
 * @author      Green Friends
 * @copyright   2018 Green Friends
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: GF Auto Feed Import
 * Plugin URI:  https://example.com/plugin-name
 * Description: auto feed import
 * Version:     1.0.0
 * Author:      Green Friends
 * Author URI:  https://example.com
 * Text Domain: gf-auto-feed-import
 * Domain Path: /languages
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */


function gf_auto_feed_import_options_create_menu()
{
    //create new top-level menu
    add_menu_page('Auto feed import', 'Auto feed import', 'administrator', 'gf_auto_feed_import_options', 'gf_auto_feed_import_options_page', null, 99);

}

add_action('admin_menu', 'gf_auto_feed_import_options_create_menu');


function gf_auto_feed_import_options_page()
{
    $curl = curl_init();

// set url
    curl_setopt($curl, CURLOPT_URL, "https://www.vitapur.si/media/feed/non-stop-shop-rs.xml?rand=1533891503.2719");

//return the transfer as a string
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

// $output contains the output string
    $response = curl_exec($curl);

// close curl resource to free up system resources
    curl_close($curl);

    $xml = simplexml_load_string($response, null, LIBXML_NOCDATA);

    global $wpdb;

    foreach ($xml->proizvod as $product) {
        $position = 0;

        $vendor_sku_json = json_encode($product->sku);
        $vendor_sku = json_decode($vendor_sku_json)->$position;

        $category_json = json_encode($product->kategorija);
        $category = json_decode($category_json)->$position;

        $name_json = json_encode($product->naziv);
        $name = json_decode($name_json)->$position;

        $stock_status_json = json_encode($product->dostupnost);
        $stock_status = json_decode($stock_status_json)->$position;

        $short_description_json = json_encode($product->kratak_opis);
        $short_desctiption = json_decode($short_description_json)->$position;

        $description_json = json_encode($product->opis);
        $description = json_decode($description_json)->$position;

        $image_json = json_encode($product->slika);
        $image = json_decode($image_json)->$position;

        $regular_price_json = json_encode($product->mp_cena);
        $regular_price = json_decode($regular_price_json)->$position;
        $regular_price_clean = (int)str_replace(',', '', $regular_price);

        $sale_price_json = json_encode($product->akcijska_cena);
        $sale_price = json_decode($sale_price_json)->$position;
        $sale_price_clean = (int)str_replace(',', '', $sale_price);

        $input_price_json = json_encode($product->vp_cena);
        $input_price = json_decode($input_price_json)->$position;
        $input_price_clean = (int)str_replace(',', '', $input_price);


        $product_id = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $wpdb->postmeta WHERE meta_key='vendor_code' AND meta_value='%s' LIMIT 1", $vendor_sku));

        if ($product_id) {
            $product_update = new WC_Product($product_id);
            $product_update->set_sale_price($sale_price_clean);
            $product_update->set_regular_price($regular_price_clean);
            update_post_meta($product_id, 'input_price', $input_price_clean);

            /*TODO proveriti za status izgleda da zbog ovoga baguje da mora da se uradi publish*/
//            $product_update->set_status($data[4]);
//            wp_update_post(array(
//                'ID' => $product_id,
//                'post_status' => 'publish'
//            ));
            $product_update->save();

        } else {
            $new_post = array(
                'post_title' => $name,
                'post_content' => $description,
                'post_status' => 'publish',
                'post_type' => "product",
                'post_excerpt' => $short_desctiption
            );
            $post_id = wp_insert_post($new_post);

            update_post_meta($post_id, '_regular_price', $regular_price_clean);
            update_post_meta($post_id, '_sale_price', $sale_price_clean);
            update_post_meta($post_id, 'input_price', $input_price_clean);
            update_post_meta($post_id, 'vendor_code', $vendor_sku);

//            /*TODO proveriti za kategorije */
//            /*TODO proveriti kako podesiti sliku */
//            /*TODO proveriti dostupnost */
        }
    }
}