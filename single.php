<?php
// single.php - UIkit3対応, タクソノミー強化, パンくず対応

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<div class="uk-section uk-section-default">
  <div class="uk-container">

    <?php if ( function_exists('yoast_breadcrumb') ) {
      yoast_breadcrumb( '<div class="uk-margin-bottom">','</div>' );
    } ?>

    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

    <?php
      // DBの本文は使わない場合はコメントアウト
      $content_html = apply_filters( 'the_content', get_the_content() );

      // カスタムフィールドから apicode を取得し、API URL を組み立て
      $code    = get_post_meta( get_the_ID(), 'apicode', true );
      $api_url = filter_var( $code, FILTER_VALIDATE_URL )
               ? $code
               : 'https://api.panolabo.com/contents/' . rawurlencode( $code );

      $data = panolabo_fetch_url( $api_url );

      $title       = sanitize_text_field( $data['title']         ?? get_the_title() );
      $description = wp_kses_post( nl2br( $data['description'] ?? '' ) );
      $vr_url      = esc_url( $data['authored_index_url_secure'] ?? '' );
      $main_cat    = sanitize_text_field( $data['main_category'] ?? '' );
      $sub_cat     = sanitize_text_field( $data['sub_category']  ?? '' );
      $address     = sanitize_text_field( $data['address']       ?? '' );
      $tel         = sanitize_text_field( $data['tel']           ?? '' );
      $lat         = esc_attr( $data['lat'] ?? '' );
      $lng         = esc_attr( $data['lng'] ?? '' );

      // ──────── サムネイル取得 ────────
      // API の thumb2x を優先、なければ thumb。それを S3 の HTTPS ドメインに変換
      $thumb_raw = $data['thumb2x'] ?? $data['thumb'] ?? '';
      if ( $thumb_raw ) {
        // static.panolabo.com → s3-ap-northeast-1.amazonaws.com/static.panolabo.com
        $thumb = preg_replace(
          '#^https?://static\.panolabo\.com/#',
          'https://s3-ap-northeast-1.amazonaws.com/static.panolabo.com/',
          $thumb_raw
        );
      } else {
        $thumb = '';
      }
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class('uk-article'); ?>>

      <?php $has_vr = ! empty( $vr_url ); ?>
      <div class="uk-grid-large" uk-grid>
        <!-- 左カラム：メディア（VR優先、なければサムネ） -->
        <div class="<?php echo $has_vr ? 'uk-width-2-3@m' : ( $thumb ? 'uk-width-1-3@m' : 'uk-width-1-1' ); ?>">
          <?php if ( $has_vr ) : ?>
            <div class="plb-vr-wrap" style="position:relative;width:100%;height:0;padding-bottom:56.25%;">
              <iframe
                src="<?php echo esc_url( $vr_url ); ?>"
                style="position:absolute;inset:0;border:none;width:100%;height:100%;"
                allowfullscreen uk-responsive uk-video="automute: true"
                loading="lazy">
              </iframe>
            </div>
            <div class="uk-margin-small-top">
              <a class="uk-button uk-button-default uk-button-small" href="<?php echo esc_url( $vr_url ); ?>" target="_blank" rel="noopener">全画面で見る</a>
            </div>
          <?php elseif ( $thumb ) : ?>
            <img src="<?php echo esc_url( $thumb ); ?>"
                 alt="<?php echo esc_attr( $title ); ?>"
                 loading="lazy"
                 class="uk-border-rounded uk-width-1-1"
                 onerror="this.onerror=null;this.src='<?php echo esc_url( get_template_directory_uri() . '/assets/images/noimage.png' ); ?>';">
          <?php endif; ?>
        </div>

        <!-- 右カラム：本文・メタ -->
        <div class="<?php echo ( $has_vr || $thumb ) ? 'uk-width-expand@m' : 'uk-width-1-1'; ?>">
          <h1 class="uk-article-title"><?php echo esc_html( $title ); ?></h1>
          <p class="uk-article-meta"><?php echo esc_html( get_the_date() ); ?></p>

          <!-- カテゴリ -->
          <div class="uk-margin-small-bottom">
            <?php
              $cats = get_the_category();
              foreach ( $cats as $cat ) {
                $color = get_term_meta( $cat->term_id, 'color', true ) ?: 'default';
                printf(
                  '<span class="uk-label uk-label-%1$s"><a href="%2$s" class="uk-link-reset">%3$s</a></span> ',
                  esc_attr( $color ),
                  esc_url( get_category_link( $cat ) ),
                  esc_html( $cat->name )
                );
              }
            ?>
          </div>

          <!-- タグ -->
          <div class="uk-margin-small-bottom">
            <?php
              $tags = get_the_tags();
              if ( $tags ) {
                foreach ( $tags as $tag ) {
                  printf(
                    '<a href="%1$s" class="uk-button uk-button-text uk-margin-small-right">#%2$s</a>',
                    esc_url( get_tag_link( $tag ) ),
                    esc_html( $tag->name )
                  );
                }
              }
            ?>
          </div>

          <!-- カスタムタクソノミー -->
          <div class="uk-margin-small-bottom">
            <?php
              $taxonomies = get_object_taxonomies( get_post_type(), 'objects' );
              foreach ( $taxonomies as $tax ) {
                if ( $tax->public && ! in_array( $tax->name, ['category','post_tag'], true ) ) {
                  $terms = get_the_terms( get_the_ID(), $tax->name );
                  if ( $terms && ! is_wp_error( $terms ) ) {
                    echo '<div><strong>' . esc_html( $tax->labels->singular_name ) . ':</strong> ';
                    foreach ( $terms as $term ) {
                      printf(
                        '<a href="%1$s" class="uk-button uk-button-small uk-button-default uk-margin-small-right">%2$s</a>',
                        esc_url( get_term_link( $term ) ),
                        esc_html( $term->name )
                      );
                    }
                    echo '</div>';
                  }
                }
              }
            ?>
          </div>

          <!-- DBの本文（必要ならアンコメント） -->
          <div class="uk-margin">
            <?php  echo $content_html; ?>
          </div>
        </div>
      </div>

      <!-- 説明文 -->
      <?php if ( $description ) : ?>
        <div class="uk-margin-medium-top">
          <!--<?php echo $description; ?>-->
        </div>
      <?php endif; ?>

      <!-- 基本情報 -->
      <ul class="uk-list uk-list-divider uk-margin-top">
        <?php if ( $main_cat || $sub_cat ) : ?>
          <li>
            <strong>カテゴリ:</strong>
            <?php
              echo esc_html( $main_cat );
              if ( $sub_cat ) {
                echo ' &raquo; ' . esc_html( $sub_cat );
              }
            ?>
          </li>
        <?php endif; ?>
        <?php if ( $address ) : ?>
          <li><strong>住所:</strong> <?php echo esc_html( $address ); ?></li>
        <?php endif; ?>
        <?php if ( $tel ) : ?>
          <li><strong>電話:</strong> <?php echo esc_html( $tel ); ?></li>
        <?php endif; ?>
      </ul>

      <?php /* 下部のVRビューアは上部に統合したため削除 */ ?>

      <!-- Google Map -->
      <?php if ( $lat && $lng ) : ?>
        <div class="uk-margin-large-top">
          <iframe
            src="https://www.google.com/maps?q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>&hl=ja&z=15&output=embed"
            width="100%" height="400" style="border:0;" allowfullscreen loading="lazy">
          </iframe>
        </div>
      <?php endif; ?>

    </article>

    <?php endwhile; wp_reset_postdata(); endif; ?>

  </div>
</div>

<?php get_footer(); ?>
