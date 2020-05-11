<?php
/**
 * Shortcode Abstract Class
 *
 * Provides a base for creating new shortcodes.
 *
 * You may copy, distribute and modify the software as long as you track changes/dates in source files.
 * Any modifications to or software including (via compiler) GPL-licensed code must also be made
 * available under the GPL along with build & install instructions.
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

use WPS\Core\Singleton;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( __NAMESPACE__ . '\Shortcode' ) ) {
	/**
	 * Shortcode Abstract Class
	 *
	 * Assists in creating Shortcodes.
	 *
	 * @package WPS\WP
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	abstract class Shortcode extends Singleton {

		/**
		 * Shortcode name.
		 *
		 * @var string
		 */
		public $name;

		/**
		 * Whether the shortcode is active on the page.
		 *
		 * @var bool
		 */
		protected $is_active = false;

		/**
		 * The current shortcode attributes.
		 *
		 * @var array
		 */
		protected $atts = array();

		/**
		 * The current shortcode content.
		 *
		 * @var array
		 */
		protected $content = '';

		/**
		 * Shortcode constructor.
		 */
		protected function __construct() {
			$this->maybe_do_action( 'plugins_loaded', array( $this, 'add_shortcode' ) );

			if ( method_exists( $this, 'init' ) ) {
				$this->maybe_do_action( 'init', array( $this, 'init' ) );
			}

			if ( method_exists( $this, 'register_scripts' ) ) {
				$this->maybe_do_action( 'init', array( $this, 'register_scripts' ) );
			}

			$this->maybe_do_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_scripts' ) );
		}

		/**
		 * Hooks action or executes action.
		 *
		 * @since  1.0.0
		 * @author Travis Smith <t@wpsmith.net>
		 *
		 * @param string       WordPress action to be checked with did_action().
		 * @param string|array Function name/array to be called.
		 *
		 * @return void.
		 */
		private function maybe_do_action( $hook, $action ) {
			if ( ! is_callable( $action ) ) {
				return;
			}

			if ( ! did_action( $hook ) ) {
				add_action( $hook, $action );
			} else {
				call_user_func( $action );
			}
		}

		/**
		 * Enqueues the script.
		 *
		 * @param int $post_id Post ID.
		 */
		public function maybe_enqueue_scripts() {

			if ( method_exists( $this, 'enqueue_scripts' ) && $this->is_active() ) {
				$this->enqueue_scripts();
			}

		}

		/**
		 * Adds the shortcode.
		 */
		public function add_shortcode() {

			if ( shortcode_exists( $this->name ) ) {
				remove_shortcode( $this->name );
			}

			add_shortcode( $this->name, array( $this, 'shortcode' ) );

			// Check the Title
			add_filter( 'the_title', array( $this, 'has_shortcode' ), 9 );

			// Check the Content
			add_filter( 'the_content', array( $this, 'has_shortcode' ) );

			// Check the Excerpt
			add_filter( 'the_excerpt', array( $this, 'has_shortcode' ), 9 );

			// Check the widgets stuffs
			add_filter( 'widget_title', array( $this, 'has_shortcode' ), 9 );
			add_filter( 'widget_custom_html_content', array( $this, 'has_shortcode' ), 9 );
			add_filter( 'widget_text', array( $this, 'has_shortcode' ), 9 );
			add_filter( 'widget_text_content', array( $this, 'has_shortcode' ), 9 );

			// Check the nav
			add_filter( 'nav_menu_link_attributes', array( $this, 'nav_menu_link_attributes' ) );
			add_filter( 'nav_menu_item_title', array( $this, 'nav_menu_item_title' ), 99 );

		}

		/**
		 * Gets the hooks that a shortcode may be foudn.
		 *
		 * @return array
		 */
		protected function get_hooks_to_do_shortcode() {
			return array(
				'the_title',
				'the_excerpt',
				'widget_title',
				'widget_text',
				'widget_text_content',
				'widget_custom_html_content',
			);
		}

		/**
		 * Searches specified content contains the shortcode & sets is_active prop.
		 *
		 * @param string $content The content.
		 *
		 * @return string
		 */
		public function has_shortcode( $content ) {
			// Don't do anything if we already know it's active on the page.
			if ( true === $this->is_active ) {
				return $content;
			}

			if ( has_shortcode( $content, $this->name ) ) {
				$this->is_active = true;
				if ( in_array( current_filter(), $this->get_hooks_to_do_shortcode(), true ) ) {
					return do_shortcode( $content );
				}
			}

			return $content;
		}

		/**
		 * Gets shortcode attributes.
		 *
		 * @param array $atts Array of user shortcode attributes.
		 *
		 * @return array Array of parsed shortcode attributes.
		 */
		protected function get_atts( $atts ) {
			$this->atts = shortcode_atts( $this->get_defaults(), $atts );

			if ( method_exists( $this, 'sanitize_atts' ) ) {
				return $this->sanitize_atts( $this->atts );
			}

			return $this->atts;
		}

		/**
		 * Does the shortcode in a Nav Menu Item Title.
		 *
		 * @param string $title The menu item's title.
		 *
		 * @return string Parsed output of shortcode.
		 */
		public function nav_menu_item_title( $title ) {
			if ( has_shortcode( $title, $this->name ) ) {
				$this->is_active = true;
				$v               = do_shortcode( $title );

				return $v;
			}

			return $title;
		}

		/**
		 * Filters the HTML attributes applied to a menu item's anchor element.
		 *
		 * @param array $atts {
		 *     The HTML attributes applied to the menu item's `<a>` element, empty strings are ignored.
		 *
		 *     @type string $title  Title attribute.
		 *     @type string $target Target attribute.
		 *     @type string $rel    The rel attribute.
		 *     @type string $href   The href attribute.
		 * }
		 *
		 * @return mixed Array of HTML attributes.
		 */
		public function nav_menu_link_attributes( $atts ) {
			if ( isset( $atts['href'] ) ) {
				foreach ( $atts as $key => $att ) {
					$att = urldecode( $att );
					if ( has_shortcode( $att, $this->name ) ) {
						$this->is_active = true;
						$atts[ $key ]    = do_shortcode( $att );
					}
				}
			}

			return $atts;
		}

		/**
		 * Gets default attributes.
		 *
		 * @return array Default attributes
		 */
		protected function get_defaults() {
			return array();
		}

		/**
		 * Performs the shortcode.
		 *
		 * @param array  $atts    Array of user attributes.
		 * @param string $content Content of the shortcode.
		 *
		 * @return string Parsed output of the shortcode.
		 */
		abstract public function shortcode( $atts, $content = null );

		/**
		 * Whether the shortcode exists in the post content.
		 *
		 * @param null $post_id Post ID. Defaults to get_the_ID().
		 *
		 * @return bool Whether the content contains the shortcode.
		 */
		public function is_active( $post_id = null ) {
			if ( ! $post_id ) {
				$post_id = get_the_ID();
			}

			$post = get_post( $post_id );
			if ( is_a( $post, 'WP_Post') ) {
				return apply_filters( "wps_fundraising_shortcode_{$this->name}_is_active", ( has_shortcode( $post->post_content, $this->name ) ) );
			}

			return apply_filters( "wps_fundraising_shortcode_{$this->name}_is_active", false );
		}
	}
}
