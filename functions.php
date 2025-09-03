<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ========================================
 * セキュリティ設定
 * ========================================
 */

// WordPressバージョン非表示
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

// WordPress REST API制限（管理者以外）
add_filter('rest_authentication_errors', function($result) {
    if (!empty($result)) return $result;
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'REST APIにはログインが必要です。', array('status' => 401));
    }
    return $result;
});

// XML-RPC無効化
add_filter('xmlrpc_enabled', '__return_false');

// ファイル編集機能無効化
if (!defined('DISALLOW_FILE_EDIT')) {
    define('DISALLOW_FILE_EDIT', true);
}

// wp-includesファイルへの直接アクセス防止
add_action('init', function() {
    if (strpos($_SERVER['REQUEST_URI'], '/wp-includes/') !== false) {
        wp_die('Forbidden', 'Forbidden', array('response' => 403));
    }
});

// ログインエラーメッセージを曖昧に
add_filter('login_errors', function() {
    return 'ログイン情報が正しくありません。';
});

// セキュリティヘッダー設定
add_action('send_headers', function() {
    if (!is_admin()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }
});

// wp-json エンドポイント情報削除
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');

// RSD/wlwmanifest リンク削除
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// 絵文字スクリプト無効化
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// WordPress pingback無効化
add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// ユーザー列挙防止
add_action('init', function() {
    if (!is_admin() && isset($_REQUEST['author'])) {
        wp_die('Forbidden', 'Forbidden', array('response' => 403));
    }
});

// wp-config.php保護
add_action('init', function() {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-config.php') !== false) {
        wp_die('Forbidden', 'Forbidden', array('response' => 403));
    }
});

// デバッグログ無効化
if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}
if (!defined('WP_DEBUG_LOG')) {
    define('WP_DEBUG_LOG', false);
}
if (!defined('WP_DEBUG_DISPLAY')) {
    define('WP_DEBUG_DISPLAY', false);
}

// PHPエラー表示無効化
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// Google Analytics 4 設定をカスタマイザーに追加
add_action('customize_register', function($wp_customize) {
    // Analytics セクション追加
    $wp_customize->add_section('analytics_section', array(
        'title' => 'Analytics 設定',
        'priority' => 35,
        'description' => 'Google Analytics 4 の設定を行います。',
    ));
    
    // GA4 Measurement ID設定
    $wp_customize->add_setting('ga4_measurement_id', array(
        'default' => 'G-2CXVKGKNSE',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));
    
    $wp_customize->add_control('ga4_measurement_id', array(
        'label' => 'GA4 Measurement ID',
        'description' => 'Google Analytics 4のMeasurement ID (例: G-XXXXXXXXXX)',
        'section' => 'analytics_section',
        'type' => 'text',
        'input_attrs' => array(
            'placeholder' => 'G-XXXXXXXXXX',
        ),
    ));
});



/**
 * API から記事一覧を取得
 *
 * @return array|false 成功時に連想配列の配列、失敗時は false
 */
function panolabo_fetch_contents_list() {
    $transient_key = 'api_content_list';
    if ( false !== ( $cached = get_transient( $transient_key ) ) ) {
        return $cached;
    }

    $url      = 'https://api.panolabo.com/contents';
    $response = wp_remote_get( $url, [
        'timeout'    => 10,
        'user-agent' => 'panolabo-theme/' . wp_get_theme()->get( 'Version' ),
    ] );

    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        // error_log( 'panolabo_fetch_contents_list: リスト取得失敗 - ' . print_r( $response, true ) );
        return false;
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! is_array( $data ) ) {
        // error_log( 'panolabo_fetch_contents_list: JSON 形式異常' );
        return false;
    }

    // データ整形＆サニタイズ
    $list = [];
    foreach ( $data as $item ) {
        $list[] = [
            'title'   => sanitize_text_field( $item['title']   ?? '' ),
            'content' => wp_kses_post( $item['content'] ?? '' ),
            'thumb'   => esc_url_raw( $item['thumb']   ?? '' ),
            'url'     => esc_url_raw( $item['url']     ?? '' ),
        ];
    }

    set_transient( $transient_key, $list, HOUR_IN_SECONDS );
    return $list;
}


/**
 * API からデータ取得
 */
function panolabo_fetch_api_data( $apicode ) {
    $url = filter_var( $apicode, FILTER_VALIDATE_URL )
        ? $apicode
        : 'https://api.panolabo.com/contents/' . rawurlencode( $apicode );

    $res = wp_remote_get( $url, [ 'timeout'=>10 ] );
    if ( is_wp_error( $res ) ) return [];
    $json = json_decode( wp_remote_retrieve_body( $res ), true );
    return is_array( $json ) ? $json : [];
}

/**
 * 投稿メタにキャッシュ保存（thumb2x → S3 ドメイン付き）
 */
