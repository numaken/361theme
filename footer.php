  </main>
  <footer class="site-footer" id="site-footer" role="contentinfo">
    <div class="footer-main uk-container">
      <div class="uk-grid-small uk-child-width-1-3@s" uk-grid>
        <section class="footer-col">
          <h2 class="footer-title">About</h2>
          <p class="footer-desc"><?php echo esc_html( get_bloginfo('description') ); ?></p>
          <ul class="footer-sns">
            <li><a href="#" aria-label="X / Twitter">X</a></li>
            <li><a href="#" aria-label="Instagram">Instagram</a></li>
            <li><a href="#" aria-label="Facebook">Facebook</a></li>
          </ul>
        </section>
        <section class="footer-col">
          <h2 class="footer-title">Categories</h2>
          <ul class="footer-cats">
            <?php wp_list_categories([ 'title_li' => '', 'depth' => 1, 'number' => 8 ]); ?>
          </ul>
        </section>
        <section class="footer-col">
          <h2 class="footer-title">Links</h2>
          <?php
            if ( has_nav_menu('footer') ) {
              wp_nav_menu([
                'theme_location' => 'footer',
                'container'      => false,
                'menu_class'     => 'menu menu--footer'
              ]);
            } elseif ( is_active_sidebar('footer-widget') ) {
              dynamic_sidebar('footer-widget');
            } else {
              echo '<ul class="menu"><li><a href="'.esc_url( admin_url('nav-menus.php') ).'">Set Footer Menu</a></li></ul>';
            }
          ?>
        </section>
      </div>
    </div>

    <button id="back-to-top" class="back-to-top" aria-label="Back to top" hidden>↑</button>

    <div class="footer-legal">
      <div class="uk-container">
        <small>
          &copy; <?php echo esc_html( date('Y') ); ?> <?php bloginfo('name'); ?>. All rights reserved.
          <?php if ( function_exists('the_privacy_policy_link') ) { the_privacy_policy_link(' | '); } ?>
        </small>
      </div>
    </div>
  </footer>
  <?php wp_footer(); ?>
  </body>
  </html>
