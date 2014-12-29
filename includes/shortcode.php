<?php
/**
 * Shortcodes for WP Year End Stats plugin.
 */

namespace SM\YearEndStats\Shortcode;

/**
 * yes Stats Shortcode handler.
 */
function yes_stats_shortcode_handler( $atts ) {
    $current_year = date( 'Y' );
    $atts = shortcode_atts( array(
        'type'       => 'post_num',
        'post_type'  => 'post',
        'status'     => 'publish',
        'start_year' => $current_year,
        'end_year'   => $current_year,
        'range'      => 'to',
        'title'      => '',
        'width'      => 500,
        'height'     => 500,
    ), $atts );

    $atts['type']       = sanitize_text_field( $atts['type'] );
    $atts['post_type']  = sanitize_text_field( $atts['post_type'] );
    $atts['status']     = sanitize_text_field( $atts['status'] );
    $atts['start_year'] = absint( $atts['start_year'] );
    $atts['end_year']   = absint( $atts['end_year'] );
    $atts['range']      = ( 'and' == $atts['range'] ) ? 'and': 'to';
    $atts['title']      = sanitize_text_field( $atts['title'] );
    $atts['width']      = absint( $atts['width'] );
    $atts['height']     = absint( $atts['height'] );

    $stats = new \SM\YearEndStats\Stats( $atts );
    return $stats->generate_graph();
}
add_shortcode( 'yes_stats', __NAMESPACE__ . '\yes_stats_shortcode_handler' );
?>
