# DEV_SPEC_AFFILIATES.md

## 目的

361theme（UIkit3）において、広告・アフィリエイト収益最大化のための最小実装を行う。

- A8.net（例：じゃらんnet）を記事末に自動挿入
- ウィジェット3枠（ヒーロー直下／本文中／本文末）でAdSenseや任意広告を配置可能に
- ショートコードで任意位置にアフィリCTAを配置可能に
- LazyLoadでパフォーマンス低下を回避
- GA4イベントでリンククリックを計測（配置別・パートナー別）

## 対象リポジトリ / ブランチ戦略

- **Repo**: 361theme
- **新規ブランチ**: feat/affiliates-and-ads-v1
- **変更ファイル**:
  - functions.php（追記）
  - front-page.php（ウィジェット呼び出し追記）
  - single.php（ウィジェット呼び出し追記・※本文自動挿入はフックで行うため編集不要でも可）
  - README.md（運用メモ追記／任意）

## 実装タスク

### T1) 記事末：じゃらんCTAの自動挿入

functions.php 末尾に追記（既存コードに影響しない独立ブロック）

```php
// === [AFF] 記事末：じゃらんCTA自動挿入（UIkitカード） ===
add_filter('the_content', function($content){
  if (!is_single() || !in_the_loop() || !is_main_query()) return $content;

  // カテゴリ→リンク切替（差し替えやすいマップ）
  $aff_map = [
    'hotel'   => 'https://a8.example/jalan-hotel',   // 【差し替え】A8 じゃらん ホテル系
    'temple'  => 'https://a8.example/jalan-kyoto',   // 【差し替え】
    'gourmet' => 'https://a8.example/jalan-dinner',  // 【差し替え】
    'default' => 'https://a8.example/jalan-generic', // 【差し替え】汎用
  ];

  $link = $aff_map['default'];
  $cats = get_the_terms(get_the_ID(), 'category');
  if ($cats && !is_wp_error($cats)) {
    foreach ($cats as $c) {
      if (isset($aff_map[$c->slug])) { $link = $aff_map[$c->slug]; break; }
    }
  }

  $block = '
  <div class="uk-card uk-card-default uk-card-body uk-margin">
    <div class="uk-grid-small uk-flex-middle" uk-grid>
      <div class="uk-width-expand">
        <h3 class="uk-card-title uk-margin-remove">京都の宿を今すぐ予約</h3>
        <p class="uk-margin-small">観光地に近い宿・お得プランをチェック。</p>
      </div>
      <div>
        <a class="uk-button uk-button-primary"
           href="'.esc_url($link).'"
           target="_blank" rel="nofollow sponsored noopener"
           data-aff="jalan" data-aff-pos="post_bottom">
           じゃらんで宿泊プランを見る
        </a>
      </div>
    </div>
  </div>';

  return $content . $block;
});
```

**必須差し替え**: `$aff_map` の各URLを A8.net の 提携確定リンク に置換。

### T2) アフィリエイトショートコード

任意位置（本文・ウィジェット）に配置できるボタンCTA。

```php
// === [AFF] ショートコード: [aff_jalan url="..." label="..." pos="sidebar"] ===
add_shortcode('aff_jalan', function($atts){
  $a = shortcode_atts([
    'url'   => 'https://a8.example/jalan-generic', // 【差し替え】
    'label' => 'じゃらんで宿を探す',
    'pos'   => 'widget',
  ], $atts);

  return '<div class="uk-card uk-card-default uk-card-body uk-margin">
    <a class="uk-button uk-button-primary uk-width-1-1"
       href="'.esc_url($a['url']).'"
       target="_blank" rel="nofollow sponsored noopener"
       data-aff="jalan" data-aff-pos="'.esc_attr($a['pos']).'">'.
       esc_html($a['label']).'</a>
  </div>';
});
```

**使用例**:
```
[aff_jalan url="https://a8.example/jalan-arashiyama" label="嵐山周辺の宿を探す" pos="article_mid"]
```

### T3) ウィジェット3枠の登録（ヒーロー直下／本文中／本文末）

```php
// === [ADS] ウィジェット枠登録 ===
add_action('widgets_init', function(){
  register_sidebar([
    'name' => 'After Hero (広告/告知)',
    'id' => 'ad_after_hero',
    'before_widget' => '<div class="uk-container uk-margin">',
    'after_widget'  => '</div>',
  ]);
  register_sidebar([
    'name' => 'In Content (広告/告知)',
    'id' => 'ad_in_content',
    'before_widget' => '<div class="uk-margin">',
    'after_widget'  => '</div>',
  ]);
  register_sidebar([
    'name' => 'After Content (広告/告知)',
    'id' => 'ad_after_content',
    'before_widget' => '<div class="uk-container uk-margin">',
    'after_widget'  => '</div>',
  ]);
});
```

**呼び出し（テンプレート側の最小追記）**

front-page.php（ヒーロー直下と思われる位置の直後）：

```php
<?php if (is_active_sidebar('ad_after_hero')): ?>
  <div class="uk-section uk-section-muted">
    <div class="uk-container">
      <?php dynamic_sidebar('ad_after_hero'); ?>
    </div>
  </div>
<?php endif; ?>
```

single.php（本文中ブロックの前後、または本文レンダ後）：

```php
<?php if (is_active_sidebar('ad_in_content')) dynamic_sidebar('ad_in_content'); ?>
<?php if (is_active_sidebar('ad_after_content')) dynamic_sidebar('ad_after_content'); ?>
```

