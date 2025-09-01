/**
 * Theme common JS: Load More posts with nonce protection
 */
(function($){
  $(function(){
    var $btn = $('#load-more');
    if (!$btn.length) return;
    var loading = false;
    $btn.on('click', function(){
      if (loading) return;
      var page = parseInt($btn.data('page')) || 0;
      var max  = parseInt($btn.data('max'))  || 0;
      if (page >= max) return;
      loading = true;
      var oldLabel = $btn.text();
      $btn.text('Loading…');
      $.ajax({
        url: (window.panolaboAjax && panolaboAjax.ajax_url) || '/wp-admin/admin-ajax.php',
        method: 'POST',
        data: {
          action: 'load_more_posts',
          page: page,
          nonce: (window.panolaboAjax && panolaboAjax.nonce) || ''
        }
      }).done(function(html){
        $('#post-list').append(html);
        $btn.data('page', page + 1);
        if (page + 1 >= max) $btn.hide();
      }).always(function(){
        loading = false;
        $btn.text(oldLabel);
      });
    });
  });
})(jQuery);

