<?php get_header(); ?>

<div class="uk-container">
  <div class="uk-grid-match uk-child-width-1-2@s uk-grid-small" uk-grid>
    <?php if ( have_posts() ) :
      while ( have_posts() ) : the_post();
        // API連携例: get_field('apicode') を取得
        $api_url = get_field('apicode');
        $obj = [];
        if ( $api_url ) {
          $resp = wp_remote_get( $api_url, [ 'timeout'=>5 ] );
          if ( ! is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200 ) {
            $body = wp_remote_retrieve_body($resp);
            $obj  = json_decode( $body, true );
          }
        }
        // フィールド fallback
        $thumb = isset($obj['thumb2x']) ? esc_url($obj['thumb2x']) : '';
    ?>
      <div>
        <article class="uk-card uk-card-default uk-card-hover uk-card-small">
          <?php if ( $thumb ) : ?>
            <div class="uk-card-media-top">
              <img src="<?php echo $thumb; ?>"
                   alt="<?php the_title_attribute(); ?>"
                   onerror="this.onerror=null; this.src='<?php echo esc_url(get_template_directory_uri().'/assets/images/noimage.png'); ?>';">
            </div>
          <?php endif; ?>
          <div class="uk-card-body">
            <h2 class="uk-card-title">
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>
            <p><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
            <a href="<?php the_permalink(); ?>" class="uk-button uk-button-text">詳細を見る</a>
          </div>
        </article>
      </div>
    <?php
      endwhile;
      // ページネーション
      echo '<div class="uk-text-center uk-margin-medium-top">';
      the_posts_pagination([
        'mid_size'  => 1,
        'prev_text' => '« 前へ',
        'next_text' => '次へ »',
      ]);
      echo '</div>';
    else : ?>
      <p>投稿が見つかりませんでした。</p>
    <?php endif; ?>
  </div>
</div>

<?php get_footer(); ?>