function panolabo_cache_api_data( $post_id ) {
    if ( wp_is_post_revision( $post_id ) || get_post_type( $post_id ) !== 'post' ) {
        return;
    }

    $apicode = get_post_meta( $post_id, 'apicode', true );
    if ( ! $apicode ) {
        return;
    }

    // 既にキャッシュがあればスキップ
    if ( get_post_meta( $post_id, 'cached_thumb', true ) ) {
        return;
    }

    // API 取得
    $data = panolabo_fetch_api_data( $apicode );
    if ( empty( $data['thumb2x'] ) && empty( $data['thumb'] ) ) {
        return;
    }

    // thumb2x 優先、なければ thumb
    $raw = ! empty( $data['thumb2x'] ) ? $data['thumb2x'] : $data['thumb'];

    // S3 ドメイン付与 & サニタイズ
    $normalized = panolabo_normalize_thumbnail_url( $raw );

    update_post_meta( $post_id, 'cached_thumb', esc_url_raw( $normalized ) );
}
add_action( 'save_post', 'panolabo_cache_api_data' );



/**
 * フロントページで表示するコンテンツの ID リストを API から取得
 *
 * @return int[] 成功時に整数IDの配列、失敗時は空配列
 */
// functions.php に追記・修正
//========================
// API Utility
//========================
function panolabo_fetch_url( string $url ) {
    $response = wp_remote_get( $url, [
        'timeout'    => 10,
        'user-agent' => 'panolabo-theme/' . wp_get_theme()->get( 'Version' ),
    ] );
    if ( is_wp_error( $response ) ) return false;
    if ( wp_remote_retrieve_response_code( $response ) !== 200 ) return false;
    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    return is_array( $data ) ? $data : false;
}




//―――― テーマサポート設定 ――――
function panolabo_theme_setup() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'menus' );
    register_nav_menus( [
        'primary' => __( 'メインメニュー', 'panolabo' ),
        'footer'  => __( 'フッターメニュー', 'panolabo' ),
    ] );
}
add_action( 'after_setup_theme', 'panolabo_theme_setup' );

//―――― スタイル＆スクリプト読み込み ――――
function panolabo_enqueue_assets() {
    // 互換性のためWP同梱のjQueryを利用
    if ( ! is_admin() ) {
        wp_enqueue_script( 'jquery' );
    }

    // UIkit3（CSS + JS + Icons）
    wp_enqueue_style(
        'uikit',
        'https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/css/uikit.min.css',
        [],
        '3.19.4'
    );
    wp_enqueue_script(
        'uikit',
        'https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/js/uikit.min.js',
        ['jquery'],
        '3.19.4',
        true
    );
    wp_enqueue_script(
        'uikit-icons',
        'https://cdn.jsdelivr.net/npm/uikit@3.19.4/dist/js/uikit-icons.min.js',
        ['uikit'],
        '3.19.4',
        true
    );

    // テーマの style.css
    wp_enqueue_style(
        'panolabo-style',
        get_stylesheet_uri(),
        ['uikit'],
        wp_get_theme()->get( 'Version' )
    );

    // Enhanced Visual Kyoto Styles
    wp_enqueue_style(
        'visual-kyoto-styles',
        get_template_directory_uri() . '/assets/css/visual-kyoto.css',
        ['panolabo-style'],
        wp_get_theme()->get( 'Version' )
    );

    // Card Components
    wp_enqueue_style(
        'card-components-styles',
        get_template_directory_uri() . '/assets/css/card-components.css',
        ['visual-kyoto-styles'],
        wp_get_theme()->get( 'Version' )
    );

    // Single Post Styles
    if ( is_single() ) {
        wp_enqueue_style(
            'single-post-styles',
            get_template_directory_uri() . '/assets/css/single-post.css',
            ['visual-kyoto-styles'],
            wp_get_theme()->get( 'Version' )
        );
    }

    // MailFormPro スタイル（外部リソースエラーのためコメントアウト）
    // wp_enqueue_style(
    //     'mailform-style',
    //     'https://eyesup.jp/inquiry/mfp.statics/mailformpro.css',
    //     [],
    //     null
    // );

    // テーマ共通JS（Load More等）
    wp_enqueue_script(
        'panolabo-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        [ 'jquery' ],
        wp_get_theme()->get( 'Version' ),
        true
    );
    wp_localize_script( 'panolabo-theme', 'panolaboAjax', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'load_more_nonce' ),
    ] );
    // AdSense publish/slot values for client-side grid insertion
    wp_localize_script( 'panolabo-theme', 'plbAds', [
        'client'      => get_theme_mod('plb_adsense_client', ''),
        'gridSlot'    => get_theme_mod('plb_adsense_grid_slot', ''),
        'fluidKey'    => get_theme_mod('plb_adsense_fluid_layout_key', ''),
        'stickySlot'  => get_theme_mod('plb_adsense_sticky_slot', ''),
        'stickyEnable'=> (bool) get_theme_mod('plb_adsense_sticky_enable', false),
        'show'        => ! ( is_user_logged_in() && current_user_can('edit_posts') ),
    ] );

    // Header/Footer scoped styles (optional external file)
    if ( file_exists( get_stylesheet_directory() . '/assets/css/header-footer.css' ) ) {
        wp_enqueue_style(
            '361-header-footer',
            get_stylesheet_directory_uri() . '/assets/css/header-footer.css',
            array('uikit','panolabo-style'),
            wp_get_theme()->get( 'Version' )
        );
    }
    // Header/Footer interactions
    if ( file_exists( get_stylesheet_directory() . '/assets/js/theme-header-footer.js' ) ) {
        wp_enqueue_script(
            '361-header-footer',
            get_stylesheet_directory_uri() . '/assets/js/theme-header-footer.js',
            array('jquery'),
            wp_get_theme()->get( 'Version' ),
            true
        );
    }
}
add_action( 'wp_enqueue_scripts', 'panolabo_enqueue_assets' );

