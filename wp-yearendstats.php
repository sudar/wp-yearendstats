<?php
/*
Plugin Name: Year End Stats
Plugin Script: wp-yearendstats.php
Plugin URI: http://sudarmuthu.com/wordpress/wp-year-end-stats
Description: Displays some fancy stats about your blog which you can include in your year end review posts. Based on the queries by <a href = 'http://alexking.org/blog/2007/01/01/sql-for-blog-stats'>Alex King</a> .
Version: 0.4
License: GPL
Author: Sudar
Author URI: http://sudarmuthu.com/ 

=== RELEASE NOTES ===
2008-01-01 - v0.1 - First Version
2008-06-08 - v0.2 - Second Version
2008-12-27 - v0.3 - Third Version
2009-01-27 - v0.4 - Fourth Version
*/

/**
 * Display Options page
 */
if (!function_exists('smyes_displayOptions')) {
    function smyes_displayOptions() {

        global $wpdb;

        $results = $wpdb->get_results("select distinct(date_format((post_date), '%Y')) as year from " . $wpdb->prefix . "posts");
        $years = array();
        
        foreach ($results as $result) {
            $years[] = $result->year;
        }
        
        arsort($years);

	    if ($_POST['smyes_action'] == "getstats") {
            $year_1 = $_POST['year_1'];
            $year_2 = $_POST['year_2'];
            $range = $_POST['range'];
	    }

		print('<div class="wrap">');
		print('<h2>Year end stats</h2>');
        
        print ('<form name="smyes_form" action="'. get_bloginfo("wpurl") . '/wp-admin/index.php?page=wp-yearendstats/wp-yearendstats.php' .'" method="post">');
?>
        <h3>Select year range</h3>

        <select id = "year_1" name="year_1">
<?php
            foreach ($years as $year) {
                print ('<option value = "' . $year . '" ');
                if ($year_1 == $year) { print ('selected');}
                print ('>' . $year . '</option>');
            }
?>
        </select>

        <select id = "range" name="range">
            <option value="to"  <?php if ($range == "to")  print ('selected') ?>>To</option>
            <option value="and" <?php if ($range == "and") print ('selected') ?>>And</option>
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
        <p style="border:0;" class = "submit">
            <input type="submit" value="Get Stats &raquo;">
        </p>
		<input type="hidden" name="smyes_action" value="getstats" />
        </form>
<?php
	    if ($_POST['smyes_action'] == "getstats") {
?>
	        <table border="0" class="form-table">
	        <tbody>
		        <tr valign="top">
                    <td>
                        <div id = "posts_chart"></div>
                        <p style="border:0;" class = "submit"><input type="button" value="Save as Image" onclick="save_image('posts_chart');"></p>
                    </td>
                    <td>
                        <div id = "comments_chart"></div>
                        <p style="border:0;" class = "submit"><input type="button" value="Save as Image" onclick="save_image('comments_chart');"></p>
                    </td>
			    </tr>
			    <tr>	
                    <td>
                        <div id = "avg_post_length_chart"></div>
                        <p style="border:0;" class = "submit"><input type="button" value="Save as Image" onclick="save_image('avg_post_length_chart');"></p>
                    </td>
                    <td>
                        <div id = "total_post_length_chart"></div>
                        <p style="border:0;" class = "submit"><input type="button" value="Save as Image" onclick="save_image('total_post_length_chart');"></p>
                    </td>
			    </tr>
			</tbody>
			</table>
<?php        
	    }	    
	    print ('</div>');

    }
}

/**
 * Enqueue Scripts
 */
function smyes_enqueue_scripts() {
    if ($_POST['smyes_action'] == "getstats") {
       wp_enqueue_script('json');
       wp_enqueue_script('swfobject');
    }
}

/**
 * Print scripts
 */