AdSenseは**ウィジェットの「カスタムHTML」**に `<ins class="adsbygoogle">…` を貼る運用にする（コードはテーマに埋め込まない）。

### T4) LazyLoad と クリック計測（GA4）

```php
// === [PERF] LazyLoad + [ANALYTICS] クリック計測 ===
add_action('wp_footer', function(){
  ?>
  <script>
  // Lazy init for AdSense <ins> と iframe
  document.addEventListener('DOMContentLoaded', function(){
    const els = document.querySelectorAll('iframe[loading="lazy"], ins.adsbygoogle');
    if (!('IntersectionObserver' in window)) return;
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(e=>{
        if(e.isIntersecting){
          const el = e.target;
          if (el.tagName === 'INS' && !el.dataset.inited) {
            (adsbygoogle = window.adsbygoogle || []).push({});
            el.dataset.inited = '1';
          }
          io.unobserve(el);
        }
      });
    }, {rootMargin: '200px 0px'});
    els.forEach(el=>io.observe(el));
  });

  // GA4: affiliate_click イベント計測（data属性ベース）
  document.addEventListener('click', function(e){
    const a = e.target.closest('a[data-aff]');
    if(!a || typeof gtag !== 'function') return;
    try {
      gtag('event', 'affiliate_click', {
        partner: a.dataset.aff,
        position: a.dataset.affPos || 'unknown',
        page_path: location.pathname
      });
    } catch(e){}
  });
  </script>
  <?php
});
```

GA4の gtag は既に全体で読み込まれている前提。未導入なら別途 `<script>` で設定。

### T5) （将来拡張用）カテゴリ→パートナー切替の共通関数

```php
// === [AFF] カテゴリ→パートナー/URLの解決関数（共通化） ===
function plb_get_aff_link_for_post($post_id){
  $map = [
    'hotel'   => ['partner'=>'jalan', 'url'=>'https://a8.example/jalan-hotel'],
    'activity'=> ['partner'=>'klook', 'url'=>'https://vc.example/klook-activity'],
    'souvenir'=> ['partner'=>'rakuten', 'url'=>'https://a8.example/rakuten-kyoto'],
    'default' => ['partner'=>'jalan', 'url'=>'https://a8.example/jalan-generic'],
  ];
  $cats = get_the_terms($post_id, 'category');
  if ($cats && !is_wp_error($cats)) {
    foreach ($cats as $c) if(isset($map[$c->slug])) return $map[$c->slug];
  }
  return $map['default'];
}
```

## 受け入れ条件（Acceptance Criteria）

- ✅ 記事詳細を開くと、本文末にUIkitカードのじゃらんCTAが自動で1つ挿入される
- ✅ 外観 > ウィジェットに「After Hero / In Content / After Content」3枠が表示され、カスタムHTMLでAdSenseや任意コードが配置できる
- ✅ front-page.php のヒーロー直下に ad_after_hero の内容が表示される
- ✅ single.php で ad_in_content と ad_after_content が表示される
- ✅ a[data-aff] をクリックすると GA4 affiliate_click イベントが送信される（partner と position が付与される）
- ✅ AdSense `<ins class="adsbygoogle">` がLazyLoadで初回表示時に push({}) される（コンソールエラー無し）

## 動作確認チェックリスト

- [ ] 管理画面 → A8リンクをショートコードで貼って表示確認
- [ ] 記事詳細ページで 本文末CTA が出て、リンク遷移OK
- [ ] GA4 リアルタイムで affiliate_click が入ること（デバッグビュー推奨）
- [ ] front-page.php のヒーロー直下に広告枠が出る（ウィジェットへバナー投入）
- [ ] モバイルでスクロールしないと広告が実行されない＝LazyLoadが効いている
- [ ] Lighthouse でCLSに悪影響がないか（大きすぎるユニット配置を避ける）

## 環境変数/設定の前提

- GA4 は既に gtag('config', 'G-XXXX') を全ページで読込済み
- AdSense は 自動広告OFF、手動ユニットで運用（制御性のため）
- カテゴリスラッグ：hotel / temple / gourmet / activity / souvenir など（存在しない場合は default にフォールバック）

## セキュリティ・ポリシー

- すべてのアフィリリンクに rel="nofollow sponsored noopener" を付与
- HTMLはテーマファイル内で出力、ユーザー入力は使用しない（XSS回避）
- 外部スクリプトの追加は無し（テーマ内の軽量JSのみ）

## ロールアウト手順

1. ブランチ作成 feat/affiliates-and-ads-v1
2. コード反映 → ローカルでWP起動しテスト
3. PR作成（この DEV_SPEC_AFFILIATES.md を含める）
4. 本番へデプロイ
5. 管理画面でウィジェットに AdSenseユニット と A8検索窓/バナー を配置
6. GA4 の イベントレポートでクリック流入を確認

## 将来タスク（別PR）

- カテゴリ別で自動的にパートナー切替（宿泊=じゃらん／体験=Klook／土産=楽天）
- 記事テンプレに「関連記事」→ その下に固定CTA（宿泊検索窓）
- 特集ページ（桜/紅葉/雨の日）に固定アフィリ枠
- クリック→成果の日次集計（簡易ダッシュボード）

## コミット例（Claude Code用）
```
feat: add affiliate CTA injection and ad widget areas
- add Jalan CTA auto-insert to single posts
- register 3 widget areas (after hero, in content, after content)
- add shortcode [aff_jalan] for anywhere CTA
- add lazy init for AdSense and GA4 affiliate_click tracking
- wire widget areas in front-page.php / single.php
- add DEV_SPEC_AFFILIATES.md
```