//―――― ウィジェットエリア登録 ――――
function panolabo_widgets_init() {
    register_sidebar( [
        'name'          => 'サイドバーウィジェット-1',
        'id'            => 'sidebar-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s uk-card uk-card-default uk-card-body">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="uk-card-title">',
        'after_title'   => '</h3>',
    ] );
    register_sidebar( [
        'name'          => 'フッターウィジェット',
        'id'            => 'footer-widget',
        'before_widget' => '<div class="uk-card uk-card-small uk-card-default uk-padding-small %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="uk-card-title">',
        'after_title'   => '</h4>',
    ] );
}
add_action( 'widgets_init', 'panolabo_widgets_init' );


// ─────────────────────────────
// ① OGP＆Twitterカード用メタタグ + SEO統合
// ─────────────────────────────
function panolabo_meta_tags() {
    // If major SEO plugin is active, skip theme-level meta tags to avoid duplication
    if (
        defined('AIOSEO_VERSION') || class_exists('AIOSEO\\Plugin') ||
        defined('WPSEO_VERSION')  || function_exists('wpseo_replace_vars') ||
        defined('SEOPRESS_VERSION')
    ) {
        return;
    }

    if ( is_singular() ) {
        global $post;
        $title = get_the_title($post);
        $desc  = has_excerpt($post) ? get_the_excerpt($post)
               : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
        $url   = get_permalink($post);
        $img   = get_the_post_thumbnail_url($post, 'full');
        
        $canonical_url = $url;
        
    } else {
        $title = get_bloginfo('name');
        $desc  = get_bloginfo('description');
        $url   = home_url();
        $canonical_url = $url;
        $img   = get_theme_mod('custom_logo')
               ? wp_get_attachment_image_url( get_theme_mod('custom_logo'), 'full' )
               : '';
    }
    
    echo "\n";
    // Canonical URL for SEO consolidation
    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    
    echo '<meta property="og:title" content="'   . esc_attr($title) . "\" />\n";
    echo '<meta property="og:description" content="'. esc_attr($desc)  . "\" />\n";
    echo '<meta property="og:url" content="'     . esc_url($canonical_url)     . "\" />\n";
    echo '<meta property="og:site_name" content="'. esc_attr(get_bloginfo('name')) . "\" />\n";
    echo '<meta property="og:type" content="' . (is_singular() ? 'article' : 'website') . "\" />\n";
    
    if ( $img ) {
        echo '<meta property="og:image" content="'. esc_url($img) . "\" />\n";
        echo '<meta property="og:image:alt" content="' . esc_attr($title) . "\" />\n";
    }
    
    echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . "\" />\n";
    echo '<meta name="twitter:description" content="' . esc_attr($desc) . "\" />\n";
    if ( $img ) {
        echo '<meta name="twitter:image" content="' . esc_url($img) . "\" />\n";
    }
}
add_action( 'wp_head', 'panolabo_meta_tags', 5 );

// ─────────────────────────────
// ② Lazy Loading 対応（サムネイルIMG出力に loading 属性を付与）
// ─────────────────────────────
function panolabo_add_lazyloading( $html, $post_id, $post_thumbnail_id, $size, $attr ) {
    // loading="lazy" を追加
    if ( strpos( $html, 'loading=' ) === false ) {
        $html = str_replace( '<img', '<img loading="lazy"', $html );
    }
    return $html;
}
add_filter( 'post_thumbnail_html', 'panolabo_add_lazyloading', 10, 5 );

// ─────────────────────────────
// ③ カスタマイザーにロゴ設定を追加
// ─────────────────────────────
function panolabo_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'panolabo_logo_section', [
        'title'    => __( 'サイトロゴ', 'panolabo' ),
        'priority' => 30,
    ]);
    $wp_customize->add_setting( 'panolabo_logo', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control( new WP_Customize_Cropped_Image_Control(
        $wp_customize, 'panolabo_logo', [
            'label'    => __( 'サイトロゴをアップロード', 'panolabo' ),
            'section'  => 'panolabo_logo_section',
            'settings' => 'panolabo_logo',
            'width'    => 300,
            'height'   => 80,
        ]
    ));
}
add_action( 'customize_register', 'panolabo_customize_register' );


