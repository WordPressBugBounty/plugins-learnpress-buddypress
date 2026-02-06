<?php
/**
 * Plugin load class.
 *
 * @author   ThimPress
 * @package  LearnPress/BuddyPress/Classes
 * @version  3.0.0
 */

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Profile\ProfileQuizzesTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LP_Addon_BuddyPress' ) ) {
	/**
	 * Class LP_Addon_BuddyPress.
	 */
	class LP_Addon_BuddyPress extends LP_Addon {
		public $version         = LP_ADDON_BUDDYPRESS_VER;
		public $require_version = LP_ADDON_BUDDYPRESS_REQUIRE_VER;
		public $plugin_file     = LP_ADDON_BUDDYPRESS_FILE;
		public $text_domain     = 'learnpress-buddypress';

		/**
		 * LP_Addon_BuddyPress constructor.
		 */
		public function __construct() {
			parent::__construct();
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_assets' ) );
			$this->hooks();
		}

		public static function instance() {
			static $instance = null;
			if ( is_null( $instance ) ) {
				$instance = new self();
			}

			return $instance;
		}

		/**
		 * Includes.
		 */
		protected function _includes() {
			include_once $this->plugin_folder_path . '/inc/functions.php';
		}

		/**
		 * Init hooks.
		 */
		protected function hooks() {
			// Check bbPress v12.0+ doesn't trigger this; only runs on v11.x.x and earlier.
			if ( defined( 'BP_VERSION' ) && version_compare( BP_VERSION, '12.0', '<' ) ) {
				add_action( 'wp_loaded', array( $this, 'bp_add_new_item' ) );
			}
			// Check bbPress v12.0+ doesn't trigger this; only runs on v11.x.x and earlier.
			add_action( 'bp_setup_admin_bar', array( $this, 'bp_setup_courses_bar' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 0 );
		}

		/**
		 * Add new item.
		 */
		public function bp_add_new_item() {
			$tabs = apply_filters(
				'learn-press/buddypress/profile-tabs',
				array(
					/*array(
						'name'                    => __( 'Courses', 'learnpress-buddypress' ),
						'slug'                    => $this->get_tab_courses_slug(),
						'show_for_displayed_user' => true,
						'screen_function'         => array( $this, 'bp_tab_content' ),
						'default_subnav_slug'     => 'all',
						'position'                => 100,
					),*/
					array(
						'name'                    => __( 'Quizzes', 'learnpress-buddypress' ),
						'slug'                    => $this->get_tab_quizzes_slug(),
						'show_for_displayed_user' => true,
						'screen_function'         => array( $this, 'bp_tab_content' ),
						'default_subnav_slug'     => 'all',
						'position'                => 100,
					),
					array(
						'name'                    => __( 'Orders', 'learnpress-buddypress' ),
						'slug'                    => $this->get_tab_orders_slug(),
						'show_for_displayed_user' => true,
						'screen_function'         => array( $this, 'bp_tab_content' ),
						'default_subnav_slug'     => 'all',
						'position'                => 100,
					),
				)
			);
			// create new nav item
			foreach ( $tabs as $tab ) {
				bp_core_new_nav_item( $tab );
			}
		}

		/**
		 * Setup courses bar.
		 */
		public function bp_setup_courses_bar() {

			if ( ! get_current_user_id() ) {
				return;
			}
			// Define the WordPress global
			global $wp_admin_bar;

			global $bp;
			$lp_profile   = LP_Profile::instance();
			$courses_slug = $this->get_tab_courses_slug();
			$courses_name = __( 'Courses', 'learnpress-buddypress' );
			$items        = array(
				array(
					'parent' => $bp->my_account_menu_id,
					'id'     => 'my-account-' . $courses_slug,
					'title'  => $courses_name,
					'href'   => trailingslashit( $lp_profile->get_tab_link( 'courses' ) ),
				),
				array(
					'parent' => 'my-account-' . $courses_slug,
					'id'     => 'my-account-' . $courses_slug . '-all',
					'title'  => __( 'All courses', 'learnpress-buddypress' ),
					'href'   => trailingslashit( $lp_profile->get_tab_link( 'my-courses' ) ),
				),
			);
			// Add each admin menu
			foreach ( $items as $item ) {
				$wp_admin_bar->add_menu( $item );
			}
		}

		/**
		 * Get current link.
		 *
		 * @param string $tab
		 *
		 * @return bool|string
		 */
		/*public function bp_get_current_link( $tab = 'courses' ) {
			// Determine user to use
			if ( bp_displayed_user_domain() ) {
				$user_domain = bp_displayed_user_domain();
			} elseif ( bp_loggedin_user_domain() ) {
				$user_domain = bp_loggedin_user_domain();
			} else {
				return false;
			}

			$func = "get_tab_{$tab}_slug";
			if ( is_callable( array( $this, $func ) ) ) {
				$slug = call_user_func( array( $this, $func ) );
			} else {
				$slug = '';
			}

			// Link to user courses
			return trailingslashit( $user_domain . $slug );
		}*/

		/**
		 * Get link.
		 *
		 * @param $link
		 * @param $user_id
		 * @param $course_id
		 *
		 * @return string
		 */
		/*public function bp_get_link( $link, $user_id, $course_id ) {
			// Determine user to use
			if ( is_null( $user_id ) ) {
				$course  = get_post( $course_id );
				$user_id = $course->post_author;
			}
			$link = bp_core_get_user_domain( $user_id );

			return trailingslashit( $link . 'courses' );
		}*/

		/**
		 * Get profile tab courses slug.
		 *
		 * @return mixed
		 */
		public function get_tab_courses_slug() {
			$slugs = LearnPress::instance()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['courses'] ) ) {
				$slug = $slugs['courses'];
			}
			if ( ! $slug ) {
				$slug = 'courses';
			}

			return sanitize_title( apply_filters( 'learn_press_bp_tab_courses_slug', $slug ) );
		}

		/**
		 * Get profile tab quizzes slug.
		 *
		 * @return mixed
		 */
		public function get_tab_quizzes_slug() {
			$slugs = LearnPress::instance()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['quizzes'] ) ) {
				$slug = $slugs['quizzes'];
			}
			if ( ! $slug ) {
				$slug = 'quizzes';
			}

			return sanitize_title( apply_filters( 'learn_press_bp_tab_quizzes_slug', $slug ) );
		}

		/**
		 * Get profile tab quizzes slug.
		 *
		 * @return mixed
		 */
		public function get_tab_orders_slug() {
			$slugs = LearnPress::instance()->settings->get( 'profile_endpoints' );
			$slug  = '';
			if ( isset( $slugs['orders'] ) ) {
				$slug = $slugs['orders'];
			}
			if ( ! $slug ) {
				$slug = 'orders';
			}

			return sanitize_title( apply_filters( 'learn_press_bp_tab_orders_slug', $slug ) );
		}

		/**
		 * Get tab content.
		 */
		public function bp_tab_content() {
			global $bp;
			$type              = '';
			$current_component = $bp->current_component;
			$slugs             = LP_Settings::get_option( 'profile_endpoints', [] );
			$tab_slugs_default = array( 'profile-courses', 'profile-quizzes', 'profile-orders' );
			$tab_slugs         = array_keys( $slugs, $current_component );
			$tab_slugs         = wp_parse_args( $tab_slugs, $tab_slugs_default );
			$tab_slug          = array_shift( $tab_slugs );
			if ( in_array( $tab_slug, $tab_slugs_default ) ) {
				switch ( $current_component ) {
					case $this->get_tab_courses_slug():
						$type = 'courses';
						break;
					case $this->get_tab_quizzes_slug():
						$type = 'quizzes';
						break;
					case $this->get_tab_orders_slug():
						$type = 'orders';
						break;
					default:
						break;
				}
				if ( $type ) {
					add_action( 'bp_template_content', array( $this, "bp_tab_{$type}_content" ) );
					bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
				}
			}
			do_action( 'learn-press/buddypress/bp-tab-content', $current_component );
		}

		/**
		 * Tab courses content.
		 */
		public function bp_tab_courses_content() {
			//$args = array( 'user' => learn_press_get_current_user() );
			//learn_press_get_template( 'profile/courses.php', $args, learn_press_template_path() . '/addons/buddypress/', LP_ADDON_BUDDYPRESS_PATH . '/templates' );
		}

		/**
		 * Tab quizzes content.
		 */
		public function bp_tab_quizzes_content() {
			$html_wrapper = array(
				'<div class="learn-press-subtab-content">' => '</div>',
			);
			$callback     = array(
				'class'  => ProfileQuizzesTemplate::class,
				'method' => 'renderContent',
			);
			$args         = array(
				'id_url'  => 'profile_quizzes',
				'user_id' => get_current_user_id(),
				'paged'   => 1,
				'type'    => 'all',
			);

			$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
			$html    = Template::instance()->nest_elements( $html_wrapper, $content );
			echo $html;
		}

		/**
		 * Tab orders content.
		 */
		public function bp_tab_orders_content() {
			learn_press_get_template( 'profile/tabs/orders.php', array( 'user' => learn_press_get_current_user() ) );
		}

		/**
		 * Admin scripts.
		 *
		 * @param $hook
		 */
		public function admin_scripts( $hook ) {
			global $post;
			if ( $post && in_array(
				$post->post_type,
				array(
					LP_COURSE_CPT,
					LP_LESSON_CPT,
					LP_QUESTION_CPT,
					LP_QUIZ_CPT,
					LP_ORDER_CPT,
				)
			)
			) {
				add_filter( 'bp_activity_maybe_load_mentions_scripts', array( $this, 'dequeue_script' ), - 99 );
			}
		}

		/**
		 * Dequeue script.
		 *
		 * @param $load_mentions
		 *
		 * @return bool
		 */
		public function dequeue_script( $load_mentions ) {
			return false;
		}

		/**
		 * Frontend assets.
		 */
		public function wp_assets() {
			wp_enqueue_style( 'learn-press-buddypress', plugins_url( '/assets/css/site.css', LP_ADDON_BUDDYPRESS_FILE ) );
		}
	}
}
