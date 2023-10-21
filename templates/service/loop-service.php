<?php
if(isset($_service_id)) :
?>
<div class="serviceloop <?php echo esc_attr('serviceloop-' . $_service_id); ?>">
    <div class="serviceloop__wrap">
        <?php get_the_post_thumbnail($_service_id, 'post-thumbnail', ['class' => 'serviceloop_image']); ?>
        <div class="serviceloop__title"><?php echo esc_html(get_the_title($_service_id)); ?></div>
    </div>
</div>
<?php endif; ?>