// ─────────────────────────────
// ④ ブロックエディタ＆テーマ JSON サポート
// ─────────────────────────────
function panolabo_editor_support() {
    // editor-style.css を有効化
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // ワイド配置・埋め込みレスポンシブ対応
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );

    // HTML5 マークアップ強化
    add_theme_support( 'html5', [
        'search-form', 'comment-form', 'comment-list',
        'gallery', 'caption', 'style', 'script'
    ]);

    // カスタムロゴ（Customizer）
    add_theme_support( 'custom-logo', [
        'height'      => 80,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
}
add_action( 'after_setup_theme', 'panolabo_editor_support', 20 );

// ─────────────────────────────
// ⑤ ブロックスタイル登録（UIkitボタン風）
// ─────────────────────────────
function panolabo_register_block_styles() {
    register_block_style( 'core/button', [
        'name'  => 'ui-button-secondary',
        'label' => __( 'UIkit セカンダリ', 'panolabo' ),
    ] );
}
add_action( 'init', 'panolabo_register_block_styles' );

// ─────────────────────────────
// ⑥ パンくずリスト出力関数
// ─────────────────────────────
function panolabo_breadcrumb() {
    if ( ! is_front_page() ) {
        echo '<nav class="uk-breadcrumb uk-margin-small-bottom" aria-label="breadcrumb"><ul>';
        echo '<li><a href="' . esc_url( home_url() ) . '">' . __( 'ホーム', 'panolabo' ) . '</a></li>';
        if ( is_singular() ) {
            echo '<li>' . get_the_title() . '</li>';
        } elseif ( is_category() ) {
            echo '<li>' . single_cat_title( '', false ) . '</li>';
        } elseif ( is_archive() ) {
            the_archive_title( '<li>', '</li>' );
        }
        echo '</ul></nav>';
    }
}



/**
 * static.panolabo.com ドメインの HTTP/HTTPS URL を
 * S3 の HTTPS ドメインに置換して返します。
 */
function panolabo_normalize_thumbnail_url( string $url ): string {
    if ( empty( $url ) ) {
        return '';
    }
    return esc_url_raw( preg_replace(
        '#^https?://static\.panolabo\.com/#',
        'https://s3-ap-northeast-1.amazonaws.com/static.panolabo.com/',
        $url
    ) );
}




//========================
// 投稿編集画面：🧠 AI加筆ボタン
//========================
add_action( 'admin_enqueue_scripts', 'panolabo_ai_editor_assets' );
function panolabo_ai_editor_assets( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;

    wp_enqueue_script(
        'ai-enhancer-script',
        get_template_directory_uri() . '/assets/js/ai-enhancer.js',
        [ 'jquery' ],
        wp_get_theme()->get( 'Version' ),
        true
    );

    wp_localize_script( 'ai-enhancer-script', 'AIEnhancer', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ai_enhance_nonce' ),
        'button_label' => '🧠 AI加筆'
    ] );
}


// AJAXフック（管理者のみ）
add_action( 'wp_ajax_ai_enhance_content', 'panolabo_ai_enhance_content' );
function panolabo_ai_enhance_content() {
    check_ajax_referer( 'ai_enhance_nonce', 'nonce' );

    $content = sanitize_textarea_field( $_POST['content'] ?? '' );
    if ( empty( $content ) ) wp_send_json_error( 'No content provided' );

    $result = openai_enhance_description( $content );
    if ( empty( $result ) ) {
        wp_send_json_error( 'API error' );
    } else {
        wp_send_json_success( $result );
    }
}


// OpenAI API 呼び出し関数
function panolabo_call_openai_editor( $text ) {
    $api_key = getenv( 'OPENAI_API_KEY' ); // wp-config.php等で定義推奨
    if ( ! $api_key ) return '';

    $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode( [
            'model'    => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'messages' => [
                    [
                        'role'    => 'system',
                        'content' => 'あなたはSEOと読者体験に優れた外国人旅行者向けの記事を得意とする日本人ガイド兼ライターです。',
                    ],
                    [
                        'role'    => 'user',
                        'content' =>
                            "この説明文を、" .
                            "日本初心者の外国人観光客に向けて、文化的背景も自然に織り交ぜながら、" .
                            "自然な日本語と英語のバイリンガル形式で、わかりやすく丁寧に加筆してください。\n\n" .
                            "説明文：\n" . $text,
                    ],
            ],
        ] ),
        'timeout' => 45,
    ]);

    if ( is_wp_error( $response ) ) return '';
    $data = json_decode( wp_remote_retrieve_body( $response ), true );

    return $data['choices'][0]['message']['content'] ?? '';
}


