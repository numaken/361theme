<?php
// template-parts/content-card.php
?>
<?php
  // Get geo location data for distance calculation
  $geo = isset($GLOBALS['plb_geo_current']) ? $GLOBALS['plb_geo_current'] : panolabo_get_geo_for_post( get_the_ID() );
  $lat_attr = $geo && isset($geo['lat']) ? ' data-lat="' . esc_attr($geo['lat']) . '"' : '';
  $lng_attr = $geo && isset($geo['lng']) ? ' data-lng="' . esc_attr($geo['lng']) . '"' : '';

  // Check for VR content availability
  $apicode = get_post_meta( get_the_ID(), 'apicode', true );
  $has_vr = false;
  $vr_url = '';
  
  if ( $apicode && function_exists('panolabo_fetch_api_data') ) {
    $data = panolabo_fetch_api_data( $apicode );
    if ( is_array($data) && !empty($data['authored_index_url_secure']) ) {
      $has_vr = true;
      $vr_url = esc_url($data['authored_index_url_secure']);
    }
  }

  // Enhanced English description with VR context
  if ( ! function_exists('plb_get_enhanced_card_desc') ) {
    function plb_get_enhanced_card_desc( int $post_id, bool $has_vr ) : string {
      $apicode = get_post_meta( $post_id, 'apicode', true );
      $tkey = 'plb_enhanced_card_' . md5( $post_id . ($has_vr ? '_vr' : '') );
      $cached = get_transient( $tkey );
      if ( is_string($cached) && $cached !== '' ) return $cached;

      $desc = '';
      if ( $apicode && function_exists('panolabo_fetch_api_data') ) {
        $data = panolabo_fetch_api_data( $apicode );
        if ( is_array($data) ) {
          foreach ( ['description_en','en_description','desc_en','enDesc'] as $k ) {
            if ( ! empty( $data[$k] ) && is_string( $data[$k] ) ) { 
              $desc = $data[$k]; 
              break; 
            }
          }
        }
      }
      
      if ( $desc === '' ) {
        $title = get_the_title( $post_id );
        $desc = $has_vr ? 'Experience ' . $title . ' in immersive VR' : 'Discover the beauty of ' . $title;
      }
      
      // Add VR context if available
      if ( $has_vr && strpos(strtolower($desc), 'vr') === false ) {
        $desc = 'VR Experience: ' . $desc;
      }
      
      $desc = wp_html_excerpt( wp_strip_all_tags( $desc ), 120, '…' );
      $desc = esc_html( $desc );
      set_transient( $tkey, $desc, 12 * HOUR_IN_SECONDS );
      return $desc;
    }
  }
  $card_desc = plb_get_enhanced_card_desc( get_the_ID(), $has_vr );

  // Thumbnail resolution with higher priority for VR content
  $thumb_url = '';
  if ( has_post_thumbnail() ) {
    $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'large' );
  }
  if ( ! $thumb_url && $apicode ) {
    if ( function_exists('panolabo_fetch_api_data') ) {
      $data = panolabo_fetch_api_data( $apicode );
      if ( is_array($data) ) {
        // Prioritize higher quality images for VR content
        $raw = $has_vr ? 
          ($data['thumb2x'] ?? $data['thumb'] ?? '') : 
          ($data['thumb'] ?? $data['thumb2x'] ?? '');
        if ( $raw ) {
          $thumb_url = function_exists('panolabo_normalize_thumbnail_url') ? 
            panolabo_normalize_thumbnail_url( $raw ) : esc_url( $raw );
        }
      }
    }
  }

  // Get category for visual clustering
  $cats = get_the_category();
  $main_cat = !empty($cats) ? $cats[0] : null;
?>
<article class="post-card<?php echo $has_vr ? ' has-vr' : ''; ?>"<?php echo $lat_attr . $lng_attr; ?> data-post-id="<?php the_ID(); ?>">
  <a href="<?php the_permalink(); ?>" class="card-link" aria-label="<?php echo esc_attr(get_the_title()); ?>の詳細を見る">
    
    <!-- Visual Indicators -->
    <?php if ( $has_vr ) : ?>
      <div class="vr-indicator" title="VR体験可能">
        <span class="vr-icon">🥽</span>
        <span class="vr-text">VR</span>
      </div>
    <?php endif; ?>
    
    <?php if ( $geo && isset($geo['lat']) ) : ?>
      <div class="location-indicator" title="位置情報あり">
        <span class="location-icon">📍</span>
      </div>
    <?php endif; ?>

    <!-- Category Badge -->
    <?php if ( $main_cat ) : ?>
      <div class="category-badge" data-category="<?php echo esc_attr($main_cat->slug); ?>">
        <?php echo esc_html( $main_cat->name ); ?>
      </div>
    <?php endif; ?>

    <!-- Visual Content -->
    <div class="card-visual">
      <?php if ( $thumb_url ) : ?>
        <div class="card-image-wrapper">
          <img src="<?php echo esc_url( $thumb_url ); ?>" 
               alt="<?php echo esc_attr( get_the_title() ); ?>" 
               loading="lazy" 
               decoding="async" 
               class="card-image" />
          <div class="image-overlay"></div>
        </div>
      <?php else : ?>
        <div class="card-placeholder">
          <span class="placeholder-icon">🏛️</span>
          <span class="placeholder-text">Image Loading...</span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Content Information -->
    <div class="card-content">
      <header class="card-header">
        <h3 class="card-title"><?php the_title(); ?></h3>
        <p class="card-description"><?php echo $card_desc; ?></p>
      </header>
      
      <footer class="card-meta">
        <div class="meta-group">
          <span class="meta-date" title="投稿日">
            <span class="meta-icon">📅</span>
            <?php echo esc_html( get_the_date('M j') ); ?>
          </span>
          <span class="meta-distance" title="現在地からの距離">
            <span class="meta-icon">🚶</span>
            <span class="distance-value" aria-label="距離">---</span>
          </span>
        </div>
        
        <?php if ( $has_vr ) : ?>
          <div class="vr-badge" title="360度VR体験">
            <span class="vr-badge-text">360° VR</span>
          </div>
        <?php endif; ?>
      </footer>
    </div>

    <!-- Hover Effect Overlay -->
    <div class="card-hover-overlay">
      <div class="hover-content">
        <span class="hover-icon">👀</span>
        <span class="hover-text"><?php echo $has_vr ? 'VR体験する' : '詳細を見る'; ?></span>
      </div>
    </div>

  </a>
</article>
