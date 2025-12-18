<?php

namespace pimcorejp\TranslationBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\AdminAbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use pimcorejp\TranslationBundle\Service\DeeplTranslationService;
use pimcorejp\TranslationBundle\Service\GeminiTranslationService;
use pimcorejp\TranslationBundle\Service\UsageTrackingService;
use Psr\Log\LoggerInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Data\Hotspotimage;


#[Route('/admin/translation')]
class TranslationController extends AdminAbstractController
{
    private DeeplTranslationService $deeplTranslationService;
    private GeminiTranslationService $geminiTranslationService;
    private UsageTrackingService $usageTrackingService;
    private LoggerInterface $logger;

    public function __construct(
        DeeplTranslationService $deeplTranslationService,
        GeminiTranslationService $geminiTranslationService,
        UsageTrackingService $usageTrackingService,
        LoggerInterface $logger
    ) {
        $this->deeplTranslationService = $deeplTranslationService;
        $this->geminiTranslationService = $geminiTranslationService;
        $this->usageTrackingService = $usageTrackingService;
        $this->logger = $logger;
    }

    /**
     * ★ V1.1: 選択された翻訳元言語を取得
     */
    #[Route('/source-languages', name: 'translation_bundle_source_languages', methods: ['GET'])]
    public function getSourceLanguagesAction(): JsonResponse
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
            
            $selectedCode = $bundleConfig['selected_source_language'] ?? 'en';
            
            // 選択された言語の情報を取得
            $sourceLanguages = $bundleConfig['source_languages'] ?? [];
            $selectedLanguage = null;
            
            foreach ($sourceLanguages as $lang) {
                if ($lang['code'] === $selectedCode) {
                    $selectedLanguage = $lang;
                    break;
                }
            }
            
            // デフォルトフォールバック
            if (!$selectedLanguage) {
                $selectedLanguage = ['code' => 'en', 'label' => 'English'];
            }
            
            return new JsonResponse([
                'success' => true,
                'selected_language' => $selectedLanguage,
                'source_languages' => $sourceLanguages
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * フィールド翻訳API
     */
    #[Route('/translate-fields', name: 'translation_bundle_translate_fields', methods: ['POST'])]
    public function translateFieldsAction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $fields = $data['fields'] ?? [];
            $fieldTypes = $data['fieldTypes'] ?? [];
            $sourceLang = $data['sourceLang'] ?? 'ja';
            $targetLang = $data['targetLang'] ?? 'en';
            $formality = $data['formality'] ?? 'default';
            $provider = $data['provider'] ?? 'deepl';

            if (empty($fields)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No fields to translate'
                ], 400);
            }

            // ★ Gemini無料版の月間制限チェック
            if ($provider === 'gemini') {
                $domain = $request->getHost();
                $ipAddress = $request->getClientIp();
                
                if (!$this->usageTrackingService->canUseGemini($domain, $ipAddress)) {
                    return new JsonResponse([
                        'success' => false,
                        'error' => '月間の無料翻訳回数（10回）を超過しました。翌月まで待つか、Pro版をご購入ください。'
                    ], 429);
                }
            }

            $this->logger->info('Translation request received', [
                'provider' => $provider,
                'sourceLang' => $sourceLang,
                'targetLang' => $targetLang,
                'fieldCount' => count($fields)
            ]);

            $translations = [];