function openai_enhance_article( $text ) {
    $api_key = getenv('OPENAI_API_KEY'); // ← wp-config.php の環境変数から取得
    if ( ! $api_key ) {
        // error_log('[OpenAI] APIキーが取得できませんでした');
        return '';
    }

    $endpoint = 'https://api.openai.com/v1/chat/completions';

    $body = [
        'model'    => 'gpt-3.5-turbo',
        'messages' => [
            [ 'role' => 'system', 'content' => 'あなたはSEOと読者体験に優れた外国人旅行者向けの記事を得意とする日本人ガイド兼ライターです。' ],
            [ 'role' => 'user',   'content' => "以下の説明文を、日本初心者の外国人観光客向けに文化的背景も含めて、わかりやすく、かつ詳しく加筆してください：\n\n" . $text ],
            [ 'content' =>     "この説明文を、" .
    "日本初心者の外国人観光客に向けて、文化的背景も自然に織り交ぜながら、" .
    "自然な日本語と英語のバイリンガル形式で、わかりやすく丁寧に加筆してください。\n\n" .
    "説明文：\n" . $text, ]

        ],
        'temperature' => 0.7,
    ];

    $res = wp_remote_post( $endpoint, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
    ]);

    if ( is_wp_error( $res ) ) return '';
    $json = json_decode( wp_remote_retrieve_body( $res ), true );
    return $json['choices'][0]['message']['content'] ?? '';
}

function panolabo_generate_and_save_post( $api_url ) {
    $data = panolabo_fetch_url( $api_url );
    if ( ! is_array( $data ) ) return false;

    $original_desc = $data['description'] ?? '';
    if ( empty( $original_desc ) ) return false;

    // OpenAI で加筆
    $enhanced_desc = openai_enhance_article( $original_desc );
    if ( empty( $enhanced_desc ) ) return false;

    // バックスラッシュを削除
    $cleaned_content = stripslashes( $enhanced_desc );

    // 投稿タイトル
    $post_title = sanitize_text_field( $data['title'] ?? '無題の記事' );

    // 投稿登録（下書き）
    $post_id = wp_insert_post( [
        'post_title'   => $post_title,
        'post_content' => $cleaned_content,
        'post_status'  => 'draft',
        'post_type'    => 'post',
        'meta_input'   => [
            'apicode' => basename( $api_url ),
        ],
    ] );

    return $post_id;
}



/**
 * OpenAIでdescriptionを加筆する関数
 * ————————————————————————
 * 出力は「【日本語】」と「【English】」の2つのセクションに分けてください。
 */
function openai_enhance_description( $text ) {
    $api_key = getenv( 'OPENAI_API_KEY' );
    if ( ! $api_key ) {
        // error_log('[OpenAI] APIキーが取得できませんでした');
        return '';
    }

    $endpoint = 'https://api.openai.com/v1/chat/completions';
    $body = [
        'model'       => 'gpt-3.5-turbo',
        'temperature' => 0.7,
        'messages'    => [
            [
                'role'    => 'system',
                'content' => implode("\n", [
                    'あなたはSEOと読者体験に優れた外国人旅行者向けの記事を得意とする日本人ガイド兼ライターです。',
                    '以下の説明文を、文化的背景、地元の風習、旅行者が知るべき注意点などを盛り込みながら、',
                    '日本語ブロック（最低800文字）と英語ブロック（同内容を英訳、パラグラフ単位で完結）の',
                    '二つのセクションに分けて、わかりやすく丁寧に加筆してください。',
                ]),
            ],
            [
                'role'    => 'user',
                'content' => "説明文：\n" . $text,
            ],
        ],
    ];

    $response = wp_remote_post( $endpoint, [
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ],
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
    ] );

    if ( is_wp_error( $response ) ) {
        // error_log('[OpenAI] リクエストエラー: ' . $response->get_error_message());
        return '';
    }

    $json = json_decode( wp_remote_retrieve_body( $response ), true );
    return $json['choices'][0]['message']['content'] ?? '';
}

//========================
// 投稿保存時：original_description 取得＆本文加筆（1回限り）
//========================
add_action('save_post', 'panolabo_add_content_from_description_once');
function panolabo_add_content_from_description_once($post_id) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision($post_id) ) return;
    if ( get_post_type($post_id) !== 'post' ) return;

    $post = get_post($post_id);
    if ( ! $post || ! empty($post->post_content) ) return;

    $original = get_post_meta($post_id, 'original_description', true);
    $apicode  = get_post_meta($post_id, 'apicode', true);

    if ( empty($original) && ! empty($apicode) ) {
        $url  = 'https://api.panolabo.com/contents/' . rawurlencode($apicode);
        $data = panolabo_fetch_url($url);
        if ( is_array($data) && ! empty($data['description']) ) {
            $original = wp_strip_all_tags($data['description']);
            update_post_meta($post_id, 'original_description', $original);
        }
    }

    if ( empty($original) ) {
        // error_log("[missing original_description] post_id={$post_id}, apicode={$apicode}");
        return;
    }

    $enhanced = openai_enhance_description($original);
    if ( empty($enhanced) ) return;

    $cleaned = wp_kses_post(htmlspecialchars_decode(wp_unslash($enhanced), ENT_QUOTES));

    remove_action('save_post', 'panolabo_add_content_from_description_once');
    wp_update_post([
        'ID'           => $post_id,
        'post_content' => $cleaned,
    ]);
    add_action('save_post', 'panolabo_add_content_from_description_once');
}


