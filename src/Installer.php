<?php

namespace pimcorejp\TranslationBundle;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Symfony\Component\Filesystem\Filesystem;
use Pimcore\Db;

class Installer extends AbstractInstaller
{
    private Filesystem $filesystem;
    
    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }
    
    /**
     * インストール処理
     */
    public function install(): void
    {
        echo "Installing Translation Bundle..." . PHP_EOL;

        try {
            // 使用量トラッキング用テーブル作成
            $this->createUsageTrackingTable();
            echo "✓ Database table created" . PHP_EOL;
            
            // デフォルト設定作成
            $this->createDefaultConfig();
            echo "✓ Configuration file created" . PHP_EOL;
            
            echo PHP_EOL;
            echo "Translation Bundle installed successfully!" . PHP_EOL;
            echo PHP_EOL;
            echo "Next steps:" . PHP_EOL;
            echo "1. Add API keys to .env file:" . PHP_EOL;
            echo "   DEEPL_API_KEY=your-deepl-api-key" . PHP_EOL;
            echo "   GEMINI_API_KEY=your-gemini-api-key" . PHP_EOL;
            echo "   DEEPL_FREE_API=true" . PHP_EOL;
            echo PHP_EOL;
            echo "2. Clear cache:" . PHP_EOL;
            echo "   bin/console cache:clear" . PHP_EOL;
            echo PHP_EOL;
            
        } catch (\Exception $e) {
            echo "✗ Installation failed: " . $e->getMessage() . PHP_EOL;
            throw $e;
        }
        
        parent::install();
    }
    
    /**
     * アンインストール処理
     */
    public function uninstall(): void
    {
        echo "Uninstalling Translation Bundle..." . PHP_EOL;
        
        // 使用量トラッキングテーブルを削除
        if ($this->checkUsageTrackingTableExists()) {
            echo PHP_EOL;
            echo "⚠ Warning: This will delete usage tracking data." . PHP_EOL;
            echo "  To backup, run: bin/console dbal:run-sql \"SELECT * FROM bundle_translation_usage\" > backup.csv" . PHP_EOL;
            echo PHP_EOL;
            
            $this->dropUsageTrackingTable();
            echo "✓ Database table removed" . PHP_EOL;
        }
        
        echo PHP_EOL;
        echo "Translation Bundle uninstalled successfully!" . PHP_EOL;
        echo "Configuration file was preserved at: var/config/pimcore/translation_bundle.yaml" . PHP_EOL;
        echo PHP_EOL;
        
        parent::uninstall();
    }
    
    /**
     * インストール状態をチェック
     */
    public function isInstalled(): bool
    {
        return $this->checkUsageTrackingTableExists();
    }
    
    /**
     * インストール可能かチェック
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }
    
    /**
     * アンインストール可能かチェック
     */
    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }
    
    /**
     * インストール後にリロードが必要か
     */
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }
    
    /**
     * 使用量トラッキング用テーブルを作成
     */
    private function createUsageTrackingTable(): void
    {
        $db = Db::get();
        
        // テーブルが既に存在する場合はスキップ
        if ($this->checkUsageTrackingTableExists()) {
            return;
        }
        
        $db->executeQuery("
            CREATE TABLE IF NOT EXISTS `bundle_translation_usage` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `year_month` CHAR(7) NOT NULL COMMENT 'YYYY-MM',
                `provider` VARCHAR(20) NOT NULL COMMENT 'gemini or deepl',
                `domain` VARCHAR(255) NOT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `count` INT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `unique_tracking` (`year_month`, `provider`, `domain`, `ip_address`),
                INDEX `idx_month` (`year_month`),
                INDEX `idx_domain` (`domain`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Translation usage tracking for free tier limits'
        ");
    }
    
    /**
     * 使用量トラッキング用テーブルを削除
     */
    private function dropUsageTrackingTable(): void
    {
        $db = Db::get();
        $db->executeQuery("DROP TABLE IF EXISTS `bundle_translation_usage`");
    }
    
    /**
     * 使用量トラッキング用テーブルの存在をチェック
     */
    private function checkUsageTrackingTableExists(): bool
    {
        try {
            $db = Db::get();
            $result = $db->fetchOne("SHOW TABLES LIKE 'bundle_translation_usage'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * デフォルト設定ファイルを作成
     */
    private function createDefaultConfig(): void
    {
        // PIMCORE 12の設定ディレクトリパス
        if (!defined('PIMCORE_CONFIGURATION_DIRECTORY')) {
            // フォールバック: プロジェクトルートから推測
            $projectRoot = dirname(__DIR__, 5); // vendor/pimcorejp/pimcore-translation-bundle/src から5階層上
            $configDir = $projectRoot . '/var/config/pimcore';
        } else {
            $configDir = PIMCORE_CONFIGURATION_DIRECTORY . '/pimcore';
        }
        
        $configFile = $configDir . '/translation_bundle.yaml';
        
        // 設定ファイルが既に存在する場合はスキップ
        if (file_exists($configFile)) {
            return;
        }
        
        // 設定ディレクトリが存在しない場合は作成
        if (!is_dir($configDir)) {
            $this->filesystem->mkdir($configDir, 0755);
        }
        
        // デフォルト設定内容
        $defaultConfig = <<<YAML
# Translation Bundle Configuration
# このファイルはBundle内部で使用されます。通常は編集不要です。

translation_bundle:
    # ライセンス情報（Bundle内部で管理）
    license:
        key: ''
        validated: false
        last_check: null
    
    # API Keys（.envで設定してください）
    # DEEPL_API_KEY=your-deepl-api-key
    # GEMINI_API_KEY=your-gemini-api-key
    # DEEPL_FREE_API=true
    
    # 無料版の月間制限（Gemini）
    limits:
        free_gemini_monthly: 10
    
    # 機能設定
    features:
        show_translation_buttons: true
        auto_detect_source_language: true
    
    # 選択された翻訳元言語
    selected_source_language: 'en'
    
    # 利用可能な翻訳元言語（10言語固定）
    source_languages:
        - code: 'en'
          label: 'English'
        - code: 'ja'
          label: '日本語'
        - code: 'zh'
          label: '中文'
        - code: 'ko'
          label: '한국어'
        - code: 'de'
          label: 'Deutsch'
        - code: 'fr'
          label: 'Français'
        - code: 'es'
          label: 'Español'
        - code: 'it'
          label: 'Italiano'
        - code: 'pt'
          label: 'Português'
        - code: 'ru'
          label: 'Русский'
    
    # Gemini追加プロンプト
    gemini:
        additional_prompt: ''

YAML;
        
        try {
            $this->filesystem->dumpFile($configFile, $defaultConfig);
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "Failed to create configuration file at {$configFile}: " . $e->getMessage()
            );
        }
    }
}