if (!function_exists('smyes_print_scripts')) {
    function smyes_print_scripts() {

	    if ($_POST['smyes_action'] == "getstats") {
            $year_1 = $_POST['year_1'];
            $year_2 = $_POST['year_2'];
            $range = $_POST['range'];

            // Opne Flash Chart class is available through open flash chart core Plugin
            $chart_1 = new open_flash_chart();
            $chart_2 = new open_flash_chart();
            $chart_3 = new open_flash_chart();
            $chart_4 = new open_flash_chart();

            $year_range = array();

            switch ($range) {
                case "and":
                    $year_range [] = (string)$year_1;
                    if ($year_1 != $year_2) {
                        $year_range [] = (string)$year_2;
                    }
                    break;

                case "to":
                    if ($year_1 > $year_2){
                        $tmp = $year_2;
                        $year_2 = $year_1;
                        $year_1 = $tmp;
                    }
                    for ($i = $year_1; $i <= $year_2; $i++ ) {
                        $year_range [] = (string)$i;
                    }
                    break;

                default:
                    break;
            }
            $posts_values = array();
            $comments_values = array();
            $avg_post_length  = array();
            $total_post_length = array();

            foreach ($year_range as $year) {

                $posts_values [] = (int)smyes_get_num_posts($year);
                $comments_values [] = (int)smyes_get_num_comments($year);

                $avg_post_length[] = (float)smyes_get_post_avg_length($year);
                $total_post_length[] = (int)smyes_get_post_total_length($year);
            }

            // titles
            $title_1 = new title("Total number of posts per year");
            $title_2 = new title("Total number of comments per year");
            $title_3 = new title("Average length of posts per year");
            $title_4 = new title("Total length of all posts per year");

            $chart_1->set_title( $title_1 );
            $chart_2->set_title( $title_2 );
            $chart_3->set_title( $title_3 );
            $chart_4->set_title( $title_4 );

            // Bars
            $posts_bar = new bar();
            $posts_bar->set_values( $posts_values);
            $posts_bar->set_tooltip( "#val# posts in #x_label#" );
            $chart_1->add_element( $posts_bar );

            $comments_bar = new bar();
            $comments_bar->set_values( $comments_values);
            $comments_bar->set_tooltip( "#val# comments in #x_label#" );
            $chart_2->add_element( $comments_bar );

            $avg_post_length_bar = new bar();
            $avg_post_length_bar->set_values( $avg_post_length);
            $avg_post_length_bar->set_tooltip( "#val# characters in #x_label#" );
            $chart_3->add_element( $avg_post_length_bar );

            $total_post_length_bar = new bar();
            $total_post_length_bar->set_values( $total_post_length);
            $total_post_length_bar->set_tooltip( "#val# characters in #x_label#" );
            $chart_4->add_element( $total_post_length_bar );

            // X Axis
            $x_labels = new x_axis_labels();
            $x_labels->set_vertical();
            $x_labels->set_labels($year_range);

            $x_axis = new x_axis();
            $x_axis->set_labels($x_labels);

            $chart_1->set_x_axis($x_axis);
            $chart_2->set_x_axis($x_axis);
            $chart_3->set_x_axis($x_axis);
            $chart_4->set_x_axis($x_axis);

            // Y Axis
            $y_axis_1 = new y_axis();
            $y_axis_2 = new y_axis();
            $y_axis_3 = new y_axis();
            $y_axis_4 = new y_axis();

            $min = min($posts_values);
            $max = max($posts_values);
            $steps = round($max / 10, 0);
            $y_axis_1->set_range(0, $max, $steps);

            $min = min($comments_values);
            $max = max($comments_values);
            $steps = round($max / 10, 0);
            $y_axis_2->set_range(0, $max, $steps);

            $min = round(min($avg_post_length), 0);
            $max = round(max($avg_post_length), 0);
            $steps = round($max / 10, 0);
            $y_axis_3->set_range(0, $max, $steps);

            $min = min($total_post_length);
            $max = max($total_post_length);
            $steps = round($max / 10, 0);
            $y_axis_4->set_range(0, $max, $steps);

            $chart_1->set_y_axis($y_axis_1);
            $chart_2->set_y_axis($y_axis_2);
            $chart_3->set_y_axis($y_axis_3);
            $chart_4->set_y_axis($y_axis_4);

            $include_url = get_option("SM_OFC_INC_URL");
?>
<script type="text/javascript">
swfobject.embedSWF("<?php echo $include_url; ?>open-flash-chart.swf", "posts_chart", "350", "250", "9.0.0", "expressInstall.swf", {"get-data":"open_flash_chart_data_2", "id": "posts_chart", "loading":"Loading data..."}, {"menu":"false"});
swfobject.embedSWF("<?php echo $include_url; ?>open-flash-chart.swf", "comments_chart", "350", "250", "9.0.0","expressInstall.swf", {"get-data":"open_flash_chart_data_2", "id": "comments_chart", "loading":"Loading data..."}, {"menu":"false"});
swfobject.embedSWF("<?php echo $include_url; ?>open-flash-chart.swf", "avg_post_length_chart", "350", "250", "9.0.0","expressInstall.swf", {"get-data":"open_flash_chart_data_2", "id": "avg_post_length_chart", "loading":"Loading data..."}, {"menu":"false"});
swfobject.embedSWF("<?php echo $include_url; ?>open-flash-chart.swf", "total_post_length_chart", "350", "250", "9.0.0","expressInstall.swf", {"get-data":"open_flash_chart_data_2", "id": "total_post_length_chart", "loading":"Loading data..."}, {"menu":"false"});
</script>

<script type="text/javascript">

/**
 * Provides data for charts
 */
function open_flash_chart_data_2(id)
{
    switch (id) {
        case "posts_chart":
            var data = <?php echo $chart_1->toPrettyString(); ?>;
            return JSON.stringify(data);
            break;
        case "comments_chart":
            var data = <?php echo $chart_2->toPrettyString(); ?>;
            return JSON.stringify(data);
            break;
        case "avg_post_length_chart":
            var data = <?php echo $chart_3->toPrettyString(); ?>;
            return JSON.stringify(data);
            break;
        case "total_post_length_chart":
            var data = <?php echo $chart_4->toPrettyString(); ?>;
            return JSON.stringify(data);
            break;
        default:
            break;
    }
}

/**
 * Utility Function
 */
function findSWF(movieName) {
  if (navigator.appName.indexOf("Microsoft")!= -1) {
    return window[movieName];
  } else {
    return document[movieName];
  }
}

/**
 * Save chart as Image
 */
function save_image(src) {
    // TODO, save image directly, instead of opening in a new window
    var img_win = window.open('', 'Charts: Export as Image');
    img_win.document.write("<html><head><title>Charts: Export as Image<\/title><\/head><body>" + "<img src='data:image/png;base64," + document.getElementById(src).get_img_binary() + "' />" + "<\/body><\/html>");
}
</script>

<?php
        }
    }
}

