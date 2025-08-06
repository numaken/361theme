<?php
// front-page.php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<!-- HERO セクション -->
<div class="uk-section uk-padding-remove-vertical">
  <div class="uk-background-cover uk-light" style="background-image:url('<?php echo esc_url(get_post_meta( get_option('page_on_front'), 'cached_thumb', true )); ?>');">
    <div class="uk-container uk-flex uk-flex-center uk-flex-middle" style="height:60vh;">
      <div class="uk-text-center">
        <h1 class="uk-heading-medium uk-text-bold">Explore Kyoto in VR</h1>
        <p class="uk-text-lead">最新のVRビューで、古都の魅力を体験しよう</p>
      </div>
    </div>
  </div>
</div>

<!-- ピックアップ記事（ACFなどで設定された注目記事） -->
<div class="uk-section uk-section-muted">
  <div class="uk-container">

    <h1 class="uk-heading-medium uk-text-center uk-margin-large-bottom">Explore Kyoto in VR</h1>


<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8539502502589814"
     crossorigin="anonymous"></script>
<!-- 右上AD -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-8539502502589814"
     data-ad-slot="4304001980"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>

    <div class="uk-child-width-1-2@m uk-grid-match" uk-grid>

      <?php
      $args = ['post_type' => 'post', 'posts_per_page' => -1];
      $query = new WP_Query($args);
      while ($query->have_posts()) : $query->the_post();

        $post_id   = get_the_ID();
        $title     = get_post_meta($post_id, 'cached_title', true) ?: get_the_title();
        $thumb     = get_post_meta($post_id, 'cached_thumb', true);
        $desc      = get_post_meta($post_id, 'cached_description', true);
        $vr_url    = get_post_meta($post_id, 'cached_vr_url', true);
      ?>

      <div>

        <div class="uk-card uk-card-default uk-grid-collapse uk-child-width-1-2@s uk-margin" uk-grid>
          <div class="uk-card-media-left uk-cover-container">
            <?php if ($thumb): ?>
              <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($title); ?>" uk-cover
                   onerror="this.onerror=null;this.src='<?php echo esc_url(get_template_directory_uri() . '/assets/images/noimage.png'); ?>';">
              <canvas width="600" height="400"></canvas>
            <?php endif; ?>
          </div>
          <div>
            <div class="uk-card-body">
              <h3 class="uk-card-title"><?php echo esc_html($title); ?></h3>
              <p><?php echo esc_html(wp_trim_words($desc, 30, '…')); ?></p>
              <p>
                <a href="<?php the_permalink(); ?>" class="uk-button uk-button-primary uk-button-small">詳細を見る</a>
                <?php if ($vr_url): ?>
                  <a href="<?php echo esc_url($vr_url); ?>" target="_blank" class="uk-button uk-button-default uk-button-small">VR体験</a>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>

      </div>

      <?php endwhile; wp_reset_postdata(); ?>

    </div>
  </div>
</div>


<!-- カードグリッド -->
<div class="uk-section">
  <div class="uk-container">
    <div class="uk-child-width-1-2@s uk-child-width-1-3@m uk-grid-match" uk-grid>

      <?php
      $q = new WP_Query(['post_type'=>'post','posts_per_page'=>-1]);
      while( $q->have_posts() ): $q->the_post();
        $id    = get_the_ID();
        $title = get_post_meta($id,'cached_title',true) ?: get_the_title();
        $img   = get_post_meta($id,'cached_thumb',true);
        $desc  = get_post_meta($id,'cached_description',true);
        $vr    = get_post_meta($id,'cached_vr_url',true);
      ?>

      <div>
        <div class="uk-card uk-card-default uk-card-hover uk-card-small">
          <?php if($img): ?>
            <div class="uk-card-media-top uk-cover-container">
              <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" uk-cover>
              <canvas width="600" height="400"></canvas>
            </div>
          <?php endif; ?>
          <div class="uk-card-body">
            <h3 class="uk-card-title uk-margin-remove"><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html(wp_trim_words($desc,25,'…')); ?></p>
            <p class="uk-margin-small-top">
              <a href="<?php the_permalink(); ?>" class="uk-button uk-button-primary uk-button-small">詳細を見る</a>
              <?php if($vr): ?>
                <a href="<?php echo esc_url($vr); ?>" target="_blank" class="uk-button uk-button-text uk-button-small">VR体験</a>
              <?php endif; ?>
            </p>
          </div>
        </div>
      </div>

      <?php endwhile; wp_reset_postdata(); ?>

    </div>
  </div>
</div>

<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-8539502502589814"
     crossorigin="anonymous"></script>
<!-- text-responsible -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-8539502502589814"
     data-ad-slot="9359447187"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>

<!-- 地図＋カテゴリ検索（後日JS連携も可能） -->
<div class="uk-section uk-section-secondary uk-light">
  <div class="uk-container uk-text-center">
    <h3 class="uk-heading-bullet">Find by Category or Location</h3>
    <div class="uk-margin">
      <?php
        $cats = get_categories();
        foreach ($cats as $cat) {
          echo '<a href="' . esc_url(get_category_link($cat->term_id)) . '" class="uk-button uk-button-small uk-button-primary uk-margin-small-right uk-margin-small">' . esc_html($cat->name) . '</a>';
        }
      ?>
    </div>

  </div>
</div>

<?php get_footer(); ?>
