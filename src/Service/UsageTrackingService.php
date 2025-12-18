<?php

namespace pimcorejp\TranslationBundle\Service;

use Pimcore\Db;

class UsageTrackingService
{
    /**
     * 使用量を記録
     * 
     * @param string $provider 'gemini' or 'deepl'
     * @param string $domain ドメイン名
     * @param string $ipAddress IPアドレス
     */
    public function trackUsage(string $provider, string $domain, string $ipAddress): void
    {
        $db = Db::get();
        
        $yearMonth = date('Y-m');

        // UPSERTクエリ（既存レコードがあれば+1、なければINSERT）
        $db->executeQuery("
            INSERT INTO `bundle_translation_usage` 
                (`year_month`, `provider`, `domain`, `ip_address`, `count`)
            VALUES 
                (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                `count` = `count` + 1,
                `updated_at` = CURRENT_TIMESTAMP
        ", [$yearMonth, $provider, $domain, $ipAddress]);
    }

    /**
     * 今月の使用量を取得（Settings画面用）
     */
    public function getCurrentMonthUsage(): array
    {
        $db = Db::get();
        
        $yearMonth = date('Y-m');
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $geminiUsage = $db->fetchOne("
            SELECT `count` FROM `bundle_translation_usage`
            WHERE `year_month` = ?
              AND `provider` = 'gemini'
              AND `domain` = ?
              AND `ip_address` = ?
        ", [$yearMonth, $domain, $ipAddress]);

        $deeplUsage = $db->fetchOne("
            SELECT `count` FROM `bundle_translation_usage`
            WHERE `year_month` = ?
              AND `provider` = 'deepl'
              AND `domain` = ?
              AND `ip_address` = ?
        ", [$yearMonth, $domain, $ipAddress]);

        return [
            'gemini_used' => (int)($geminiUsage ?: 0),
            'gemini_limit' => 10,  // Free Planの制限
            'deepl_used' => (int)($deeplUsage ?: 0),
            'deepl_limit' => 'unlimited'
        ];
    }

    /**
     * 指定されたドメイン・IPの今月の使用量を取得（制限チェック用）
     * 
     * @param string $domain ドメイン名
     * @param string $ipAddress IPアドレス
     * @param string $provider 'gemini' or 'deepl'
     * @return int 使用回数
     */
    public function getCurrentMonthUsageCount(string $domain, string $ipAddress, string $provider = 'gemini'): int
    {
        $db = Db::get();
        
        $yearMonth = date('Y-m');

        $count = $db->fetchOne("
            SELECT `count` FROM `bundle_translation_usage`
            WHERE `year_month` = ?
              AND `provider` = ?
              AND `domain` = ?
              AND `ip_address` = ?
        ", [$yearMonth, $provider, $domain, $ipAddress]);

        return (int)($count ?: 0);
    }

    /**
     * Gemini無料版が使用可能かチェック
     * 
     * @param string $domain ドメイン名
     * @param string $ipAddress IPアドレス
     * @return bool 使用可能ならtrue
     */
    public function canUseGemini(string $domain, string $ipAddress): bool
    {
        $currentUsage = $this->getCurrentMonthUsageCount($domain, $ipAddress, 'gemini');
        $limit = 10; // 無料版の月間制限
        
        return $currentUsage < $limit;
    }

    /**
     * 使用量制限をチェック
     */
    public function checkLimit(string $provider, bool $isProPlan = false): array
    {
        // Pro Planは無制限
        if ($isProPlan) {
            return [
                'allowed' => true,
                'remaining' => 'unlimited'
            ];
        }

        // DeepLは常に無制限
        if ($provider === 'deepl') {
            return [
                'allowed' => true,
                'remaining' => 'unlimited'
            ];
        }

        // Free PlanのGemini制限チェック
        if ($provider === 'gemini') {
            $usage = $this->getCurrentMonthUsage();
            $limit = 10;
            $used = $usage['gemini_used'];

            return [
                'allowed' => $used < $limit,
                'remaining' => max(0, $limit - $used),
                'used' => $used,
                'limit' => $limit
            ];
        }

        return [
            'allowed' => false,
            'remaining' => 0
        ];
    }

    /**
     * 月別の使用量統計を取得
     */
    public function getMonthlyStats(string $yearMonth = null): array
    {
        $db = Db::get();
        
        if ($yearMonth === null) {
            $yearMonth = date('Y-m');
        }

        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $results = $db->fetchAllAssociative("
            SELECT 
                `provider`,
                SUM(`count`) as total_count
            FROM `bundle_translation_usage`
            WHERE `year_month` = ?
              AND `domain` = ?
              AND `ip_address` = ?
            GROUP BY `provider`
        ", [$yearMonth, $domain, $ipAddress]);

        $stats = [
            'gemini' => 0,
            'deepl' => 0
        ];

        foreach ($results as $row) {
            $stats[$row['provider']] = (int)$row['total_count'];
        }

        return $stats;
    }

    /**
     * 全使用量履歴を取得（管理用）
     */
    public function getAllUsageHistory(int $limit = 12): array
    {
        $db = Db::get();
        
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        return $db->fetchAllAssociative("
            SELECT 
                `year_month`,
                `provider`,
                `count`,
                `updated_at`
            FROM `bundle_translation_usage`
            WHERE `domain` = ?
              AND `ip_address` = ?
            ORDER BY `year_month` DESC, `provider` ASC
            LIMIT ?
        ", [$domain, $ipAddress, $limit]);
    }
}