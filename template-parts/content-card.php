<?php
// template-parts/content-card.php
?>
<?php
  // Inject data-lat/lng for client-side distance calc
  $geo = isset($GLOBALS['plb_geo_current']) ? $GLOBALS['plb_geo_current'] : panolabo_get_geo_for_post( get_the_ID() );
  $lat_attr = $geo && isset($geo['lat']) ? ' data-lat="' . esc_attr($geo['lat']) . '"' : '';
  $lng_attr = $geo && isset($geo['lng']) ? ' data-lng="' . esc_attr($geo['lng']) . '"' : '';

  // English short description helper (cached)
  if ( ! function_exists('plb_get_card_en_desc') ) {
    function plb_get_card_en_desc( int $post_id ) : string {
      $apicode = get_post_meta( $post_id, 'apicode', true );
      $tkey = $apicode ? 'plb_card_en_' . md5( $apicode ) : 'plb_card_en_post_' . $post_id;
      $cached = get_transient( $tkey );
      if ( is_string($cached) && $cached !== '' ) return $cached;

      $desc = '';
      if ( $apicode && function_exists('panolabo_fetch_api_data') ) {
        $data = panolabo_fetch_api_data( $apicode );
        if ( is_array($data) ) {
          foreach ( ['description_en','en_description','desc_en','enDesc'] as $k ) {
            if ( ! empty( $data[$k] ) && is_string( $data[$k] ) ) { $desc = $data[$k]; break; }
          }
        }
      }
      if ( $desc === '' ) {
        // Fallback: simple English tagline from title
        $desc = 'Discover ' . get_the_title( $post_id );
      }
      // Trim to 110 chars
      $desc = wp_html_excerpt( wp_strip_all_tags( $desc ), 110, '…' );
      $desc = esc_html( $desc );
      set_transient( $tkey, $desc, 12 * HOUR_IN_SECONDS );
      return $desc;
    }
  }
  $card_en = plb_get_card_en_desc( get_the_ID() );
?>
<div class="post-card"<?php echo $lat_attr . $lng_attr; ?>>
  <a href="<?php the_permalink(); ?>"
     class="uk-card uk-card-default uk-card-hover uk-card-small uk-inline">
    <?php
      $cats = get_the_category();
      if ( ! empty( $cats ) ) {
        $cat = $cats[0];
        echo '<span class="category-badge uk-label uk-label-success uk-position-small uk-position-top-left">' . esc_html( $cat->name ) . '</span>';
      }
    ?>
    <?php if ( has_post_thumbnail() ) : ?>
      <div class="uk-card-media-top">
        <?php the_post_thumbnail( 'medium', [
          'loading' => 'lazy',
          'decoding' => 'async',
          'class'   => 'uk-width-1-1 uk-border-rounded',
        ] ); ?>
      </div>
    <?php endif; ?>
    <div class="uk-card-body">
      <h3 class="uk-card-title uk-text-truncate"><?php the_title(); ?></h3>
      <p class="card-desc-en uk-margin-remove-top uk-text-muted"><?php echo $card_en; ?></p>
      <p class="uk-text-meta uk-flex uk-flex-between uk-margin-remove-top">
        <span><?php echo esc_html( get_the_date() ); ?></span>
        <span class="distance-meta" aria-label="距離"></span>
      </p>
    </div>
  </a>
</div>
