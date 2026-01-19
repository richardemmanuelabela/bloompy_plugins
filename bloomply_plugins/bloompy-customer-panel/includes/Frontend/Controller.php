<?php
namespace Bloompy\CustomerPanel\Frontend;
use BookneticApp\Providers\Core\Controller as BaseController;
use function \BloompyAddon\WooCommerceBridge\bkntc__;


class Controller extends BaseController
{
    /**
	 * @param $meta_key
	 * @param $meta_value
	 * @return false|string
	 */
	public static function get_post_id_by_postmeta( $meta_key, $meta_value ) {
		global $wpdb;
		$post_id = $wpdb->get_var( $wpdb->prepare("
			SELECT post_id 
			FROM $wpdb->postmeta 
			WHERE meta_key = %s AND meta_value = %s 
			LIMIT 1
		", $meta_key, $meta_value));
		if ($post_id) {
			return $post_id;
		} else {
			return false;
		}
	}
}