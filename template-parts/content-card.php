<?php
// template-parts/content-card.php
?>
<div>
  <a href="<?php the_permalink(); ?>"
     class="uk-card uk-card-default uk-card-hover uk-card-small">
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
      <p class="uk-text-meta"><?php echo esc_html( get_the_date() ); ?></p>
    </div>
  </a>
</div>
