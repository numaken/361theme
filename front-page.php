<?php
// front-page.php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

// Hero section (background from theme assets)
?>
<section class="plb-hero" aria-label="Hero">
  <div class="plb-hero__bg" style="background-image:url('<?php echo esc_url( get_template_directory_uri().'/assets/images/hero.png' ); ?>');"></div>
  <div class="plb-hero__overlay"></div>
  <div class="plb-hero__content">
    <h1 class="plb-hero__title">KYOTO</h1>
    <p class="plb-hero__subtitle">Discover Hidden Gems Through VR</p>
    <div class="hero-cta-group">
      <button type="button" class="hero-cta-primary plb-jump-near" data-scroll-target="#post-list">
        <span class="cta-icon">📍</span>
        <span class="cta-text">近い順で探索</span>
      </button>
      <a href="#post-list" class="hero-cta-secondary">
        <span class="cta-icon">🗾</span>
        <span class="cta-text">すべて見る</span>
      </a>
    </div>
    <div class="hero-stats">
      <?php
      $vr_count = wp_count_posts()->publish;
      echo '<span class="stat-item"><strong>' . $vr_count . '</strong> VRスポット</span>';
      ?>
    </div>
  </div>
  <a href="#main-list" class="plb-hero__scr" aria-label="下へ"><span uk-icon="icon: chevron-down; ratio:1.2"></span></a>
</section>
<?php

// After Hero 広告枠
if (is_active_sidebar('ad_after_hero')): ?>
  <div class="uk-section uk-section-muted">
    <div class="uk-container">
      <?php dynamic_sidebar('ad_after_hero'); ?>
    </div>
  </div>
<?php endif;

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

<div id="main-list" class="content-section">
  <div class="uk-container">

    <header class="section-header">
      <div class="section-title-group">
        <h2 class="section-title">VR京都体験</h2>
        <p class="section-subtitle">360度で感じる、京都の美しさ</p>
      </div>
      <div class="plb-sort-toggle" role="group" aria-label="並び順">
        <button type="button" class="sort-btn" data-sort="new">
          <span class="sort-icon">🆕</span>
          <span class="sort-label">新着順</span>
        </button>
        <button type="button" class="sort-btn" data-sort="near">
          <span class="sort-icon">📍</span>
          <span class="sort-label">近い順</span>
        </button>
      </div>
    </header>

    <?php if ( $query->have_posts() ) : ?>
      <div id="post-list" class="visual-grid">
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
