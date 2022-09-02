<?php
/**
 * Plugin Name: Rather Simple WooCommerce Extra Columns
 * Plugin URI:
 * Update URI: false
 * Version: 1.0
 * Requires at least: 5.3
 * Requires PHP: 7.0
 * WC tested up to: 4.4.1
 * Author: Oscar Ciutat
 * Author URI: http://oscarciutat.com/code/
 * Text Domain: rather-simple-woocommerce-extra-columns
 * Description: Adds extra columns to the admin products list.
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
 * @package rather_simple_woocommerce_extra_columns
 */

/**
 * Core class used to implement the plugin.
 */
class Rather_Simple_WooCommerce_Extra_Columns {

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
		add_filter( 'manage_edit-product_columns', array( $this, 'product_extra_columns' ) );
		add_filter( 'manage_product_posts_custom_column', array( $this, 'product_extra_column' ) );
		add_filter( 'manage_edit-product_sortable_columns', array( $this, 'product_extra_sortable_columns' ) );

		// Make columns sortable.
		add_filter( 'request', array( $this, 'sort_meta_columns' ) );
		add_filter( 'posts_clauses', array( $this, 'sort_taxonomy_columns' ), 10, 2 );

	}


	/**
	 * Constructor. Intentionally left empty and public.
	 */
	public function __construct() {}


	/**
	 * Enqueues scripts and styles in the frontend.
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style(
			'wat-style',
			plugins_url( 'style.css', __FILE__ ),
			array(),
			filemtime( plugin_dir_path( __FILE__ ) . '/style.css' )
		);
	}

	/**
	 * Adds extra columns.
	 *
	 * @param array $columns An associative array of column headings.
	 */
	public function product_extra_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $column ) {
			$new_columns[ $key ] = $columns[ $key ];
			if ( 'price' === $key ) {
				$new_columns['tax_class']      = __( 'Tax class', 'woocommerce' );
				$new_columns['shipping_class'] = __( 'Shipping class', 'woocommerce' );
			}
		}
		return $new_columns;
	}

	/**
	 * Displays extra columns.
	 *
	 * @param string $column  The name of the column to display.
	 */
	public function product_extra_column( $column ) {
		global $post, $product;
		if ( 'tax_class' === $column ) {
			// Excluding variable and grouped products.
			if ( is_a( $product, 'WC_Product' ) ) {
				$args = wc_get_product_tax_class_options();
				echo $args[ $product->get_tax_class() ];
			}
		}
		if ( 'shipping_class' === $column ) {
			// Excluding variable and grouped products.
			if ( is_a( $product, 'WC_Product' ) ) {
				$shipping_class_id   = $product->get_shipping_class_id();
				$shipping_class_term = get_term( $shipping_class_id, 'product_shipping_class' );
				if ( ! is_wp_error( $shipping_class_term ) && is_a( $shipping_class_term, 'WP_Term' ) ) {
					echo $shipping_class_term->name;
				}
			}
		}
	}

	/**
	 * Define which extra columns are sortable.
	 *
	 * @param array $columns An array of existing columns.
	 */
	public function product_extra_sortable_columns( $columns ) {
		$custom = array(
			'tax_class'      => 'tax_class',
			'shipping_class' => 'shipping_class',
		);
		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Sort meta columns.
	 *
	 * @param array $vars  The array of requested query variables.
	 */
	public function sort_meta_columns( $vars ) {
		if ( isset( $vars['orderby'] ) && 'tax_class' === $vars['orderby'] ) {
			$vars = array_merge(
				$vars,
				array(
					'orderby'  => 'meta_value',
					'meta_key' => '_tax_class',
				)
			);
		}
		return $vars;
	}

	/**
	 * Sort taxonomy columns.
	 *
	 * @param array    $clauses   Associative array of the clauses for the query.
	 * @param WP_Query $wp_query  The WP_Query instance (passed by reference).
	 */
	public function sort_taxonomy_columns( $clauses, $wp_query ) {
		global $wpdb;
		if ( isset( $wp_query->query['orderby'] ) && 'shipping_class' === $wp_query->query['orderby'] ) {
			$clauses['join'] .= <<<SQL
				LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
				LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
				LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
				SQL;

				$clauses['where']   .= " AND (taxonomy = 'product_shipping_class' OR taxonomy IS NULL)";
				$clauses['groupby']  = 'object_id';
				$clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
				$clauses['orderby'] .= ( 'ASC' === strtoupper( $wp_query->get( 'order' ) ) ) ? 'ASC' : 'DESC';
		}
		return $clauses;
	}

}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {
	add_action( 'plugins_loaded', array( Rather_Simple_WooCommerce_Extra_Columns::get_instance(), 'plugin_setup' ) );
}
