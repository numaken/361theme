<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo( 'charset' ); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google-adsense-account" content="ca-pub-8539502502589814">

  <?php wp_head(); ?>

  <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"
          data-ad-client="ca-pub-8539502502589814"
          crossorigin="anonymous"></script>


</head>
<body <?php body_class(); ?>>
<?php if ( function_exists( 'wp_body_open' ) ) { wp_body_open(); } ?>
<a href="#main-content" class="uk-skip-link uk-position-fixed uk-position-top-left uk-padding-small">
  <?php esc_html_e( 'コンテンツへスキップ', 'panolabo' ); ?>
</a>

<header class="site-header uk-background-primary uk-light uk-padding-small">
  <div class="uk-container">
    <?php
      if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
        the_custom_logo();
      } else {
    ?>
      <h1 class="uk-heading-small">
        <a href="<?php echo esc_url( home_url('/') ); ?>" class="uk-link-reset">
          <?php bloginfo('name'); ?>
        </a>
      </h1>
    <?php } ?>

    <nav class="uk-navbar-container" uk-navbar aria-label="メインメニュー">
      <div class="uk-navbar-left">
        <?php
          wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'uk-navbar-nav',
          ]);
        ?>
      </div>
    </nav>
  </div>
</header>

<main id="main-content" role="main" class="uk-section uk-section-default">
  <?php panolabo_breadcrumb(); ?>
