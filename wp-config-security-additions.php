<?php
/**
 * wp-config.php に追加するセキュリティ設定
 * これらの設定をwp-config.phpの「/* 編集が必要なのはここまでです */」の前に追加してください
 */

// =============================================
// セキュリティ強化設定
// =============================================

// ファイル編集無効化
define('DISALLOW_FILE_EDIT', true);

// プラグイン・テーマのインストール無効化（オプション）
// define('DISALLOW_FILE_MODS', true);

// WordPress自動更新設定
define('WP_AUTO_UPDATE_CORE', 'minor'); // マイナーアップデートのみ自動更新

// デバッグ無効化（本番環境）
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);

// リビジョン制限
define('WP_POST_REVISIONS', 3);

// 自動保存間隔延長
define('AUTOSAVE_INTERVAL', 300); // 5分

// ゴミ箱自動削除期間短縮
define('EMPTY_TRASH_DAYS', 7); // 7日

// メモリ制限
ini_set('memory_limit', '256M');

// 実行時間制限
set_time_limit(300);

// Cookieのセキュリティ設定
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);

// セキュリティキー（既存のものがあれば置き換えない）
// https://api.wordpress.org/secret-key/1.1/salt/ で新しいキーを生成することをお勧めします

// データベーステーブル接頭辞をデフォルトの'wp_'から変更することをお勧めします
// 例: $table_prefix = 'xyz_';

// =============================================
// セキュリティヘッダー（追加設定）
// =============================================

if (!is_admin()) {
    // Content Security Policy (CSP) - 必要に応じて調整
    // header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' *.googleapis.com *.google.com; style-src 'self' 'unsafe-inline' *.googleapis.com; img-src 'self' data: *.googleapis.com *.google.com; font-src 'self' *.googleapis.com; connect-src 'self'");
    
    // HTTPS強制（HTTPS環境の場合）
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// =============================================
// 推奨追加設定
// =============================================

// セッション設定
if (!session_id()) {
    session_start([
        'cookie_lifetime' => 3600,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// エラー報告レベル
error_reporting(0);

// PHP設定のセキュリティ強化
ini_set('expose_php', 'Off');
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
ini_set('html_errors', 'Off');

?>