//========================
// 管理画面バッチ：全記事の description を API から取得して保存
//========================
function panolabo_batch_fetch_descriptions() {
    if ( ! is_admin() || ! current_user_can('administrator') || $_GET['do'] !== 'fetch_descriptions' ) {
        return;
    }

    $posts = get_posts([
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ]);

    foreach ( $posts as $post ) {
        $post_id = $post->ID;
        $apicode_raw = get_field( 'apicode', $post_id );

        if ( empty( $apicode_raw ) ) {
            // error_log( "[skipped] apicode is empty for post_id={$post_id}" );
            continue;
        }

        if ( preg_match( '#/contents/(\d+)$#', $apicode_raw, $m ) ) {
            $apicode = $m[1];
        } else {
            $apicode = $apicode_raw;
        }

        $api_url = "https://api.panolabo.com/contents/{$apicode}";
        $data = panolabo_fetch_url( $api_url );

        if ( is_array( $data ) && ! empty( $data['description'] ) ) {
            update_post_meta( $post_id, 'original_description', wp_strip_all_tags( $data['description'] ) );
            // error_log( "[description saved] post_id={$post_id}, apicode={$apicode}" );
        } else {
            // error_log( "[invalid response] post_id={$post_id}, apicode={$apicode}" );
        }
    }
}


/**
 * バッチで全投稿キャッシュ（例: /?do=cache_api を叩く）
 */
add_action( 'init', function(){
    if ( is_admin() && isset($_GET['do']) && $_GET['do']==='cache_api' ) {
        $all = get_posts(['post_type'=>'post','numberposts'=>-1]);
        foreach( $all as $p ) panolabo_cache_api_data($p->ID);
        exit('✅ 全投稿 API キャッシュ完了');
    }
});


//========================
// 管理画面通知：original_description の有無を表示
//========================
add_action('admin_notices', function () {
    if (is_admin() && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        $original = get_post_meta($post_id, 'original_description', true);
        if ($original) {
            echo '<div class="notice notice-success"><p><strong>original_description:</strong><br>' . esc_html($original) . '</p></div>';
        } else {
            echo '<div class="notice notice-warning"><p><strong>original_description は存在しません。</strong></p></div>';
        }
    }
});


//========================
// ACF メタボックス表示設定
//========================
add_filter( 'acf/settings/remove_wp_meta_box', '__return_false' );








// functions.php に追記

/**
 * 管理画面バッチ：全記事の original_description を元に本文を OpenAI で加筆して保存
 */
function panolabo_batch_enhance_descriptions() {
    // 管理者かつ ?do=enhance_descriptions がある場合のみ実行
    if ( ! is_admin() || ! current_user_can( 'administrator' ) || empty( $_GET['do'] ) || $_GET['do'] !== 'enhance_descriptions' ) {
        return;
    }

    // 投稿取得：公開・下書き含め全件
    $all_posts = get_posts( [
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'post_status'    => ['publish','draft'],
        'fields'         => 'ids',
    ] );

    foreach ( $all_posts as $post_id ) {
        // 自動保存・リビジョンはスキップ
        if ( wp_is_post_revision( $post_id ) ) {
            continue;
        }

        // 既に本文が入っていればスキップ（空本文のみ処理したい場合はこの行を外す）
        $post = get_post( $post_id );
        if ( ! empty( $post->post_content ) ) {
            continue;
        }

        // original_description メタ or API から取得
        $original = get_post_meta( $post_id, 'original_description', true );
        if ( ! $original ) {
            $apicode = get_post_meta( $post_id, 'apicode', true );
            if ( ! $apicode ) {
                // error_log( "[skipped] apicode missing for post_id={$post_id}" );
                continue;
            }
            $data = panolabo_fetch_api_data( $apicode );
            if ( empty( $data['description'] ) ) {
                // error_log( "[skipped] description missing for post_id={$post_id}" );
                continue;
            }
            $original = wp_strip_all_tags( $data['description'] );
            update_post_meta( $post_id, 'original_description', $original );
        }

        // OpenAI で加筆
        $enhanced = openai_enhance_description( $original );
        if ( ! $enhanced ) {
            // error_log( "[error] OpenAI enhancement failed for post_id={$post_id}" );
            continue;
        }

        // HTML エスケープ復元・保存
        $cleaned = wp_kses_post( htmlspecialchars_decode( wp_unslash( $enhanced ), ENT_QUOTES ) );

        // 一時的に save_post フックを外して更新
        remove_action( 'save_post', 'panolabo_cache_api_data' );
        wp_update_post( [
            'ID'           => $post_id,
            'post_content' => $cleaned,
        ] );
        add_action( 'save_post', 'panolabo_cache_api_data' );

        // error_log( "[enhanced] post_id={$post_id}" );

        // サーバープレッシャー軽減のため少し待つ（必要に応じて）
        sleep(1);
    }

    // 完了メッセージ
    wp_die( '✅ 全記事の本文加筆が完了しました。', 'Batch Complete' );
}
add_action( 'admin_init', 'panolabo_batch_enhance_descriptions' );



