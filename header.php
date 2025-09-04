<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google-adsense-account" content="ca-pub-8539502502589814">
  
  <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-PNMT8QWB');</script>
  <!-- End Google Tag Manager -->
  
  <!-- Google Analytics 4 -->
  <?php 
  $ga_id = get_theme_mod('ga4_measurement_id', '');
  if ($ga_id) : ?>
    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo esc_js($ga_id); ?>', {
        'anonymize_ip': true,
        'custom_map': {'custom_parameter_1': 'vr_interaction'}
      });
      
      // VR体験トラッキング
      function trackVRInteraction(action, spot_name) {
        gtag('event', 'vr_interaction', {
          'event_category': 'VR Experience',
          'event_label': spot_name,
          'custom_parameter_1': action
        });
      }
      
      // CTAクリックトラッキング
      function trackCTAClick(cta_type, button_text) {
        gtag('event', 'cta_click', {
          'event_category': 'CTA',
          'event_label': button_text,
          'custom_parameter_1': cta_type
        });
      }
    </script>
  <?php endif; ?>
  
  <?php wp_head(); ?>
  <?php $plb_client = get_theme_mod('plb_adsense_client', 'ca-pub-8539502502589814');
    if ( $plb_client ) : ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo esc_attr($plb_client); ?>" crossorigin="anonymous"></script>
  <?php endif; ?>
  <style>:root { --accent: #1E7C6E; --header-bg:#fff; }</style>
</head>
<body <?php body_class(); ?>>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PNMT8QWB"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
<?php if ( function_exists('wp_body_open') ) { wp_body_open(); } ?>
<a href="#main-content" class="uk-skip-link uk-position-fixed uk-position-top-left uk-padding-small"><?php esc_html_e('コンテンツへスキップ','panolabo'); ?></a>

<header class="site-header" id="site-header" role="banner">
  <!-- Main Header Bar -->
  <div class="header-main">
    <div class="container uk-container">
      <div class="header-content">
        <div class="brand">
          <a class="logo" href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php bloginfo('name'); ?>">
            <?php if ( function_exists('the_custom_logo') && has_custom_logo() ) { 
              the_custom_logo(); 
            } else { ?>
              <span class="site-title"><?php bloginfo('name'); ?></span>
            <?php } ?>
          </a>
        </div>
        
        <nav class="primary-nav" aria-label="Primary">
          <?php
            wp_nav_menu([
              'theme_location' => 'primary',
              'container'      => false,
              'menu_class'     => 'menu menu--primary',
              'fallback_cb'    => function(){ 
                if ( current_user_can('manage_options') ) {
                  echo '<ul class="menu"><li><a href="'.esc_url( admin_url('nav-menus.php') ).'">Set Primary Menu</a></li></ul>';
                }
              }
            ]);
          ?>
        </nav>
        
        <div class="header-actions">
          <?php if ( is_front_page() ) : ?>
            <button class="btn-link distance-toggle" data-action="toggle-distance" aria-pressed="false" title="近い順で表示">
              <span class="icon">📍</span>
              <span class="text">近い順</span>
            </button>
          <?php endif; ?>
          <button class="btn-link search-toggle" data-action="open-search" aria-expanded="false" aria-controls="header-search" title="検索">
            <span class="icon">🔍</span>
          </button>
          <button class="hamburger" id="hamburger" aria-expanded="false" aria-controls="mobile-drawer" aria-label="メニューを開く">
            <span></span><span></span><span></span>
          </button>
        </div>
      </div>
    </div>
  </div>


  <!-- Search Bar -->
  <div class="header-search" id="header-search" hidden>
    <div class="container uk-container">
      <?php get_search_form(); ?>
    </div>
  </div>
</header>

<div id="mobile-drawer" class="mobile-drawer" hidden>
  <div class="drawer-inner">
    <button class="drawer-close" data-action="close-drawer" aria-label="Close menu">×</button>
    <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container'      => 'nav',
        'container_class'=> 'drawer-nav',
        'menu_class'     => 'menu menu--drawer',
        'fallback_cb'    => function(){
          if ( current_user_can('manage_options') ) {
            echo '<nav class="drawer-nav"><ul class="menu menu--drawer"><li><a href="'.esc_url( admin_url('nav-menus.php') ).'">Set Primary Menu</a></li></ul></nav>';
          }
        }
      ]);
    ?>
    <div class="drawer-search">
      <?php get_search_form(); ?>
    </div>
    
    <!-- Categories & Tags -->
    <div class="drawer-taxonomy">
      <div class="taxonomy-section">
        <h4 class="taxonomy-title">カテゴリー</h4>
        <div class="taxonomy-cloud">
          <?php
            $categories = get_categories(['hide_empty' => true]);
            foreach ($categories as $category) {
              printf(
                '<a href="%s" class="taxonomy-tag category-tag">%s <span class="count">(%d)</span></a>',
                esc_url(get_category_link($category)),
                esc_html($category->name),
                $category->count
              );
            }
          ?>
        </div>
      </div>
      
      <div class="taxonomy-section">
        <h4 class="taxonomy-title">タグ</h4>
        <div class="taxonomy-cloud">
          <?php
            $tags = get_tags(['hide_empty' => true, 'number' => 20]);
            foreach ($tags as $tag) {
              printf(
                '<a href="%s" class="taxonomy-tag tag-tag">#%s <span class="count">(%d)</span></a>',
                esc_url(get_tag_link($tag)),
                esc_html($tag->name),
                $tag->count
              );
            }
          ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Breadcrumb Navigation (only on non-front pages) -->
<?php if ( ! is_front_page() ) : ?>
  <div class="breadcrumb-section">
    <div class="container uk-container">
      <?php if ( function_exists('yoast_breadcrumb') ) {
        yoast_breadcrumb( '<nav class="breadcrumb-nav" aria-label="Breadcrumb">','</nav>' );
      } elseif ( function_exists('panolabo_breadcrumb') ) {
        echo '<nav class="breadcrumb-nav" aria-label="Breadcrumb">';
        panolabo_breadcrumb();
        echo '</nav>';
      } ?>
    </div>
  </div>
<?php endif; ?>

<main id="main-content" role="main"><?php
