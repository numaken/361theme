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

  // Insert responsive ads into grid (every 6 items)
  function insertGridAds(){
    var client = (window.plbAds && plbAds.client) || '';
    var slot   = (window.plbAds && plbAds.gridSlot) || '';
    if(!client || !slot || typeof adsbygoogle === 'undefined') return;
    var $list = $('#post-list');
    var $items = $list.children();
    $items.each(function(i,el){
      if((i+1)%6===0){
        var $n = $(el).next();
        if(!$n.hasClass('plb-ad')){
          var html = '<div class="plb-ad plb-ad--grid"><ins class="adsbygoogle" style="display:block;text-align:center;" data-ad-client="'+client+'" data-ad-slot="'+slot+'" data-ad-format="auto" data-full-width-responsive="true"></ins></div>';
          $(html).insertAfter($(el));
          (adsbygoogle=window.adsbygoogle||[]).push({});
        }
      }
    });
  }

  // Distance calc in km
  function distKm(lat1, lng1, lat2, lng2){
    var R=6371, dLat=(lat2-lat1)*Math.PI/180, dLng=(lng2-lng1)*Math.PI/180;
    var a=Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R*2*Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  }

  function annotateAndSortByDistance(pos){
    if(!pos) return;
    var lat = pos.coords.latitude, lng = pos.coords.longitude;
    // annotate
    $('#post-list .post-card').each(function(){
      var $c=$(this), la=parseFloat($c.data('lat')), ln=parseFloat($c.data('lng'));
      if(!isNaN(la) && !isNaN(ln)){
        var d = distKm(lat,lng,la,ln);
        $c.attr('data-distance', d.toFixed(3));
        $c.find('.distance-meta').text(d.toFixed(1)+' km');
      }
    });
    // sort
    var $list = $('#post-list');
    var items = $list.children().get();
    items.sort(function(a,b){
      var da=parseFloat($(a).find('.post-card').attr('data-distance'))||Number.MAX_VALUE;
      var db=parseFloat($(b).find('.post-card').attr('data-distance'))||Number.MAX_VALUE;
      return da - db;
    });
    $.each(items, function(_,el){ $list.append(el); });
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
    var payload = { action: 'load_more_posts', page: page, nonce: (window.panolaboAjax && panolaboAjax.nonce) || '' };
    if((localStorage.getItem('plb_sort')||'new')==='near' && window.__plb_geo){ payload.lat = window.__plb_geo.coords.latitude; payload.lng = window.__plb_geo.coords.longitude; }
    $.ajax({
      url: (window.panolaboAjax && panolaboAjax.ajax_url) || '/wp-admin/admin-ajax.php',
      method: 'POST',
      data: payload
    }).done(function(html){
      removeSkeletons();
      $('#post-list').append(html);
      $btn.data('page', page + 1);
      fadeInNew(); insertGridAds();
      if(window.__plb_geo){ annotateAndSortByDistance(window.__plb_geo); }
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
    var $toggle = $('.plb-sort-toggle');
    var mode = localStorage.getItem('plb_sort') || 'new';

    function updateToggle(){
      $toggle.find('button').removeClass('uk-button-primary');
      $toggle.find('button[data-sort="'+mode+'"]').addClass('uk-button-primary');
      if(mode==='new'){
        $('.distance-meta').text('');
      } else if(window.__plb_geo){
        annotateAndSortByDistance(window.__plb_geo);
      }
    }

    if(navigator.geolocation){
      navigator.geolocation.getCurrentPosition(function(p){
        window.__plb_geo = p; if(mode==='near'){ annotateAndSortByDistance(p); }
      });
    }

    $toggle.on('click','button',function(){
      var sel = $(this).data('sort');
      if(sel===mode) return;
      mode = sel; localStorage.setItem('plb_sort', mode);
      if(mode==='near' && !window.__plb_geo && navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(p){
          window.__plb_geo = p; updateToggle();
        }, function(){ mode='new'; localStorage.setItem('plb_sort','new'); updateToggle(); });
      }
      updateToggle();
    });

    // Button click
    $btn.on('click', function(e){ e.preventDefault(); doLoad($btn); });
    // Jump to near (CTA in hero)
    $(document).on('click','.plb-jump-near',function(e){
      e.preventDefault();
      localStorage.setItem('plb_sort','near');
      mode='near'; updateToggle();
      if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(function(p){
          window.__plb_geo=p; updateToggle();
          document.getElementById('post-list')?.scrollIntoView({behavior:'smooth',block:'start'});
        });
      } else {
        document.getElementById('post-list')?.scrollIntoView({behavior:'smooth',block:'start'});
      }
    });
    // Infinite scroll
    setupInfinite($btn);
    // Initial fade for first paint
    fadeInNew();
    updateToggle();
    // Header shadow
    headerShadow();
  });
})(jQuery);
