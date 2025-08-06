</main>
<footer class="uk-section uk-section-muted uk-padding-small">
  <div class="uk-container">
    <div class="uk-grid-small uk-child-width-expand@s" uk-grid>
      <?php if ( is_active_sidebar('footer-widget') ) : ?>
        <?php dynamic_sidebar('footer-widget'); ?>
      <?php endif; ?>
    </div>
    <p class="uk-text-center uk-margin-small-top">
      &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>.
    </p>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