// Ajax ハンドラを登録
add_action( 'wp_ajax_load_more_posts',        'load_more_posts' );
add_action( 'wp_ajax_nopriv_load_more_posts', 'load_more_posts' );
function load_more_posts() {
  // CSRF check (backward compatible: if nonce missing, allow for now)
  if ( isset($_REQUEST['nonce']) ) {
    check_ajax_referer( 'load_more_nonce', 'nonce' );
  }
  $page = isset($_REQUEST['page']) ? intval( $_REQUEST['page'] ) + 1 : 1;
  if ($page < 1) $page = 1;
  $lat  = isset($_REQUEST['lat']) ? floatval($_REQUEST['lat']) : null;
  $lng  = isset($_REQUEST['lng']) ? floatval($_REQUEST['lng']) : null;
  $geo  = ($lat !== null && $lng !== null && is_finite($lat) && is_finite($lng));

  $args = [
    'post_type'      => 'post',
    'posts_per_page' => 25,
    'paged'          => $page,
  ];
  $query = new WP_Query( $args );
  if ( $query->have_posts() ) {
    $posts = $query->posts;
    if ( $geo ) {
      foreach ( $posts as &$p ) {
        $g = panolabo_get_geo_for_post( $p->ID );
        $p->plb_geo = $g;
        if ( $g && isset($g['lat'], $g['lng']) ) {
          $p->plb_dist = panolabo_haversine_km( $lat, $lng, floatval($g['lat']), floatval($g['lng']) );
        } else {
          $p->plb_dist = PHP_FLOAT_MAX;
        }
      }
      unset($p);
      usort( $posts, function($a,$b){ return $a->plb_dist <=> $b->plb_dist; } );
    }
    foreach ( $posts as $p ) {
      setup_postdata( $p );
      if ( isset($p->plb_geo) ) {
        $GLOBALS['plb_geo_current'] = $p->plb_geo;
        $GLOBALS['plb_dist_current'] = $p->plb_dist ?? null;
      } else {
        unset($GLOBALS['plb_geo_current'], $GLOBALS['plb_dist_current']);
      }
      get_template_part( 'template-parts/content-card' );
    }
  }
  wp_reset_postdata();
  wp_die();
}

// Geo helpers
if ( ! function_exists('panolabo_get_geo_for_post') ) {
  function panolabo_get_geo_for_post( int $post_id ) : array {
    $apicode = get_post_meta( $post_id, 'apicode', true );
    if ( ! $apicode ) return [];
    $tkey = 'plb_geo_' . md5( $apicode );
    $cached = get_transient( $tkey );
    if ( is_array($cached) ) return $cached;
    $data = panolabo_fetch_api_data( $apicode );
    $geo  = [];
    if ( is_array($data) && isset($data['lat']) && isset($data['lng']) ) {
      $geo = [ 'lat' => floatval($data['lat']), 'lng' => floatval($data['lng']) ];
    }
    set_transient( $tkey, $geo, 12 * HOUR_IN_SECONDS );
    return $geo;
  }
}

// ========================
// Customizer (AdSense settings)
// ========================
add_action('customize_register', function($wp_customize){
    $wp_customize->add_section('plb_adsense', [
        'title' => 'AdSense 設定', 'priority' => 160
    ]);
    $wp_customize->add_setting('plb_adsense_client', [
        'type'=>'theme_mod', 'sanitize_callback'=>'sanitize_text_field'
    ]);
    $wp_customize->add_control('plb_adsense_client', [
        'section'=>'plb_adsense', 'label'=>'Client ID (ca-pub-....)'
    ]);
    $wp_customize->add_setting('plb_adsense_grid_slot', [
        'type'=>'theme_mod', 'sanitize_callback'=>'sanitize_text_field'
    ]);
    $wp_customize->add_control('plb_adsense_grid_slot', [
        'section'=>'plb_adsense', 'label'=>'Grid Slot ID'
    ]);
    $wp_customize->add_setting('plb_adsense_inarticle_slot', [
        'type'=>'theme_mod', 'sanitize_callback'=>'sanitize_text_field'
    ]);
    $wp_customize->add_control('plb_adsense_inarticle_slot', [
        'section'=>'plb_adsense', 'label'=>'In-article Slot ID'
    ]);
    $wp_customize->add_setting('plb_adsense_fluid_layout_key', [
        'type'=>'theme_mod', 'sanitize_callback'=>'sanitize_text_field'
    ]);
    $wp_customize->add_control('plb_adsense_fluid_layout_key', [
        'section'=>'plb_adsense', 'label'=>'Fluid Layout Key (optional)'
    ]);
    $wp_customize->add_setting('plb_adsense_sticky_enable', [
        'type'=>'theme_mod', 'sanitize_callback'=> function($v){ return (bool)$v; }, 'default'=>false
    ]);
    $wp_customize->add_control('plb_adsense_sticky_enable', [
        'section'=>'plb_adsense', 'label'=>'モバイルのフッタースティッキーを有効化', 'type'=>'checkbox'
    ]);
    $wp_customize->add_setting('plb_adsense_sticky_slot', [
        'type'=>'theme_mod', 'sanitize_callback'=>'sanitize_text_field'
    ]);
    $wp_customize->add_control('plb_adsense_sticky_slot', [
        'section'=>'plb_adsense', 'label'=>'Sticky Slot ID（例: 9587331859）'
    ]);
});

