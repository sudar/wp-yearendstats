<?php
/**
Plugin Name: Year End Stats
Plugin Script: wp-yearendstats.php
Plugin URI: http://sudarmuthu.com/wordpress/wp-year-end-stats
Description: Displays fancy stats about your blog which you can include in your year end review posts.
Version: 1.0
License: GPL
Author: Sudar
Author URI: http://sudarmuthu.com/
Text Domain: wp-yearendstats
Domain Path: languages/

=== RELEASE NOTES ===
Check readme file for full release notes
*/

/**  Copyright 2008  Sudar Muthu  (email : sudar@sudarmuthu.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Year End Stats Plugin
 */
class Year_End_Stats_Plugin {
    function __construct() {
        $this->includes();
        $this->setup_hooks();
    }

    /**
     * Include additional files.
     */
    public function includes() {
        require_once plugin_dir_path( __FILE__ ) . '/includes/stats.php';
        require_once plugin_dir_path( __FILE__ ) . '/includes/shortcode.php';
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks() {
        add_action( 'init', array( $this, 'do_localization' ) );

        add_action( 'admin_init', array( $this, 'on_admin_init' ) );
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_filter( 'plugin_action_links', array( $this, 'plugin_row_action' ), 10, 2 );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Do localization.
     */
    public function do_localization() {
        $translations = dirname( plugin_basename( __FILE__ ) ) . '/languages/' ;
        load_plugin_textdomain( 'wp-yearendstats', FALSE, $translations );
    }

    /**
     * Admin init callback.
     */
    public function on_admin_init() {
        $postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
        wp_register_script( 'flot', plugins_url( 'assets/js/vendor/flot/jquery.flot', __FILE__ ) . $postfix . '.js', array( 'jquery' ), '0.8.3', true );
    }

    /**
     * Add menu to the panel
     */
    public function add_menu() {
        $page_hook_suffix = add_submenu_page( 'index.php', __( 'Year End Stats' , 'wp-yearendstats'), __( 'Year End Stats' , 'wp-yearendstats'), 'manage_options', 'year-end-stats',  array( $this, 'display_dashboard_page' ) );
        add_action( 'admin_print_scripts-' . $page_hook_suffix, array( $this, 'enqueue_admin_script' ) );
    }

    /**
     * Display Stats page.
     */
    public function display_dashboard_page() {
        global $wpdb;

        $results = $wpdb->get_results("select distinct(date_format((post_date), '%Y')) as year from $wpdb->posts");
        $years = array();

        foreach ($results as $result) {
            $years[] = $result->year;
        }

        arsort($years);

	    if ( isset( $_POST['smyes_action'] ) && $_POST['smyes_action'] == 'getstats' ) {
            $year_1 = absint( $_POST['year_1'] );
            $year_2 = absint( $_POST['year_2'] );
            $range  = sanitize_text_field( $_POST['range'] );
        } else {
            $year_1 = 0;
            $year_2 = 0;
            $range  = 'to';
        }
?>
    <div class="wrap">
        <h2><?php _e( 'Year end stats' , 'wp-yearendstats'); ?></h2>

        <form name="smyes_form" method="post">

        <h3><?php _e( 'Select year range' , 'wp-yearendstats'); ?></h3>

        <select id = "year_1" name="year_1">
<?php
        foreach ( $years as $year ) {
            print ('<option value = "' . $year . '" ');
            if ($year_1 == $year) { print ('selected');}
            print ('>' . $year . '</option>');
        }
?>
        </select>

        <select id = "range" name="range">
            <option value="to"  <?php if ($range == "to")  print ('selected') ?>><?php _e(' To' , 'wp-yearendstats'); ?></option>
            <option value="and" <?php if ($range == "and") print ('selected') ?>><?php _e( 'And' , 'wp-yearendstats'); ?></option>
        </select>

        <select id = "year_2" name="year_2">
<?php
        foreach ($years as $year) {
            print ('<option value = "' . $year . '" ');
            if ($year_2 == $year) { print ('selected');}
            print ('>' . $year . '</option>');
        }
?>
        </select>
        <?php submit_button( esc_html__( 'Get Stats', 'wp-yearendstats' ) ); ?>
		<input type="hidden" name="smyes_action" value="getstats">
        </form>
<?php
	    if ( isset( $_POST['smyes_action'] ) && $_POST['smyes_action'] == 'getstats' ) {
?>
	        <table border="0" class="form-table">
	        <tbody>
		        <tr valign="top">
                    <td>
                        <div id = "posts_chart">
<?php
        $atts = array(
            'type'       => 'post_num',
            'post_type'  => 'post',
            'status'     => 'publish',
            'start_year' => $year_1,
            'end_year'   => $year_2,
            'range'      => $range,
            'title'      => esc_html__( 'Number of posts by year', 'wp-yearendstats' ),
            'width'      => 300,
            'height'     => 300,
        );
        $post_stats = new \SM\YearEndStats\Stats( $atts );
        echo $post_stats->generate_graph();
?>
                        </div>
                    </td>
                    <td>
                        <div id = "comments_chart">
<?php
        $atts = array(
            'type'       => 'comment_num',
            'post_type'  => 'post',
            'status'     => 'publish',
            'start_year' => $year_1,
            'end_year'   => $year_2,
            'range'      => $range,
            'title'      => esc_html__( 'Number of comments by year', 'wp-yearendstats' ),
            'width'      => 300,
            'height'     => 300,
        );
        $post_stats = new \SM\YearEndStats\Stats( $atts );
        echo $post_stats->generate_graph();
?>
                        </div>
                    </td>
			    </tr>
			    <tr>
                    <td>
                        <div id="avg_post_length_chart">
<?php
        $atts = array(
            'type'       => 'post_avg_length',
            'post_type'  => 'post',
            'status'     => 'publish',
            'start_year' => $year_1,
            'end_year'   => $year_2,
            'range'      => $range,
            'title'      => esc_html__( 'Average length of posts by year', 'wp-yearendstats' ),
            'width'      => 300,
            'height'     => 300,
        );
        $post_stats = new \SM\YearEndStats\Stats( $atts );
        echo $post_stats->generate_graph();
?>
                        </div>
                    </td>
                    <td>
                        <div id = "total_post_length_chart">
<?php
        $atts = array(
            'type'       => 'post_total_length',
            'post_type'  => 'post',
            'status'     => 'publish',
            'start_year' => $year_1,
            'end_year'   => $year_2,
            'range'      => $range,
            'title'      => esc_html__( 'Total length of posts by year', 'wp-yearendstats' ),
            'width'      => 300,
            'height'     => 300,
        );
        $post_stats = new \SM\YearEndStats\Stats( $atts );
        echo $post_stats->generate_graph();
?>
                        </div>
                    </td>
			    </tr>
			</tbody>
			</table>
<?php
	    }
	    print ('</div>');
    }

    /**
     * Adds the settings link in the Plugin page.
     *
     * Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/
     * @staticvar string $this_plugin
     * @param string $links
     * @param string $file
     */
    function plugin_row_action($links, $file) {
        static $this_plugin;
        if( ! $this_plugin ) $this_plugin = plugin_basename( __FILE__ );

        if( $file == $this_plugin ) {
            $settings_link = '<a href="index.php?page=year-end-stats">' . esc_html__( 'Stats', 'wp-yearendstats' ) . '</a>';
            array_unshift( $links, $settings_link ); // before other links
        }
        return $links;
    }

    /**
     * Enqueue script in admin page.
     */
    public function enqueue_admin_script() {
        //TODO: Include it only when the page is posted.
        wp_enqueue_script( 'flot' );
    }

    /**
     * Enqueue scripts.
     */
    public function enqueue_scripts() {
        //TODO: Include it only when a shortocode is used
        $postfix = ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) ? '' : '.min';
        wp_enqueue_script( 'flot', plugins_url( 'assets/js/vendor/flot/jquery.flot', __FILE__ ) . $postfix . '.js', array( 'jquery' ), '0.8.3' , true );
    }
}

// Bootstrap everything.
new Year_End_Stats_Plugin;
?>
