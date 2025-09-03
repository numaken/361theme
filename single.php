<?php
// single.php - UIkit3対応, タクソノミー強化, パンくず対応

if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>

<div class="single-post-section">
  <div class="uk-container">

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

    <article id="post-<?php the_ID(); ?>" <?php post_class('single-post-article'); ?>>

      <?php $has_vr = ! empty( $vr_url ); ?>
      
      <!-- Post Header -->
      <header class="post-header">
        <div class="post-title-group">
          <h1 class="post-title"><?php echo esc_html( $title ); ?></h1>
          <?php if ( $has_vr ) : ?>
            <div class="vr-experience-badge">
              <span class="vr-badge-icon">🥽</span>
              <span class="vr-badge-text">360° VR Experience</span>
            </div>
          <?php endif; ?>
        </div>
        
        <div class="post-meta-bar">
          <time class="post-date" datetime="<?php echo esc_attr( get_the_date('c') ); ?>">
            <span class="meta-icon">📅</span>
            <?php echo esc_html( get_the_date() ); ?>
          </time>
          
          <?php if ( $lat && $lng ) : ?>
            <div class="post-location" data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>">
              <span class="meta-icon">📍</span>
              <span class="location-text">位置情報あり</span>
            </div>
          <?php endif; ?>
        </div>
      </header>

      <!-- VR/Image Section (Full Width) -->
      <div class="post-media-full <?php echo $has_vr ? 'has-vr-content' : 'has-image-content'; ?>">
        <?php if ( $has_vr ) : ?>
          <div class="vr-viewer-container">
            <div class="vr-viewer-wrapper">
              <iframe
                src="<?php echo esc_url( $vr_url ); ?>"
                class="vr-iframe"
                allowfullscreen
                loading="lazy"
                title="<?php echo esc_attr($title); ?> VR Experience">
              </iframe>
              <div class="vr-overlay">
                <div class="vr-overlay-content">
                  <span class="vr-icon">🥽</span>
                  <span class="vr-text">VR体験中</span>
                </div>
              </div>
            </div>
            
            <div class="vr-controls uk-flex uk-flex-center uk-flex-column uk-text-center">
              <a class="vr-fullscreen-btn uk-button uk-button-primary" href="<?php echo esc_url( $vr_url ); ?>" target="_blank" rel="noopener"
                 onclick="if(typeof trackVRInteraction === 'function') trackVRInteraction('fullscreen_click', '<?php echo esc_js($title); ?>');">
                <span class="btn-icon">⛶</span>
                <span class="btn-text">全画面でVR体験</span>
              </a>
              <div class="vr-instructions uk-text-small uk-margin-small-top">
                <span class="instruction-text">マウス・タッチで360度回転</span>
              </div>
            </div>
          </div>
          
        <?php elseif ( $thumb ) : ?>
          <div class="post-image-container">
            <div class="post-image-wrapper">
              <img src="<?php echo esc_url( $thumb ); ?>"
                   alt="<?php echo esc_attr( $title ); ?>"
                   loading="lazy"
                   class="post-image"
                   onerror="this.onerror=null;this.src='<?php echo esc_url( get_template_directory_uri() . '/assets/images/noimage.png' ); ?>';">
              <div class="image-overlay">
                <div class="image-overlay-content">
                  <span class="image-icon">🏛️</span>
                  <span class="image-text">写真で見る</span>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- CTA for Business Owners -->
      <div class="uk-container uk-margin-medium">
        <div class="uk-alert-primary uk-box-shadow-small uk-margin" role="alert">
          <div class="uk-flex uk-flex-middle uk-flex-wrap">
            <div class="uk-width-expand">
              <strong>この体験をあなたの施設でも</strong><br class="uk-visible@s">
              360°VR × アプリ × Web で "来る前から好きになる" を実装します。
            </div>
            <div class="uk-margin-left uk-flex-shrink-0">
              <a class="uk-button uk-button-primary uk-margin-small-right uk-margin-small-bottom" href="/for-owners"
                 onclick="if(typeof trackCTAClick === 'function') trackCTAClick('business_cta', '資料DL（無料）');">資料DL（無料）</a>
              <a class="uk-button uk-button-default uk-margin-small-bottom" href="/cases"
                 onclick="if(typeof trackCTAClick === 'function') trackCTAClick('business_cta', '導入事例');">導入事例</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Grid Layout -->
      <div class="post-content-horizontal uk-grid-match" uk-grid>
        
        <!-- Left Column: Content & Categories -->
        <div class="uk-width-1-2@m">
          <div class="post-info-panel">
            <div class="panel-content">
              
              <!-- Categories & Tags -->
              <div class="post-taxonomy uk-margin-bottom">
                <!-- カテゴリ -->
                <div class="category-list uk-margin-small-bottom">
                  <?php
                    $cats = get_the_category();
                    foreach ( $cats as $cat ) {
                      printf(
                        '<span class="category-tag"><a href="%1$s">%2$s</a></span> ',
                        esc_url( get_category_link( $cat ) ),
                        esc_html( $cat->name )
                      );
                    }
                  ?>
                </div>

                <!-- タグ -->
                <div class="tag-list">
                  <?php
                    $tags = get_the_tags();
                    if ( $tags ) {
                      foreach ( $tags as $tag ) {
                        printf(
                          '<a href="%1$s" class="tag-link">#%2$s</a>',
                          esc_url( get_tag_link( $tag ) ),
                          esc_html( $tag->name )
                        );
                      }
                    }
                  ?>
                </div>
              </div>

              <!-- DBの本文（必要ならアンコメント） -->
              <div class="uk-margin">
                <?php echo $content_html; ?>
              </div>

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
            </div>
          </div>
        </div>

        <!-- Right Column: Map -->
        <div class="uk-width-1-2@m">
          <?php if ( $lat && $lng ) : ?>
            <div class="map-container">
              <iframe
                src="https://www.google.com/maps/embed/v1/place?key=AIzaSyD5uH8cR4XUJSgMigiJKkXxwDlGEv3zvDQ&q=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>&zoom=16&maptype=roadmap&language=ja"
                width="100%" height="400" style="border:0;" allowfullscreen loading="lazy">
              </iframe>
              <div class="map-actions uk-margin-small-top uk-flex uk-flex-center uk-child-width-auto" uk-grid>
                <div>
                  <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>" 
                     target="_blank" 
                     class="uk-button uk-button-primary uk-button-small">
                    <span uk-icon="location"></span> 経路案内
                  </a>
                </div>
                <div>
                  <a href="https://www.google.com/maps/place/<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>/@<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $lng ); ?>,16z" 
                     target="_blank" 
                     class="uk-button uk-button-default uk-button-small">
                    <span uk-icon="world"></span> 詳細表示
                  </a>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
        
      </div>

      <!-- Partner Recruitment CTA -->
      <div class="uk-container uk-margin-large">
        <div class="uk-card uk-card-default uk-card-body uk-margin">
          <div class="uk-grid-small uk-flex-middle" uk-grid>
            <div class="uk-width-expand">
              <h3 class="uk-card-title uk-margin-remove">掲載パートナー募集中</h3>
              <p class="uk-margin-small">写真と基本情報を送るだけ。地図・特集・クーポン・予約導線までセットアップ。</p>
            </div>
            <div class="uk-flex-shrink-0">
              <a class="uk-button uk-button-primary uk-margin-small-right uk-margin-small-bottom" href="/for-owners"
                 onclick="if(typeof trackCTAClick === 'function') trackCTAClick('partner_cta', '詳しく見る');">詳しく見る</a>
              <a class="uk-button uk-button-default uk-margin-small-bottom" href="https://buy.stripe.com/xxxx"
                 onclick="if(typeof trackCTAClick === 'function') trackCTAClick('partner_cta', '今すぐ申し込む');">今すぐ申し込む</a>
            </div>
          </div>
        </div>
      </div>

    </article>

    <?php endwhile; wp_reset_postdata(); endif; ?>

  </div>
</div>

<?php get_footer(); ?>
