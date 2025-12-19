<?php

namespace pimcorejp\TranslationBundle\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiTranslationService
{
    private HttpClientInterface $httpClient;
    private ?string $apiKey;
    /* private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent';*/
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemma-3-4b:generateContent';
    private ?string $translationContext;

    public function __construct(HttpClientInterface $httpClient, ?string $geminiApiKey = null, ?string $translationContext = '')
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $geminiApiKey;
        $this->translationContext = $translationContext;
    }

    /**
     * 単一のテキストを翻訳（TranslationControllerから呼ばれる）
     */
    public function translate(
        string $text,
        string $sourceLang,
        string $targetLang,
        string $formality = 'default',
        bool $preserveHtml = false
    ): string {
        $sourceLangName = $this->getLanguageName($sourceLang);
        $targetLangName = $this->getLanguageName($targetLang);

        $prompt = $this->buildPrompt($text, $sourceLangName, $targetLangName, $preserveHtml);

        // API キーを URL パラメータとして追加
        $url = $this->apiUrl . '?key=' . $this->apiKey;

        $response = $this->httpClient->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ],
        ]);

        $statusCode = $response->getStatusCode();
        
        if ($statusCode !== 200) {
            $content = $response->getContent(false);
            throw new \Exception("Gemini API returned HTTP {$statusCode}: {$content}");
        }

        $data = $response->toArray();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception("Invalid response structure from Gemini API");
        }

        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    private function buildPrompt(string $text, string $sourceLangName, string $targetLangName, bool $preserveHtml): string
    {
        if ($preserveHtml) {
            $prompt = "Translate the following HTML content from {$sourceLangName} to {$targetLangName}.\n\n";
            $prompt .= "IMPORTANT RULES:\n";
            $prompt .= "1. Preserve ALL HTML tags exactly as they are\n";
            $prompt .= "2. Preserve ALL HTML attributes (class, id, style, etc.)\n";
            $prompt .= "3. Only translate the text content between tags\n";
            $prompt .= "4. Do not add any explanations or comments\n";
            $prompt .= "5. Return ONLY the translated HTML\n\n";
            
            if ($this->translationContext) {
                $prompt .= "Context: " . $this->translationContext . "\n\n";
            }
            
            $prompt .= "HTML to translate:\n{$text}";
        } else {
            $prompt = "Translate the following text from {$sourceLangName} to {$targetLangName}.\n";
            $prompt .= "Return only the translated text without any explanations.\n\n";
            
            if ($this->translationContext) {
                $prompt .= "Context: " . $this->translationContext . "\n\n";
            }
            
            $prompt .= "Text to translate:\n{$text}";
        }

        return $prompt;
    }

    private function getLanguageName(string $code): string
    {
        $languages = [
            'ja' => 'Japanese',
            'en' => 'English',
            'de' => 'German',
            'fr' => 'French',
            'es' => 'Spanish',
            'it' => 'Italian',
            'zh' => 'Chinese (Simplified)',
            'zh-hant' => 'Chinese (Traditional)',
            'ko' => 'Korean',
        ];

        return $languages[strtolower($code)] ?? ucfirst($code);
    }
}