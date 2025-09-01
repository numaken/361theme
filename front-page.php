<?php
// front-page.php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

// 初期ページ番号
$page = 1;
// 投稿を25件ずつ取得
$args = [
  'post_type'      => 'post',
  'posts_per_page' => 25,
  'paged'          => $page,
];
$query = new WP_Query( $args );
?>

<div class="uk-section uk-section-default">
  <div class="uk-container">

    <?php if ( $query->have_posts() ) : ?>
      <div id="post-list" class="uk-grid-match uk-child-width-1-3@m uk-grid-small" uk-grid>
        <?php
        while ( $query->have_posts() ) : $query->the_post();
          // ここでは template-parts/content-card.php を使います
          get_template_part( 'template-parts/content-card' );
        endwhile;
        ?>
      </div>

      <?php if ( $query->max_num_pages > 1 ) : ?>
        <div class="uk-text-center uk-margin-large-top">
          <button id="load-more"
                  class="uk-button uk-button-primary"
                  data-page="1"
                  data-max="<?php echo esc_attr( $query->max_num_pages ); ?>">
            <span class="btn-label">もっと見る</span>
            <span class="btn-spinner" hidden uk-spinner></span>
          </button>
        </div>
      <?php endif; ?>

    <?php else : ?>
      <p>記事が見つかりませんでした。</p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

  </div>
</div>

<?php get_footer(); ?>
