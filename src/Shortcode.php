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
 * @package    WPS\Shortcodes
 * @author     Travis Smith <t@wpsmith.net>
 * @copyright  2015-2018 Travis Smith
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link       https://github.com/wpsmith/WPS
 * @version    1.0.0
 * @since      0.1.0
 */

namespace WPS\Shortcodes;

use WPS\Core;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'WPS\Shortcodes\Shortcode' ) ) {
	/**
	 * Shortcode Abstract Class
	 *
	 * Assists in creating Shortcodes.
	 *
	 * @package WPS\Core
	 * @author  Travis Smith <t@wpsmith.net>
	 */
	abstract class Shortcode extends Core\Singleton {

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
			add_action( 'plugins_loaded', array( $this, 'add_shortcode' ) );
		}

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
			return shortcode_atts( $this->get_defaults(), $atts );
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

			return ( has_shortcode( $post->post_content, $this->name ) );
		}
	}
}
