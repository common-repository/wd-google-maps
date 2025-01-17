<?php

class GMWDAdmin
{
    ////////////////////////////////////////////////////////////////////////////////////////
    // Events                                                                             //
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    // Constants                                                                          //
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    // Variables                                                                          //
    ////////////////////////////////////////////////////////////////////////////////////////
    protected static $instance = null;
    private static $version = '1.0.74';
    ////////////////////////////////////////////////////////////////////////////////////////
    // Constructor & Destructor                                                           //
    ////////////////////////////////////////////////////////////////////////////////////////
    public function __construct()
    {
        if( !get_site_option("gmwd_created_tables") ){
            self::activate();
        }
        // Includes
        add_action('init', array($this, 'gmwd_includes'));

        // Add menu
        add_action('admin_menu', array($this, 'gmwd_options_panel'), 9);

        //Screen options
        add_filter('set-screen-option', array($this, 'gmwd_set_option_maps'), 10, 3);
        add_filter('set-screen-option', array($this, 'gmwd_set_option_markercategories'), 10, 3);
        add_filter('set-screen-option', array($this, 'gmwd_set_option_themes'), 10, 3);

        // Add admin styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'gmwd_styles'));
        add_action('admin_enqueue_scripts', array($this, 'gmwd_scripts'));

        // Add shortcode
        add_action('admin_head', array($this, 'gmwd_admin_ajax'));
        add_action('wp_ajax_gmwd_shortcode', array('GMWDAdmin', 'gmwd_ajax'));

        // Enqueue block editor assets for Gutenberg.
        add_filter('gmwd_tw_get_block_editor_assets', array($this, 'register_block_editor_assets'));
        add_action( 'enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets') );

        add_filter('mce_buttons', array($this, 'gmwd_add_button'), 0);
        add_filter('mce_external_plugins', array($this, 'gmwd_register'));

        // Ajax
        add_action('wp_ajax_select_parent_category', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_remove_poi', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_publish_poi', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_save_markers', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_export', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_import', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wp_ajax_view_maps_pois', array('GMWDAdmin', 'gmwd_ajax'));


        add_action('wp_ajax_map_data', array('GMWDAdmin', 'gmwd_ajax'));
        add_action('wpmu_new_blog', array("GMWDAdmin", 'new_blog_added'), 10, 6);