            foreach ($fields as $key => $text) {
                if (empty($text)) {
                    $translations[$key] = '';
                    continue;
                }

                $fieldType = $fieldTypes[$key] ?? 'text';
                $preserveHtml = ($fieldType === 'wysiwyg' && $provider === 'gemini');

                try {
                    if ($provider === 'gemini') {
                        $translatedText = $this->geminiTranslationService->translate(
                            $text,
                            strtolower($sourceLang),
                            strtolower($targetLang),
                            $formality,
                            $preserveHtml
                        );
                    } else {
                        $translatedText = $this->deeplTranslationService->translate(
                            $text,
                            $sourceLang,
                            $targetLang,
                            $formality
                        );
                    }

                    $translations[$key] = $translatedText;
                } catch (\Exception $e) {
                    $this->logger->error('Translation failed for field', [
                        'key' => $key,
                        'error' => $e->getMessage()
                    ]);

                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Translation failed: ' . $e->getMessage()
                    ], 500);
                }
            }

            // ★ 翻訳成功後、使用量をトラッキング
            try {
                $this->usageTrackingService->trackUsage(
                    $provider,
                    $request->getHost(),
                    $request->getClientIp()
                );
                
                $this->logger->info('Usage tracked', [
                    'provider' => $provider,
                    'domain' => $request->getHost(),
                    'ip' => $request->getClientIp()
                ]);
            } catch (\Exception $e) {
                // トラッキング失敗してもエラーにしない（翻訳自体は成功）
                $this->logger->warning('Failed to track usage', [
                    'error' => $e->getMessage()
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'translations' => $translations,
                'provider' => $provider
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Translation request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 非翻訳フィールド（Image含む）をコピーするAPI
     */
    #[Route('/copy-fields', name: 'translation_bundle_copy_fields', methods: ['POST'])]
    public function copyFieldsAction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON data'
                ], 400);
            }

            $objectId = $data['objectId'] ?? null;
            $sourceLanguage = $data['sourceLanguage'] ?? 'ja';
            $targetLanguage = $data['targetLanguage'] ?? 'en';

            if (!$objectId) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Object ID is required'
                ], 400);
            }

            $this->logger->info('Copy fields request received', [
                'objectId' => $objectId,
                'sourceLanguage' => $sourceLanguage,
                'targetLanguage' => $targetLanguage
            ]);

            // オブジェクトを取得
            $object = Concrete::getById($objectId);

            if (!$object) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Object not found'
                ], 404);
            }

            // クラス定義を取得
            $classDefinition = ClassDefinition::getById($object->getClassId());

            if (!$classDefinition) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Class definition not found'
                ], 404);
            }

            $copiedFields = [];
            $fieldDefinitions = $classDefinition->getFieldDefinitions();

            // すべてのフィールドを処理
            foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
                $fieldType = $fieldDefinition->getFieldtype();

                // Localized Fieldsの場合
                if ($fieldType === 'localizedfields') {
                    $localizedFields = $fieldDefinition->getFieldDefinitions();

                    foreach ($localizedFields as $localizedFieldName => $localizedFieldDefinition) {
                        $localizedFieldType = $localizedFieldDefinition->getFieldtype();

                        // コピー対象のフィールドタイプをチェック
                        if ($this->shouldCopyField($localizedFieldType)) {
                            try {
                                $this->copyLocalizedField(
                                    $object,
                                    $localizedFieldName,
                                    $sourceLanguage,
                                    $targetLanguage,
                                    $localizedFieldType
                                );
                                $copiedFields[] = $localizedFieldName;

                                $this->logger->info('Field copied', [
                                    'field' => $localizedFieldName,
                                    'type' => $localizedFieldType
                                ]);
                            } catch (\Exception $e) {
                                $this->logger->error('Failed to copy field', [
                                    'field' => $localizedFieldName,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }

            // オブジェクトを保存
            $object->save();

            return new JsonResponse([
                'success' => true,
                'copiedFields' => $copiedFields,
                'copiedCount' => count($copiedFields)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Copy fields request failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * フィールドがコピー対象かどうかを判定
     */
    private function shouldCopyField(string $fieldType): bool
    {
        // ★★★ 除外するのは本当に特殊なものだけ ★★★
        $excludedTypes = [
            'fieldcollections', // フィールドコレクションは複雑
            'objectbricks',     // オブジェクトブリックも複雑
        ];

        return !in_array($fieldType, $excludedTypes);
    }

    /**
     * Localizedフィールドをコピー
     */
    private function copyLocalizedField(
        Concrete $object,
        string $fieldName,
        string $sourceLanguage,
        string $targetLanguage,
        string $fieldType
    ): void {
        $getter = 'get' . ucfirst($fieldName);

        if (!method_exists($object, $getter)) {
            throw new \Exception("Getter method {$getter} does not exist");
        }

        $sourceValue = $object->$getter($sourceLanguage);

        $setter = 'set' . ucfirst($fieldName);

        if (!method_exists($object, $setter)) {
            throw new \Exception("Setter method {$setter} does not exist");
        }

        // ★★★ フィールドタイプ別の処理 ★★★
        if ($fieldType === 'image') {
            if ($sourceValue instanceof Image) {
                $object->$setter($sourceValue, $targetLanguage);
            } else {
                $object->$setter(null, $targetLanguage);
            }
        } elseif ($fieldType === 'hotspotimage') {
            if ($sourceValue instanceof Hotspotimage) {
                $object->$setter($sourceValue, $targetLanguage);
            } else {
                $object->$setter(null, $targetLanguage);
            }
        } elseif ($fieldType === 'block') {
            // ★★★ Block フィールドの処理 ★★★
            // Block は DataObject\Data\BlockElement の配列として扱われる
            if ($sourceValue !== null) {
                $object->$setter($sourceValue, $targetLanguage);
            } else {
                $object->$setter(null, $targetLanguage);
            }
        } elseif ($fieldType === 'wysiwyg' || $fieldType === 'textarea' || $fieldType === 'input') {
            // テキスト系フィールドはそのままコピー
            $object->$setter($sourceValue, $targetLanguage);
        } else {
            // その他のフィールドもそのままコピー
            $object->$setter($sourceValue, $targetLanguage);
        }
    }

}