/**
 * Get the Number of posts in a year.
 *
 * @param Number $year
 * @return Number
 */
function smyes_get_num_posts($year) {
   global $wpdb;
   $prefix = $wpdb->prefix;
   $year = $wpdb->escape($year);
   $nextyear = $year + 1;
   
    $results = $wpdb->get_results("
	    SELECT COUNT(*) as count
		FROM " . $prefix . "posts
		WHERE post_date >= '$year-01-01'
		AND post_date < '$nextyear-01-01'
		AND post_status = 'publish'");

    	return ($results[0]->count);    	
}

/**
 * Get the number of comments in a year.
 *
 * @param Number $year
 * @return Number
 */
function smyes_get_num_comments($year) {
   global $wpdb;
   $prefix = $wpdb->prefix;
   $year = $wpdb->escape($year);
   $nextyear = $year + 1;
   
    $results = $wpdb->get_results("
		SELECT COUNT(*) as count
		FROM " . $prefix . "comments
		WHERE comment_date >= '$year-01-01'
		AND comment_date < '$nextyear-01-01'
		AND comment_approved = '1'");
    
    	return ($results[0]->count);    	
}

/**
 * Get the average length of posts in a year
 *
 * @param Number $year
 * @return Number
 */
function smyes_get_post_avg_length($year) {
   global $wpdb;
   $prefix = $wpdb->prefix;
   $year = $wpdb->escape($year);
   $nextyear = $year + 1;
   
    $results = $wpdb->get_results("
		SELECT AVG(LENGTH(post_content)) as avg
		FROM " . $prefix . "posts
		WHERE post_date >= '$year-01-01'
		AND post_date < '$nextyear-01-01'
		AND post_status = 'publish'    ");
    
    	return ($results[0]->avg);
}

/**
 * Get the total length of posts in a year
 *
 * @param unknown_type $year
 * @return unknown
 */
function smyes_get_post_total_length($year) {
   global $wpdb;
   $prefix = $wpdb->prefix;
   $year = $wpdb->escape($year);
   $nextyear = $year + 1;
   
    $results = $wpdb->get_results("
	    SELECT SUM(LENGTH(post_content)) as sum
		FROM " . $prefix . "posts
		WHERE post_date >= '$year-01-01'
		AND post_date < '$nextyear-01-01'
		AND post_status = 'publish'");
    
    	return ($results[0]->sum);
}

/**
 * Add menu to the panel
 */
if(!function_exists('smyes_add_menu')) {
	function smyes_add_menu() {
	    // Add a submenu to the Dashboard:
	    add_submenu_page('index.php', 'Year End Stats', 'Year End Stats', 1, __FILE__, 'smyes_displayOptions');
	}
}

/**
 * After all plugins are loaded
 */
if (!function_exists('smyes_plugins_loaded')) {
    function smyes_plugins_loaded() {
        // hook the admin notices action
        add_action( 'admin_notices', 'smyes_check_dependency' );
    }
}

/**
 * Check Plugin dependency
 */
if (!function_exists("smyes_check_dependency")) {
    function smyes_check_dependency() {
        // if Open Flash Charts API Core plugin is not installed then de-activate
        if (!class_exists('open_flash_chart')) {
            echo "<div class = 'updated'><p>ERROR! <strong>WP Year End Stats</strong> Plugin requires <a href = 'http://sudarmuthu.com/wordpress/'>Open Flash Chart Core Plugin</a>. Please install it and then activate <strong>WP Year End Stats</strong> Plugin.</p></div>";
            deactivate_plugins('wp-yearendstats/wp-yearendstats.php'); // Deactivate ourself

            // add deactivated Plugin to the recently activated list
            $deactivated = array();
            $deactivated["wp-yearendstats/wp-yearendstats.php"] = time();
            update_option('recently_activated', $deactivated + (array)get_option('recently_activated'));
        }
    }
}

/**
 * Adds the settings link in the Plugin page. Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/
 * @staticvar <type> $this_plugin
 * @param <type> $links
 * @param <type> $file
 */
function smyes_filter_plugin_actions($links, $file) {
    static $this_plugin;
    if( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

    if( $file == $this_plugin ) {
        $settings_link = '<a href="index.php?page=wp-yearendstats/wp-yearendstats.php">' . _('Manage') . '</a>';
        array_unshift( $links, $settings_link ); // before other links
    }
    return $links;
}
add_filter( 'plugin_action_links', 'smyes_filter_plugin_actions', 10, 2 );

add_action('admin_menu', 'smyes_add_menu');
add_action('admin_head', 'smyes_print_scripts', 1);
add_action('init', 'smyes_enqueue_scripts');

// Start this plugin once all other files and plugins are fully loaded
add_action( 'plugins_loaded', 'smyes_plugins_loaded');
?>