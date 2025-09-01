/**
 * Theme common JS: Load More posts with nonce protection
 */
(function($){
  function showSpinner($btn, on){
    var $label = $btn.find('.btn-label');
    var $spin  = $btn.find('.btn-spinner');
    if(on){ $spin.removeAttr('hidden'); $label.text('読み込み中…'); }
    else { $spin.attr('hidden', 'hidden'); $label.text('もっと見る'); }
  }

  function addSkeletons(count){
    var $list = $('#post-list');
    var html = '';
    for(var i=0;i<count;i++){
      html += '<div class="skeleton-card uk-card uk-card-default uk-card-small">'
           +   '<div class="uk-card-media-top skeleton-box"></div>'
           +   '<div class="uk-card-body">'
           +     '<div class="skeleton-line" style="width:70%"></div>'
           +     '<div class="skeleton-line" style="width:40%"></div>'
           +   '</div>'
           + '</div>';
    }
    $list.append(html);
  }

  function removeSkeletons(){
    $('#post-list .skeleton-card').remove();
  }

  function fadeInNew(){
    $('#post-list > *').not('.shown').addClass('fade-in shown');
    setTimeout(function(){ $('#post-list > .fade-in').removeClass('fade-in'); }, 300);
  }

  function canLoadMore($btn){
    var page = parseInt($btn.data('page'))||0;
    var max  = parseInt($btn.data('max'))||0;
    return page < max;
  }

  function doLoad($btn){
    if(!$btn.length) return;
    if($btn.data('loading')) return;
    if(!canLoadMore($btn)) return;
    $btn.data('loading', true);
    showSpinner($btn, true);
    addSkeletons(6);
    var page = parseInt($btn.data('page'))||0;
    $.ajax({
      url: (window.panolaboAjax && panolaboAjax.ajax_url) || '/wp-admin/admin-ajax.php',
      method: 'POST',
      data: { action: 'load_more_posts', page: page, nonce: (window.panolaboAjax && panolaboAjax.nonce) || '' }
    }).done(function(html){
      removeSkeletons();
      $('#post-list').append(html);
      $btn.data('page', page + 1);
      fadeInNew();
      if(!canLoadMore($btn)) $btn.hide();
    }).always(function(){
      showSpinner($btn, false);
      $btn.data('loading', false);
    });
  }

  function setupInfinite($btn){
    if(!$btn.length) return;
    var ticking = false;
    $(window).on('scroll', function(){
      if(ticking) return; ticking = true;
      window.requestAnimationFrame(function(){
        ticking = false;
        var bottom = $(window).scrollTop() + $(window).height();
        var trigger = $btn.offset().top - 200; // 200px手前でロード
        if(bottom > trigger){ doLoad($btn); }
      });
    });
  }

  function headerShadow(){
    var $h = $('.site-header');
    if(!$h.length) return;
    $(window).on('scroll', function(){
      if(window.scrollY > 10) $h.addClass('is-scrolled'); else $h.removeClass('is-scrolled');
    });
  }

  $(function(){
    var $btn = $('#load-more');
    // Button click
    $btn.on('click', function(e){ e.preventDefault(); doLoad($btn); });
    // Infinite scroll
    setupInfinite($btn);
    // Initial fade for first paint
    fadeInNew();
    // Header shadow
    headerShadow();
  });
})(jQuery);