        // add meta fields
        add_filter("plugin_row_meta", array($this, 'meta_links'), 10, 2);


    }
    ////////////////////////////////////////////////////////////////////////////////////////
    // Public Methods                                                                     //
    ////////////////////////////////////////////////////////////////////////////////////////

  public function meta_links($meta_fields, $file)
    {
        if (GMWD_MAIN_FILE == $file) {
            $plugin_url = "https://wordpress.org/support/plugin/wd-google-maps";
            $prefix = 'gmwd';
            $meta_fields[] = "<a href='" . $plugin_url . "/#new-post' target='_blank'>" . __('Ask a question', $prefix) . "</a>";
            $meta_fields[] = "<a href='" . $plugin_url . "/reviews#new-post' target='_blank' title='" . __('Rate', $prefix) . "'>
            <i class='wdi-rate-stars'>"
                . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
                . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
                . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
                . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
                . "<svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg>"
                . "</i></a>";

            $stars_color = "#ffb900";

            echo "<style>"
                . ".wdi-rate-stars{display:inline-block;color:" . $stars_color . ";position:relative;top:3px;}"
                . ".wdi-rate-stars svg{fill:" . $stars_color . ";}"
                . ".wdi-rate-stars svg:hover{fill:" . $stars_color . "}"
                . ".wdi-rate-stars svg:hover ~ svg{fill:none;}"
                . "</style>";
        }

        return $meta_fields;
    }

    public static function activate()
    {

        require_once(GMWD_DIR . '/sql/sql.php');
        gmwd_create_tables();
        $version = get_option("gmwd_version");
        if (get_option("gmwd_pro")) {
            update_option("gmwd_pro", "no");
        } else {
            add_option("gmwd_pro", "no", '', 'no');
        }

        if ($version && version_compare(substr($version, 2), substr(self::$version, 2), '<=')) {
            update_option("gmwd_version", self::$version);
        } else {
            add_option("gmwd_version", self::$version, '', 'no');
        }

        //add_option('gmwd_do_activation_set_up_redirect', 1);
        add_option('gmwd_download_markers', 0);

        require_once GMWD_DIR . "/update/gmwd_update.php";
        gmwd_update();

        add_site_option("gmwd_created_tables", 1);

    }

    public static function new_blog_added($blog_id)
    {
        if (is_plugin_active_for_network('wd-google-maps/wd-google-maps.php')) {
            switch_to_blog($blog_id);
            self::activate();
            restore_current_blog();
        }
    }


    public static function gmwd_activate($networkwide)
    {
        if (function_exists('is_multisite') && is_multisite()) {
            // Check if it is a network activation - if so, run the activation function for each blog id.
            //if ($networkwide) {
                global $wpdb;
                // Get all blog ids.
                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);
                    self::activate();
                    restore_current_blog();
                }

                return;
            //}
        } else {
            self::activate();
        }
    }


    // Return an instance of this class.

    public static function gmwd_get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    ////////////////////////////////////////////////////////////////////////////////////////
    // Getters & Setters                                                                  //
    ////////////////////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////////////////////
    // Private Methods                                                                    //
    ////////////////////////////////////////////////////////////////////////////////////////

    // Admin menu
    public function gmwd_options_panel()
    {
        $parent_slug = null;
        if (1 || get_option("gmwd_subscribe_done") == 1) {
            $parent_slug = "maps_gmwd";
            $gmwd_page = add_menu_page("Google Maps", "Google Maps", 'manage_options', 'maps_gmwd', array($this, 'gmwd'), GMWD_URL . '/images/icon-map-20.png', 9);
            add_action('load-' . $gmwd_page, array($this, 'gmwd_maps_per_page_option'));

        }

        $gmwd_page = add_submenu_page($parent_slug, __('Maps', 'gmwd'), __('Maps', 'gmwd'), 'manage_options', 'maps_gmwd', array($this, 'gmwd'));
        add_action('load-' . $gmwd_page, array($this, 'gmwd_maps_per_page_option'));

        $gmwd_marker_categories_page = add_submenu_page($parent_slug, __('Marker Categories', 'gmwd'), __('Marker Categories', 'gmwd'), 'manage_options', 'markercategories_gmwd', array($this, 'gmwd'));
        add_action('load-' . $gmwd_marker_categories_page, array($this, 'gmwd_markercategories_per_page_option'));

        $gmwd_themes_page = add_submenu_page($parent_slug, __('Themes', 'gmwd'), __('Themes', 'gmwd'), 'manage_options', 'themes_gmwd', array($this, 'gmwd'));
        add_action('load-' . $gmwd_themes_page, array($this, 'gmwd_themes_per_page_option'));

        $gmwd_options_page = add_submenu_page($parent_slug, __('Options', 'gmwd'), __('Options', 'gmwd'), 'manage_options', 'options_gmwd', array($this, 'gmwd'));

        $gmwd_uninstall_page = add_submenu_page($parent_slug, __('Uninstall', 'gmwd'), __('Uninstall', 'gmwd'), 'manage_options', 'uninstall_gmwd', array($this, 'gmwd'));

        add_submenu_page('maps_gmwd', __('Google Maps Add-ons', 'gmwd'),  __('Google Maps Add-ons', 'gmwd'), 'manage_options', 'gmwd_addons', array($this, "gmwd_addons"));

      /* Custom link to wordpress.org*/
      global $submenu;
      $url = 'https://wordpress.org/support/plugin/wd-google-maps/#new-post';
      $submenu[$parent_slug][] = array(
        '<div id="gmwd_ask_question">' . __('Ask a question', 'gmwd') . '</div>',
        'manage_options',
        $url
      );

    }

    // Admin main function
    public function gmwd()
    {

        $page = GMWDHelper::get('page') ? GMWDHelper::get('page') : "maps_gmwd";
        $controller_class = 'GMWDController' . ucfirst(strtolower($page));
        $controller = new $controller_class();
        $controller->execute();
    }

    public static function gmwd_ajax()
    {
        check_ajax_referer('nonce_gmwd', 'nonce_gmwd');
        $instance = self::gmwd_get_instance();
        $page = GMWDHelper::get('page') ? GMWDHelper::get('page') : "maps_gmwd";

        $controller_class = 'GMWDController' . ucfirst(strtolower($page));
        $controller = new $controller_class();
        $controller->execute();
    }

    // Admin includes
    public function gmwd_includes()
    {
        require_once(GMWD_DIR . '/framework/GMWDHelper.php');
        require_once(GMWD_DIR . '/framework/GMWDMap.php');
        require_once(GMWD_DIR . '/admin/controllers/GMWDController.php');
        require_once(GMWD_DIR . '/admin/models/GMWDModel.php');
        require_once(GMWD_DIR . '/admin/views/GMWDView.php');
        $page = GMWDHelper::get('page') ? GMWDHelper::get('page') : "maps_gmwd";

        if ($page == "maps_gmwd" || $page == "markercategories_gmwd" || $page == "themes_gmwd" || $page == "options_gmwd" || $page == "markers_gmwd" || $page == "circles_gmwd" || $page == "rectangles_gmwd" || $page == "polygons_gmwd" || $page == "polylines_gmwd" || $page == "uninstall_gmwd" || $page == "shortcode_gmwd") {

            require_once(GMWD_DIR . '/admin/controllers/GMWDController' . ucfirst(strtolower($page)) . '.php');
        }
        if ($page == 'gmwd_preview') {
            require_once('preview.php');
        }

    }

    // Admin styles
    public function gmwd_styles()
    {
        $page = GMWDHelper::get('page') ? GMWDHelper::get('page') : "maps_gmwd";
        wp_admin_css('thickbox');
        if ($page == "maps_gmwd" || $page == "markercategories_gmwd" || $page == "themes_gmwd" || $page == "options_gmwd" || $page == "markers_gmwd" || $page == "circles_gmwd" || $page == "rectangles_gmwd" || $page == "polygons_gmwd" || $page == "polylines_gmwd" || $page == "uninstall_gmwd" || $page == "shortcode_gmwd") {
            wp_enqueue_style('gmwd_admin_main-css', GMWD_URL . '/css/admin_main.css', array(), self::$version);
            wp_enqueue_style('gmwd_simple_slider-css', GMWD_URL . '/css/simple-slider.css', array(), self::$version);
        }
        if ($page == "uninstall_gmwd") {
            wp_enqueue_style('gmwd_deactivate-css', GMWD_URL . '/wd/assets/css/deactivate_popup.css', array(), self::$version);
        }


    }

    // Admin scripts
    public function gmwd_scripts()
    {

      wp_enqueue_script('thickbox');
        wp_enqueue_script('gmwd_admin_main-js', GMWD_URL . '/js/admin_main.js');
        global $wpdb, $wp_scripts;

        $map_api_url = "https://maps.googleapis.com/maps/api/js?libraries=places,geometry&sensor=false&v=3.exp";

        if (gmwd_get_option("map_language")) {
            $map_api_url .= "&language=" . gmwd_get_option("map_language");
        }
        if (gmwd_get_option("map_api_key")) {
            $map_api_url .= "&key=" . gmwd_get_option("map_api_key");
        } else {
            $api_keys = array("AIzaSyAmYQInD-coq0G5wC_D9h7uHjGeHhSSR4o", "AIzaSyBxiaSJPIRfQWID9j4hCrX3t7z-9IOOjis", "	AIzaSyDi6aVWxOVptj9WZZYeAgdAA1xpqAR1mnw", "AIzaSyCzvhE5_lt5l0fYYChF1TpRtfFTjXpYkVI", "AIzaSyBMWPhZdxcpwpfXBrGPGmz8zMjwJJt83mc");
            $map_api_url .= "&key=" . $api_keys[rand(0, 4)];
        }
        wp_enqueue_script('gmwd_map-js', $map_api_url);
        wp_enqueue_script('gmwd_admin_main_map-js', GMWD_URL . '/js/main_map.js');

        global $wp_scripts;
        if (isset($wp_scripts->registered['jquery'])) {
            $jquery = $wp_scripts->registered['jquery'];
            if (!isset($jquery->ver) OR version_compare($jquery->ver, '1.8.2', '<')) {
                wp_deregister_script('jquery');
                wp_register_script('jquery', false, array('jquery-core', 'jquery-migrate'), '1.10.2');
            }
        }
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tooltip');
        wp_enqueue_media();

        $page = GMWDHelper::get('page') ? GMWDHelper::get('page') : "maps_gmwd";
        if ($page == "markers_gmwd" || ($page == "maps_gmwd" && GMWDHelper::get('task') == "edit") || $page == "polygons_gmwd" || $page == "polylines_gmwd" || $page == "options_gmwd") {
            wp_enqueue_script('gmwd_jscolor-js', GMWD_URL . '/js/jscolor/jscolor.js', array(), true);
            wp_enqueue_script('gmwd_simple_slider-js', GMWD_URL . '/js/simple-slider.js', array(), true);
            wp_enqueue_script($page . '-js', GMWD_URL . '/js/' . $page . '.js', array(), self::$version, false);
        }

        if ($page == "maps_gmwd" && GMWDHelper::get('task') == "edit") {
            wp_enqueue_script('gmwd_init_map_admin-js', GMWD_URL . '/js/init_map_admin.js', array(), self::$version, false);

        }
        if ($page == "uninstall_gmwd") {
            wp_enqueue_script('gmwd-deactivate-popup', GMWD_URL . '/wd/assets/js/deactivate_popup.js', array(), self::$version, true);
            $admin_data = wp_get_current_user();

            wp_localize_script('gmwd-deactivate-popup', 'gmwdWDDeactivateVars', array(
                "prefix"           => "gmwd",
                "deactivate_class" => 'gmwd_deactivate_link',
                "email"            => $admin_data->data->user_email,
                "plugin_wd_url"    => "https://web-dorado.com/products/wordpress-google-maps-plugin.html",
            ));
        }


    }

    // Add pagination to map admin pages.
    public function gmwd_maps_per_page_option()
    {
        $option = 'per_page';
        $args_maps = array(
            'label'   => __('Maps', "gmwd"),
            'default' => 20,
            'option'  => 'gmwd_maps_per_page'
        );
        add_screen_option($option, $args_maps);
    }

    public function gmwd_markercategories_per_page_option()
    {
        $option = 'per_page';
        $args_markercategories = array(
            'label'   => __('Marker Categories', "gmwd"),
            'default' => 20,
            'option'  => 'gmwd_markercategories_per_page'
        );
        add_screen_option($option, $args_markercategories);
    }

    public function gmwd_themes_per_page_option()
    {
        $option = 'per_page';
        $args_themes = array(
            'label'   => __('Themes', "gmwd"),
            'default' => 20,
            'option'  => 'gmwd_themes_per_page'
        );
        add_screen_option($option, $args_themes);
    }


    public function gmwd_set_option_maps($status, $option, $value)
    {
        if ('gmwd_maps_per_page' == $option)
            return $value;

        return $status;
    }

    public function gmwd_set_option_markercategories($status, $option, $value)
    {
        if ('gmwd_markercategories_per_page' == $option)
            return $value;

        return $status;
    }

    public function gmwd_set_option_themes($status, $option, $value)
    {
        if ('gmwd_themes_per_page' == $option)
            return $value;

        return $status;
    }


    public function gmwd_admin_ajax()
    {
        ?>
        <script>
            var gmwd_admin_ajax = '<?php echo add_query_arg(array('action' => 'gmwd_shortcode', 'page' => 'shortcode_gmwd', 'nonce_gmwd' => wp_create_nonce('nonce_gmwd')), admin_url('admin-ajax.php')); ?>';
            var gmwd_plugin_url = '<?php echo GMWD_URL;?>';
        </script>
        <?php
    }

    // Add media button
    public function gmwd_add_button($buttons)
    {
        array_push($buttons, "gmwd_mce");

        return $buttons;
    }

    // Register button
    public function gmwd_register($plugin_array)
    {
        $url = GMWD_URL . '/js/gmwd_editor_button.js';
        $plugin_array["gmwd_mce"] = $url;

        return $plugin_array;
    }


    public function gmwd_addons()
    {
        require_once(GMWD_DIR . '/addons/addons.php');
        wp_register_style('gmwd_addons', GMWD_URL . '/addons/style.css', array(), array($this, "gmwd_version"));
        wp_print_styles('gmwd_addons');
        gmwd_addons_display();

    }

    public function register_block_editor_assets($assets) {
        $version = '2.0.0';
        $js_path = GMWD_URL . '/js/tw-gb/block.js';
        $css_path = GMWD_URL . '/css/tw-gb/block.css';
        if (!isset($assets['version']) || version_compare($assets['version'], $version) === -1) {
            $assets['version'] = $version;
            $assets['js_path'] = $js_path;
            $assets['css_path'] = $css_path;
        }
        return $assets;
    }

    /**
     * Enqueue block editor assets.
     */
    public function enqueue_block_editor_assets() {
        $product_key = 'tw/gmwd';
        $plugin_name = 'WD Google Maps';
        $data = GMWDHelper::get_shortcode_data();
        $icon_url = GMWD_URL . '/images/icon-map-50.png';
        $icon_svg = GMWD_URL . '/images/icon-map-50.png';

        // Remove previously registered or enqueued versions
        $wp_scripts = wp_scripts();
        foreach ($wp_scripts->registered as $key => $value) {
            // Check for an older versions with prefix.
            if (strpos($key, 'gmwd-tw-gb-block') > 0) {
                wp_deregister_script( $key );
                wp_deregister_style( $key );
            }
        }
        // Get the last version from all 10Web plugins.
        $assets = apply_filters('gmwd_tw_get_block_editor_assets', array());
        // Not performing unregister or unenqueue as in old versions all are with prefixes.
        wp_enqueue_script('gmwd-tw-gb-block', $assets['js_path'], array( 'wp-blocks', 'wp-element' ), $assets['version']);
        wp_localize_script('gmwd-tw-gb-block', 'tw_obj', array(
            'nothing_selected' => __('Nothing selected.','gmwd'),
            'empty_item' => __('- Select -', 'gmwd'),
            'key'              => $product_key,
            'title'            => $plugin_name,
            'titleSelect'      => sprintf(__('Select %s', 'gmwd'), $plugin_name),
            'iconUrl'          => $icon_url,
            'iconSvg'          => $icon_svg,
            'data'             => $data,
        ));
        wp_enqueue_style('tw-gb-block', $assets['css_path'], array( 'wp-edit-blocks' ), $assets['version']);
    }
    ////////////////////////////////////////////////////////////////////////////////////////
    // Listeners                                                                          //
    ////////////////////////////////////////////////////////////////////////////////////////

}


?>
