<?php
/*
Plugin Name: Year End Stats
Plugin Script: wp-yearendstats.php
Plugin URI: http://sudarmuthu.com/wordpress/wp-year-end-stats
Description: Displays some fancy stats about your blog which you can include in your year end review posts. Based on the queries by <a href = 'http://alexking.org/blog/2007/01/01/sql-for-blog-stats'>Alex King</a> .
Version: 0.2
License: GPL
Author: Sudar
Author URI: http://sudarmuthu.com/ 

=== RELEASE NOTES ===
2008-01-01 - v0.1 - First Version
2008-06-08 - v0.2 - Second Version
*/

if (!function_exists('smyes_displayOptions')) {
    function smyes_displayOptions() {

        global $wpdb;

        $year = 2007;
        
	    if ($_POST['smyes_action'] == "getstats") {
	        $year = $wpdb->escape($_POST['smyes_year']);
	        if (!is_numeric($year)) {
	        	echo '<br clear="all" /> <div id="message" class="updated fade"><p><strong>Please enter a valid year</strong></p></div>';
	        	$year = 0;
	        }
	    }

		print('<div class="wrap">');
		print('<h2>Year end stats</h2>');
        
        print ('<form name="smyes_form" action="'. get_bloginfo("wpurl") . '/wp-admin/index.php?page=wp-yearendstats.php' .'" method="post">');		
		print ('<p>Year: <input type="text" id="smyes_year" name="smyes_year" value="' . $year . '" size="4" maxlength="4" />');
		print ('<input type="submit" value="Get Stats &raquo;"></p>');
		print ('<input type="hidden" name="smyes_action" value="getstats" />');		
		
	    if ($_POST['smyes_action'] == "getstats" && $year != 0) {

	        $num_posts = smyes_get_num_posts($year);
	        $num_comments = smyes_get_num_comments($year);
	        $avg_post_length = smyes_get_post_avg_length($year);
	        $total_post_length = smyes_get_post_total_length($year);

		    print ('
	        <table border="0" class="form-table">
	        <tbody>
		        <tr valign="top"> 
		            <th scope="row">
			    		Total number of posts in ' . $year . ':
			    	</th>
			    	<td>
			    		' . $num_posts . '
			    	</td>	
			    </tr>
			    <tr>	
		            <th scope="row">
			    		Total number of comments in ' . $year . ':
			    	</th>
			    	<td>
			    		' . $num_comments . '
			    	</td>	
			    </tr>
			    <tr>	
		            <th scope="row">
			    		Average length of posts in ' . $year . ':
			    	</th>
			    	<td>
			    		' . $avg_post_length . '
			    	</td>	
			    </tr>
			    <tr>	
		            <th scope="row">
			    		Total length of all posts in ' . $year . ':
			    	</th>
			    	<td>
			    		' . $total_post_length . '
			    	</td>	
			    </tr>
			</tbody>
			</table>
		    ');
	    }	    
		print('</form>');	    
	    print ('</div>');
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


if(!function_exists('smyes_add_menu')) {
	function smyes_add_menu() {
	    // Add a submenu to the Dashboard:
	    add_submenu_page('index.php', 'Year End Stats', 'Year End Stats', 1, __FILE__, 'smyes_displayOptions');
	}
}

add_action('admin_menu', 'smyes_add_menu');

?>