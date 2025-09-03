<?php
/**
 * 361project.com Mixed Content 修正用コード
 * functions.php または wp-config.php に追加
 */

// SSL強制 - すべてのHTTPリソースをHTTPSに変換
add_action('template_redirect', function() {
    if (!is_ssl() && !is_admin()) {
        wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
        exit();
    }
});

// コンテンツ内のHTTP URLを自動的にHTTPSに変換
add_filter('the_content', function($content) {
    return str_replace('http://', 'https://', $content);
});

// ウィジェット・テキスト内のHTTP URLも変換
add_filter('widget_text', function($content) {
    return str_replace('http://', 'https://', $content);
});

// 管理画面でのHTTPS強制
if (!defined('FORCE_SSL_ADMIN')) {
    define('FORCE_SSL_ADMIN', true);
}

// Mixed Content 修正のための追加ヘッダー
add_action('wp_head', function() {
    echo '<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">' . "\n";
});

// Google Maps APIのHTTPS修正
add_filter('script_loader_src', function($src) {
    if (strpos($src, 'maps.google.com') !== false || strpos($src, 'maps.googleapis.com') !== false) {
        $src = str_replace('http://', 'https://', $src);
    }
    return $src;
});

// 外部CSSのHTTPS修正
add_filter('style_loader_src', function($src) {
    return str_replace('http://', 'https://', $src);
});

// データベース内のHTTP URLを一括置換（管理者のみ実行）
function fix_mixed_content_urls() {
    if (!current_user_can('administrator')) return;
    
    global $wpdb;
    
    // 投稿内容のHTTP→HTTPS置換
    $wpdb->query("UPDATE {$wpdb->posts} SET post_content = REPLACE(post_content, 'http://', 'https://')");
    
    // カスタムフィールドのHTTP→HTTPS置換  
    $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = REPLACE(meta_value, 'http://', 'https://')");
    
    // オプションテーブルのHTTP→HTTPS置換
    $wpdb->query("UPDATE {$wpdb->options} SET option_value = REPLACE(option_value, 'http://', 'https://')");
}

// 管理画面でのワンクリック修正機能
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (!current_user_can('administrator')) return;
    
    $wp_admin_bar->add_node([
        'id'    => 'fix-mixed-content',
        'title' => 'Mixed Content修正',
        'href'  => admin_url('admin.php?page=fix-mixed-content')
    ]);
});

add_action('admin_menu', function() {
    add_management_page(
        'Mixed Content修正',
        'Mixed Content修正', 
        'administrator',
        'fix-mixed-content',
        function() {
            if (isset($_POST['fix_mixed_content'])) {
                fix_mixed_content_urls();
                echo '<div class="notice notice-success"><p>Mixed Content URLsを修正しました！</p></div>';
            }
            ?>
            <div class="wrap">
                <h1>Mixed Content修正</h1>
                <form method="post">
                    <p>データベース内のHTTP URLをHTTPSに一括変換します。</p>
                    <p><strong>注意：</strong>必ずバックアップを取ってから実行してください。</p>
                    <?php submit_button('Mixed Content修正を実行', 'primary', 'fix_mixed_content'); ?>
                </form>
            </div>
            <?php
        }
    );
});
?>