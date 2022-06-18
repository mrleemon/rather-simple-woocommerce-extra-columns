<?php
/**
 * Plugin Name: Rather Simple WooCommerce Tax Class Column
 * Plugin URI:
 * Update URI: false
 * Description: Adds a tax class column to the admin products list.
 * Version: 1.0
 * WC tested up to: 4.4.1
 * Author: Oscar Ciutat
 * Author URI: http://oscarciutat.com/code/
 * Text Domain: rather-simple-woocommerce-tax-class-column
 * License: GPLv2 or later
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package rather_simple_woocommerce_tax_class_column
 */

/**
 * Core class used to implement the plugin.
 */
class Rather_Simple_WooCommerce_Tax_Class_Column {

	/**
	 * Plugin instance.
	 *
	 * @var object $instance
	 */
	protected static $instance = null;

	/**
	 * Access this plugin’s working instance
	 *
	 * @return object of this class
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Used for regular plugin work.
	 *
	 * @wp-hook plugins_loaded
	 * @return  void
	 */
	public function plugin_setup() {

		// Init.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		// Add columns.
		add_filter( 'manage_edit-product_columns', array( $this, 'product_tax_class_columns' ) );
		add_filter( 'manage_product_posts_custom_column', array( $this, 'product_tax_class_column' ) );

	}


	/**
	 * Constructor. Intentionally left empty and public.
	 */
	public function __construct() {}


	/**
	 * Enqueues scripts and styles in the frontend.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'wat-style', plugins_url( 'style.css', __FILE__ ) );
	}

	/**
	 * Adds a product tax class column.
	 *
	 * @param array $columns An associative array of column headings.
	 */
	public function product_tax_class_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $columns[ $key ];
			if ( 'price' === $key ) {
				$new_columns['tax_class'] = __( 'Tax class', 'woocommerce' );
			}
		}
		return $new_columns;
	}

	/**
	 * Displays product tax class.
	 *
	 * @param string $column  The name of the column to display.
	 */
	public function product_tax_class_column( $column ) {
		global $post, $product;
		if ( 'tax_class' === $column ) {
			// Excluding variable and grouped products.
			if ( is_a( $product, 'WC_Product' ) ) {
				$args = wc_get_product_tax_class_options();
				echo $args[ $product->get_tax_class() ];
			}
		}
	}

}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action( 'plugins_loaded', array( Rather_Simple_WooCommerce_Tax_Class_Column::get_instance(), 'plugin_setup' ) );
}
