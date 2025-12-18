<?php

namespace pimcorejp\TranslationBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeeplTranslationService
{
    private HttpClientInterface $httpClient;
    private ?string $apiKey;
    private string $apiUrl;

    public function __construct(HttpClientInterface $httpClient, ?string $deeplApiKey = null, bool $isFreeApi = false)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $deeplApiKey;
        $this->apiUrl = $isFreeApi 
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';
    }

    /**
     * 単一のテキストを翻訳（TranslationControllerから呼ばれる）
     */
    public function translate(
        string $text,
        string $sourceLang,
        string $targetLang,
        string $formality = 'default'
    ): string {
        // DeepL APIの言語コードに正規化
        $sourceLang = $this->normalizeLanguageCode($sourceLang);
        $targetLang = $this->normalizeLanguageCode($targetLang);

        $params = [
            'auth_key' => $this->apiKey,
            'text' => $text,
            'source_lang' => strtoupper($sourceLang),
            'target_lang' => strtoupper($targetLang),
        ];

        if ($formality !== 'default') {
            $params['formality'] = $formality;
        }

        $response = $this->httpClient->request('POST', $this->apiUrl, [
            'body' => $params,
        ]);

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            $content = $response->getContent(false);
            throw new \Exception("DeepL API returned HTTP {$statusCode}: {$content}");
        }

        $data = $response->toArray();

        if (!isset($data['translations'][0]['text'])) {
            throw new \Exception("Invalid response structure from DeepL API");
        }

        return $data['translations'][0]['text'];
    }

    private function normalizeLanguageCode(string $code): string
    {
        $mapping = [
            'ja' => 'JA',
            'en' => 'EN',
            'de' => 'DE',
            'fr' => 'FR',
            'es' => 'ES',
            'it' => 'IT',
            'zh' => 'ZH',
            'ko' => 'KO',
        ];

        $code = strtolower($code);

        return $mapping[$code] ?? strtoupper($code);
    }
}