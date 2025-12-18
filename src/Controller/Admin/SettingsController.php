<?php

namespace pimcorejp\TranslationBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use pimcorejp\TranslationBundle\Service\LicenseService;
use pimcorejp\TranslationBundle\Service\UsageTrackingService;

#[Route('/admin/translation-bundle')]
class SettingsController extends AdminAbstractController
{
    private LicenseService $licenseService;
    private UsageTrackingService $usageTrackingService;

    public function __construct(
        LicenseService $licenseService,
        UsageTrackingService $usageTrackingService
    ) {
        $this->licenseService = $licenseService;
        $this->usageTrackingService = $usageTrackingService;
    }

    /**
     * License情報を取得
     */
    #[Route('/license-info', name: 'translation_bundle_license_info', methods: ['GET'])]
    public function getLicenseInfo(): JsonResponse
    {
        try {
            $licenseInfo = $this->licenseService->getLicenseInfo();
            $usageStats = $this->usageTrackingService->getCurrentMonthUsage();

            return new JsonResponse([
                'success' => true,
                'license' => $licenseInfo,
                'usage' => $usageStats
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Licenseキーを検証
     */
    #[Route('/validate-license', name: 'translation_bundle_validate_license', methods: ['POST'])]
    public function validateLicense(Request $request): JsonResponse
    {
        try {
            $licenseKey = $request->request->get('license_key');

            if (empty($licenseKey)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'License key is required'
                ], 400);
            }

            $result = $this->licenseService->validateLicense($licenseKey);

            if ($result['valid']) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'License activated successfully!',
                    'tier' => $result['tier'],
                    'expires_at' => $result['expires_at'] ?? null
                ]);
            } else {
                return new JsonResponse([
                    'success' => false,
                    'message' => $result['message'] ?? 'Invalid license key'
                ], 400);
            }
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * キャッシュをクリア
     */
    #[Route('/clear-cache', name: 'translation_bundle_clear_cache', methods: ['POST'])]
    public function clearCache(): JsonResponse
    {
        try {
            $this->licenseService->clearCache();

            return new JsonResponse([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ★ V1.1: 設定を取得
     */
    #[Route('/get-settings', name: 'translation_bundle_get_settings', methods: ['GET'])]
    public function getSettings(): JsonResponse
    {
        try {
            $configFile = PIMCORE_CONFIGURATION_DIRECTORY . '/pimcore/translation_bundle.yaml';
            
            if (!file_exists($configFile)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Configuration file not found'
                ], 404);
            }
            
            $config = Yaml::parseFile($configFile);
            $bundleConfig = $config['translation_bundle'] ?? [];
            
            return new JsonResponse([
                'success' => true,
                'settings' => [
                    'selected_source_language' => $bundleConfig['selected_source_language'] ?? 'en',
                    'source_languages' => $bundleConfig['source_languages'] ?? [],
                    'gemini' => [
                        'additional_prompt' => $bundleConfig['gemini']['additional_prompt'] ?? ''
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ★ V1.1: 設定を保存
     */
    #[Route('/save-settings', name: 'translation_bundle_save_settings', methods: ['POST'])]
    public function saveSettings(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }
            
            $selectedLanguage = $data['selected_source_language'] ?? null;
            $geminiPrompt = $data['gemini_additional_prompt'] ?? '';
            
            // バリデーション
            if (!$selectedLanguage) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'selected_source_language is required'
                ], 400);
            }
            
            $validLanguages = ['en', 'ja', 'zh', 'ko', 'de', 'fr', 'es', 'it', 'pt', 'ru'];
            if (!in_array($selectedLanguage, $validLanguages)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid language code'
                ], 400);
            }
            
            if (mb_strlen($geminiPrompt) > 200) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Gemini prompt must be 200 characters or less'
                ], 400);
            }
            
            // 設定ファイルを読み込み
            $configFile = PIMCORE_CONFIGURATION_DIRECTORY . '/pimcore/translation_bundle.yaml';
            
            if (!file_exists($configFile)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Configuration file not found'
                ], 404);
            }
            
            $config = Yaml::parseFile($configFile);
            
            // 更新
            $config['translation_bundle']['selected_source_language'] = $selectedLanguage;
            $config['translation_bundle']['gemini']['additional_prompt'] = $geminiPrompt;
            
            // 保存
            $yaml = Yaml::dump($config, 4, 2);
            file_put_contents($configFile, $yaml);
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Settings saved successfully'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}