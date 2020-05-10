<?php
/**
 * AJAX Shortcode Abstract Class
 *
 * Provides a base for creating new shortcodes that use AJAX.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * PHP Version 7.2
 *
 * @package    WPS\WP\Shortcodes
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2020 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/Shortcode
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\WP\Shortcodes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\AjaxShortcode' ) ) {
	/**
	 * Class AjaxShortcode
	 *
	 * @package WPS\WP\Shortcodes
	 */
	abstract class AjaxShortcode extends Shortcode {

		/**
		 * Whether to expose ajax to external users.
		 *
		 * @var bool
		 */
		protected $nopriv = true;

		/**
		 * WP Nounce
		 *
		 * @var string
		 */
		protected $nonce = true;

		/**
		 * Init stuffs.
		 */
		public function init() {
			add_action( "wp_ajax_{$this->name}_action", [ $this, 'callback' ] );
			if ( $this->nopriv ) {
				add_action( "wp_ajax_nopriv_{$this->name}_action", [ $this, 'callback' ] );
			}
		}

		/**
		 * Helper to determine whether AJAX is running.
		 *
		 * @return bool
		 */
		public static function doing_ajax() {
			return ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		}

		/**
		 * The ajax method that does the work.
		 *
		 * @param array $data Form data.
		 *
		 * @return mixed
		 */
		abstract public function ajax( $data );

		/**
		 * The ajax callback
		 */
		public function callback() {
			// Prepare data.
			$data = array_merge( array_map( 'esc_attr', $_GET ), $_POST );
			if ( is_string( $data['data'] ) ) {
				wp_parse_str( $data['data'], $data['data'] );
			}

			// Bail if stuff isn't right.
			if ( $this->should_bail( $data ) ) {
				wp_send_json_error();
			}

			$this->ajax( $data['data'] );
		}

		/**
		 * Determines whether the AJAX operation should bail or not.
		 *
		 * @param array $data Associative array of data.
		 *
		 * @return bool
		 */
		protected function should_bail( $data ) {
			return (
				! self::doing_ajax() || // Bail if not doing AJAX.
				0 === count( $data['data'] ) || // Bail if there is no data
				! check_ajax_referer( $data['action'], "_ajax_nonce", false ) // Bail if nonce isn't correct.
			);
		}

		/**
		 * Gets the ajax action.
		 *
		 * @return string
		 */
		protected function get_action() {
			return "{$this->name}_action";
		}

		/**
		 * Creates a randomized string.
		 *
		 * @param int $length
		 *
		 * @return bool|string|void
		 */
		public static function get_random_string( $length = 5 ) {
			$permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

			return substr( str_shuffle( $permitted_chars ), 0, $length );

		}

	}
}
