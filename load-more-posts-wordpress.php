load-more.php

<?php get_header(); ?>
<div id="main-content">
    <?php
    $args = array('post_type' => 'project',
        'posts_per_page' => 9);
    $loop = new WP_Query($args);
    ?>
    <article class="portfolio_grid" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="entry-content" id="ajax-posts">
            <div class="portfolio_title"><h1>PORTFOLIO</h1></div>
            <?php while ($loop->have_posts()) : $loop->the_post(); ?>
                <div class="et_pb_portfolio_item">
                    <a href="<?php echo get_site_url(); ?>/portfolio-detail?project_id=<?php echo get_the_ID(); ?>">
                        <div class="project_image"><?php echo get_the_post_thumbnail(get_the_ID(), 'large'); ?></div>
                        <div class="project_title"><?php the_title(); ?></div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
        <div id="more_posts">Show More</div>
    </article>
</div>
<style type="text/css">
    #more_posts{text-decoration: underline;font-family: "dinlight",sans-serif;cursor: pointer}
</style>
<script type="text/javascript">
    var posts_per_page = 3;
    var pageNumber = 3;
    function load_posts() {
        pageNumber++;
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        var str = '&pageNumber=' + pageNumber + '&posts_per_page=' + posts_per_page + '&action=more_post_ajax';
        jQuery.ajax({
            type: "POST",
            dataType: "html",
            url: ajaxurl,
            data: str,
            success: function (data) {
                var $data = jQuery(data);
                if ($data.length < 3)
                {
                    jQuery("#more_posts").hide();
                    jQuery("#ajax-posts").append($data);
                }
                else if ($data.length) {
                    jQuery("#ajax-posts").append($data);
                    jQuery("#more_posts").attr("disabled", false);
                } else {
                    jQuery("#more_posts").attr("disabled", true);
                }
            },
            error: function () {
                jQuery("#ajax-posts").html('Error ijn displaying posts');
            }

        });
        return false;
    }

    jQuery("#more_posts").on("click", function () {
        jQuery("#more_posts").attr("disabled", true);
        load_posts();
    });
</script>
<?php get_footer(); ?>

functions.php()

 function more_post_ajax() {
        $ppp = (isset($_POST["ppp"])) ? $_POST["ppp"] : 3;
        $page = (isset($_POST['pageNumber'])) ? $_POST['pageNumber'] : 0;

        header("Content-Type: text/html");

        $args = array(
            'post_type' => 'project',
            'posts_per_page' => $ppp,
            'paged' => $page,
        );

        $loop = new WP_Query($args);

        $out = '';

        if ($loop->have_posts()) : while ($loop->have_posts()) : $loop->the_post();
                $out .= '<div class="et_pb_portfolio_item">
                <div class="project_image">' . get_the_post_thumbnail(get_the_ID(), 'large') . '</div>
                 <div class="project_title">' . get_the_title() . '</div>
         </div>';

            endwhile;
        endif;
        wp_reset_postdata();
        die($out);
    }

    add_action('wp_ajax_nopriv_more_post_ajax', 'more_post_ajax');
    add_action('wp_ajax_more_post_ajax', 'more_post_ajax');
