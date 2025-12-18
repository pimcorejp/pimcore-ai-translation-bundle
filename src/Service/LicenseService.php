<?php

namespace pimcorejp\TranslationBundle\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpClient\HttpClient;

class LicenseService
{
    private CacheInterface $cache;
    private string $productId;

    public function __construct(CacheInterface $cache, ?string $productId = '')
    {
        $this->cache = $cache;
        $this->productId = $productId;
    }

    /**
     * License情報を取得
     */
    public function getLicenseInfo(): array
    {
        return $this->cache->get('translation_bundle_license', function (ItemInterface $item) {
            $item->expiresAfter(3600); // 1時間キャッシュ

            // デフォルトはFree Plan
            return [
                'tier' => 'free',
                'valid' => true,
                'expires_at' => null,
                'features' => [
                    'gemini_translation' => true,
                    'deepl_translation' => true,
                    'html_preservation' => true,
                    'unlimited_gemini' => false,
                ]
            ];
        });
    }

    /**
     * Licenseキーを検証
     */
    public function validateLicense(string $licenseKey): array
    {
        try {
            // PIMCORE Storeと通信してライセンスを検証
            // 実装例: API通信でライセンスを検証
            
            // デモ実装（実際にはPIMCORE Store APIを使用）
            if (empty($this->productId)) {
                return [
                    'valid' => false,
                    'message' => 'Product ID is not configured'
                ];
            }

            // ここで実際のライセンス検証を行う
            // $client = HttpClient::create();
            // $response = $client->request('POST', 'https://store.pimcore.com/api/validate', [
            //     'json' => [
            //         'product_id' => $this->productId,
            //         'license_key' => $licenseKey,
            //     ]
            // ]);

            // デモ用の簡易実装
            if (str_starts_with($licenseKey, 'PRO-')) {
                $licenseInfo = [
                    'tier' => 'pro',
                    'valid' => true,
                    'expires_at' => date('Y-m-d', strtotime('+1 year')),
                    'features' => [
                        'gemini_translation' => true,
                        'deepl_translation' => true,
                        'html_preservation' => true,
                        'unlimited_gemini' => true,
                    ]
                ];

                // キャッシュに保存
                $this->cache->delete('translation_bundle_license');
                $this->cache->get('translation_bundle_license', function (ItemInterface $item) use ($licenseInfo) {
                    $item->expiresAfter(86400); // 24時間
                    return $licenseInfo;
                });

                return $licenseInfo;
            }

            return [
                'valid' => false,
                'message' => 'Invalid license key format'
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * キャッシュをクリア
     */
    public function clearCache(): void
    {
        $this->cache->delete('translation_bundle_license');
    }

    /**
     * Pro Planかどうかをチェック
     */
    public function isProPlan(): bool
    {
        $licenseInfo = $this->getLicenseInfo();
        return ($licenseInfo['tier'] ?? 'free') === 'pro';
    }

    /**
     * 機能が利用可能かチェック
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        $licenseInfo = $this->getLicenseInfo();
        return $licenseInfo['features'][$featureName] ?? false;
    }
}
