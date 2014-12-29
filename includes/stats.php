<?php
/**
 * Calculate stats about posts and generate graph.
 *
 * TODO: Replace queries with WordPress functions.
 * TODO: Move inline JavaScript code to a new file.
 * TODO: Cache the stats.
 */
namespace SM\YearEndStats;

/**
 * Stats about posts.
 */
class Stats {
    /**
     * List of attributes passed to the shortcode.
     */
    protected $atts;

    /**
     * Initialize the class.
     *
     * @param array $atts List of attributes passed to shortcode.
     */
    public function __construct( $atts ) {
        $this->atts = $atts;
    }

    /**
     * Generate Graph.
     *
     * @return string The HTML content for graph.
     */
    public function generate_graph() {
        $stats = $this->get_stats();
        $graph_id = uniqid( 'graph_' );
        ob_start();
?>
        <div id="graph_container">
            <div id="<?php echo $graph_id;?>" class="yes_graph" style="width:<?php echo $this->atts['width']; ?>;height:<?php echo $this->atts['height']; ?>px;"></div>
            <h2><?php echo $this->atts['title']; ?></h2>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function () {
            var <?php echo $graph_id; ?> = <?php echo json_encode( $stats ); ?>,
                options = {
                    series: {
                        points: { show: true },
                        lines: { show: true },
                    },
                    grid: {
                        hoverable: true,
                        clickable: true
                    },
                    xaxis: { ticks: <?php echo count( $stats ) - 1; ?> }
                };
            jQuery.plot('#<?php echo $graph_id; ?>', [<?php echo $graph_id; ?>], options );

            jQuery("<div id='<?php echo $graph_id;?>_tooltip'></div>").css({
                position: "absolute",
                display: "none",
                border: "1px solid #fdd",
                padding: "2px",
                "background-color": "#fee",
                opacity: 0.80
            }).appendTo("body");

            jQuery("#<?php echo $graph_id; ?>").bind("plothover", function (event, pos, item) {
                if (item) {
                    var x = item.datapoint[0].toFixed(2),
                        y = item.datapoint[1].toFixed(2);

                    jQuery("#<?php echo $graph_id; ?>_tooltip").html(Math.round(x) + ", " + Math.round(y))
                        .css({top: item.pageY+5, left: item.pageX+5})
                        .fadeIn(200);
                } else {
                    jQuery("#tooltip").hide();
                }
            });
        });
        </script>
<?php
        return ob_get_clean();
    }

    /**
     * Get status based on graph type.
     *
     * @return array $stats Stats about the current graph.
     */
    protected function get_stats() {
        $stats = array();
        switch ( $this->atts['type'] ) {
            case 'post_num':
                $stats = $this->get_num_posts();
                break;

            case 'comment_num':
                $stats = $this->get_num_comments();
                break;

            case 'post_avg_length':
                $stats = $this->get_post_avg_length();
                break;

            case 'post_total_length':
                $stats = $this->get_post_total_length();
                break;

            default:
                $stats = $this->get_num_posts();
                break;
        }
        return $stats;
    }

    /**
     * Get number of posts stats.
     *
     * @return array Number of posts stats
     */
    protected function get_num_posts() {
        $year_range = $this->get_year_range();
        $stats = array();
        foreach ( $year_range as $year ){
            $stats[] = array( $year, absint( $this->get_num_posts_by_year( $year ) ) );
        }
        return $stats;
    }

    /**
     * Get the Number of posts in a year.
     *
     * @param int $year
     * @return int
     */
    protected function get_num_posts_by_year( $year ) {
        global $wpdb;
        $next_year = $year + 1;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->posts WHERE post_date >= %s AND post_date < %s AND post_status = %s AND post_type = %s",
                "$year-01-01",
                "$next_year-01-01",
                $this->atts['status'],
                $this->atts['post_type']
            )
        );
    }

    /**
     * Get number of comments stats.
     *
     * @return array Number of comments stats
     */
    protected function get_num_comments() {
        $year_range = $this->get_year_range();
        $stats = array();
        foreach ( $year_range as $year ){
            $stats[] = array( $year, absint( $this->get_num_comments_by_year( $year ) ) );
        }
        return $stats;
    }

    /**
     * Get the number of comments in a year.
     *
     * @param int $year
     * @return int
     */
    protected function get_num_comments_by_year( $year ) {
        global $wpdb;
        $next_year = $year + 1;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_date >= %s AND comment_date < %s AND comment_approved = '1'",
                "$year-01-01",
                "$next_year-01-01"
            )
        );
    }

    /**
     * Get average post length stats.
     *
     * @return array Average post length stats
     */
    protected function get_post_avg_length() {
        $year_range = $this->get_year_range();
        $stats = array();
        foreach ( $year_range as $year ){
            $stats[] = array( $year, absint( $this->get_post_avg_length_by_year( $year ) ) );
        }
        return $stats;
    }

    /**
     * Get the average length of posts in a year
     *
     * @param int $year
     * @return int
     */
    protected function get_post_avg_length_by_year( $year ) {
        global $wpdb;
        $next_year = $year + 1;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT AVG(LENGTH(post_content)) FROM $wpdb->posts WHERE post_date >= %s AND post_date < %s AND post_status = %s AND post_type = %s",
                "$year-01-01",
                "$next_year-01-01",
                $this->atts['status'],
                $this->atts['post_type']
            )
        );
    }

    /**
     * Get average post length stats.
     *
     * @return array Average post length stats
     */
    protected function get_post_total_length() {
        $year_range = $this->get_year_range();
        $stats = array();
        foreach ( $year_range as $year ) {
            $stats[] = array( $year, absint( $this->get_post_total_length_by_year( $year ) ) );
        }
        return $stats;
    }

    /**
     * Get the total length of posts in a year
     *
     * @param int $year
     * @return int
     */
    protected function get_post_total_length_by_year( $year ) {
        global $wpdb;
        $next_year = $year + 1;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(LENGTH(post_content)) FROM $wpdb->posts WHERE post_date >= %s AND post_date < %s AND post_status = %s AND post_type = %s",
                "$year-01-01",
                "$next_year-01-01",
                $this->atts['status'],
                $this->atts['post_type']
            )
        );
    }

    /**
     * Calculated year range.
     *
     * @return array Year range
     */
    protected function get_year_range() {
        $year_range = array();

        switch ( $this->atts['range'] ) {
            case 'and':
                $year_range[] = $this->atts['start_year'];
                if ( $this->atts['start_year'] != $this->atts['end_year'] ) {
                    $year_range[] = $this->atts['end_year'];
                }
                break;

            case 'to':
                if ( $this->atts['start_year'] > $this->atts['end_year'] ) {
                    $tmp = $this->atts['start_year'];
                    $this->atts['start_year'] = $this->atts['end_year'];
                    $this->atts['end_year'] = $tmp;
                }
                for ( $i = $this->atts['start_year']; $i <= $this->atts['end_year']; $i++ ) {
                    $year_range[] = $i;
                }
                break;
        }
        return $year_range;
    }

}
?>