// Helper: render AdSense unit (can be used by shortcodes/templates)
function plb_adsense_unit($slot, $class=''){
    if ( empty($slot) ) return;
    if ( is_user_logged_in() && current_user_can('edit_posts') ) return;
    $client = get_theme_mod('plb_adsense_client', '');
    if ( empty($client) ) return;
    $cls = $class ? ' '.esc_attr($class) : '';
    ?>
    <div class="plb-ad<?=$cls?>" aria-label="ad">
      <ins class="adsbygoogle" style="display:block;text-align:center;"
           data-ad-client="<?=esc_attr($client)?>"
           data-ad-slot="<?=esc_attr($slot)?>"
           data-ad-format="auto" data-full-width-responsive="true"></ins>
    </div>
    <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>
    <?php
}

add_shortcode('ad_inarticle', function(){
    ob_start();
    plb_adsense_unit( get_theme_mod('plb_adsense_inarticle_slot', '') );
    return ob_get_clean();
});

// Inject in-article ads after specific paragraphs (e.g., 2 and 6)
add_filter('the_content', function($content){
    if ( is_admin() || is_feed() || is_search() ) return $content;
    if ( ! is_singular('post') ) return $content;
    if ( is_user_logged_in() && current_user_can('edit_posts') ) return $content;

    $slot = get_theme_mod('plb_adsense_inarticle_slot', '6055296866');
    $client = get_theme_mod('plb_adsense_client', 'ca-pub-8539502502589814');
    if ( empty($slot) || empty($client) ) return $content;

    // Split by closing paragraph tag, keep delimiter
    $parts = preg_split('/(<\\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ( ! $parts || count($parts) < 2 ) return $content;

    $targets = [2,6]; // after these paragraph indexes
    $out = '';
    $pIndex = 0;
    for ( $i=0; $i<count($parts); $i++ ) {
        $out .= $parts[$i];
        if ( preg_match('/<\\/p>/i', $parts[$i]) ) {
            $pIndex++;
            if ( in_array($pIndex, $targets, true) ) {
                // Render inline unit (responsive auto)
                ob_start();
                ?>
                <div class="plb-ad" aria-label="advertisement">
                  <ins class="adsbygoogle" style="display:block" data-ad-format="auto" data-full-width-responsive="true" data-ad-client="<?php echo esc_attr($client); ?>" data-ad-slot="<?php echo esc_attr($slot); ?>"></ins>
                </div>
                <script>(adsbygoogle=window.adsbygoogle||[]).push({});</script>
                <?php
                $out .= ob_get_clean();
            }
        }
    }
    return $out;
}, 20);

if ( ! function_exists('panolabo_haversine_km') ) {
  function panolabo_haversine_km( float $lat1, float $lng1, float $lat2, float $lng2 ) : float {
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2) * sin($dLng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
  }
}

//========================
// [ADS] 広告ウィジェット枠とUIkit統合
//========================

// === [ADS] ウィジェット枠登録（テーマ固有レイアウト） ===
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

// === [PERF] LazyLoad最適化 ===
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
  </script>
  <?php
});

// === [AMP] Affiliate Manager Pro プラグインとの統合 ===
// Affiliate Manager Proプラグインのスタイルを361themeに統合
add_action('wp_enqueue_scripts', function() {
  if (class_exists('AffiliateManagerPro')) {
    wp_add_inline_style('uikit', '
      /* Affiliate Manager Pro + UIkit統合スタイル */
      .amp-affiliate-block { margin: 20px 0; }
      .amp-affiliate-card { 
        @extend .uk-card, .uk-card-default, .uk-card-body;
      }
      .amp-affiliate-button { 
        @extend .uk-button, .uk-button-primary;
      }
      .amp-placement-bottom .amp-affiliate-card {
        @extend .uk-grid-small, .uk-flex-middle;
      }
    ');
  }
});

//========================
// [LEGACY] 旧アフィリエイト機能（Affiliate Manager Proに移行推奨）
//========================
/*
MEMO: 以下の機能はAffiliate Manager Proプラグインに移行済み
- 記事末CTA自動挿入 → プラグインのAI分析付き自動挿入
- ショートコード → プラグインの[amp_link]ショートコード  
- GA4イベント計測 → プラグインの高度分析機能
- カテゴリ別リンク切替 → プラグインのインテリジェント選択

移行後は以下をコメントアウト可能:
*/
