<?php
if(have_posts()) {
    while (have_posts()) {
        the_post();
        get_header('service');
        // the_content();
        $service_overview = get_field('service_overview');
        $how_to_order = get_field('how_to_order');
        
        echo do_shortcode('[elementor-template id="656"]', false);

        wp_footer();
    }
}
?>
<?php
// https://wordpress-692081-3935635.cloudwaysapps.com/wp-admin/edit.php?post_type=site-review&page=glsr-documentation&tab=shortcodes

// [site_review class="single-review full-width"]
// [site_reviews assigned_posts="post_id"]
?>