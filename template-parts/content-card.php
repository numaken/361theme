<?php
// template-parts/content-card.php
?>
<?php
  // Inject data-lat/lng for client-side distance calc
  $geo = isset($GLOBALS['plb_geo_current']) ? $GLOBALS['plb_geo_current'] : panolabo_get_geo_for_post( get_the_ID() );
  $lat_attr = $geo && isset($geo['lat']) ? ' data-lat="' . esc_attr($geo['lat']) . '"' : '';
  $lng_attr = $geo && isset($geo['lng']) ? ' data-lng="' . esc_attr($geo['lng']) . '"' : '';
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
      <p class="uk-text-meta uk-flex uk-flex-between">
        <span><?php echo esc_html( get_the_date() ); ?></span>
        <span class="distance-meta" aria-label="距離"></span>
      </p>
    </div>
  </a>
</div>
