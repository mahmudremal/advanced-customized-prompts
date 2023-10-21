<?php
if(have_posts()) {
    while (have_posts()) {
        the_post();
        get_header('service');
        // the_content();
        $service_overview = get_field('service_overview');
        $how_to_order = get_field('how_to_order');
        
        if(true) {
            echo do_shortcode(['[elementor-template id="656"]'], false);
        } else {
            ?>
                <?php // the_title('<h1>', '</h1>'); ?>
                <?php // print_r(SOSPOPSPROJECT_DIR_PATH . '/templates/service/single-service.php'); ?>

                <div class="ss-listing">
                    <div class="ss-listing__wrapper">
                        <div class="ss-listing__row">
                            <div class="ss-listing__col-3">
                                <div class="ss-listing__sidebar">
                                    <h4 class="ss-listing__sidebar__header"><?php esc_html_e('Service Overview', 'domain'); ?></h4>
                                    <ul class="ss-listing__sidebar__list">
                                        <li class="ss-list__single"><a href="#service-review" class="ss-list__link"><?php esc_html_e('Review', 'domain'); ?></a></li>
                                        <li class="ss-list__single"><a href="#service-details" class="ss-list__link"><?php esc_html_e('Details', 'domain'); ?></a></li>
                                        <li class="ss-list__single"><a href="#service-faqs" class="ss-list__link"><?php esc_html_e('FAQ', 'domain'); ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="ss-listing__col-9">
                                <div id="service-overview">
                                    <h3><?php echo esc_html(sprintf(__('Overview of %s', 'domain'), get_the_title())); ?></h3>
                                    <?php echo wp_kses_post(get_post_meta(get_the_ID(), 'overview', true)); ?>
                                </div>
                                <div id="service-review">
                                    <h3><?php esc_html_e('Review', 'domain'); ?></h3>
                                    <?php echo do_shortcode('[site_reviews assigned_posts="' . esc_attr(get_the_ID()) . '" pagination="ajax" display="6"]', false); ?>
                                    <?php echo do_shortcode('[site_reviews_form assigned_posts="' . esc_attr(get_the_ID()) . '" hide="email,terms,title"]', false); // images,content,name,rating, ?>
                                </div>
                                <div id="service-details">
                                    <h3><?php esc_html_e('Details', 'domain'); ?></h3>
                                    <?php echo wp_kses_post(get_post_meta(get_the_ID(), 'details', true)); ?>
                                </div>
                                <div id="service-faqs">
                                    <h3><?php esc_html_e('FAQ', 'domain'); ?></h3>
                                    <?php echo do_shortcode('[elementor-template id="574"]', false); ?>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- <button class="btn button custom_pops_btn" data-config="<?php echo esc_attr(json_encode(['id' => get_the_ID()])); ?>"><?php esc_html_e('Open Popup', 'domain'); ?></button>

                <h2><?php // esc_html_e('Service Overview:', 'domain'); ?></h2>
                <p><?php // echo wp_kses_post($service_overview); ?></p>
                <h2><?php // esc_html_e('How to Order:', 'domain'); ?></h2>
                <p><?php // echo wp_kses_post($how_to_order); ?></p> -->
                
            <?php
        }
        wp_footer();
    }
}
?>

<?php
// https://wordpress-692081-3935635.cloudwaysapps.com/wp-admin/edit.php?post_type=site-review&page=glsr-documentation&tab=shortcodes

// [site_review class="single-review full-width"]
// [site_reviews assigned_posts="post_id"]
?>