<?php

namespace pimcorejp\TranslationBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use pimcorejp\TranslationBundle\Installer;

class TranslationBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
    
    public function getVersion(): string
    {
        return '1.0.0';
    }
    
    public function getDescription(): string
    {
        return 'AI-powered translation with DeepL and Gemini. Preserves HTML structure in WYSIWYG fields.';
    }
    
    public function getNiceName(): string
    {
        return 'Translation Bundle';
    }
    
    public function getJsPaths(): array
    {
        return [
            '/bundles/translation/js/settings.js',
            '/bundles/translation/js/translation-button.js',
        ];
    }
    
    public function getCssPaths(): array
    {
        return [
            '/bundles/translation/css/translation-buttons.css',
        ];
    }
    
    public function getInstaller(): ?\Pimcore\Extension\Bundle\Installer\InstallerInterface
    {
        // ★ Container経由で取得（正しい方法）
        return $this->container->get(Installer::class);
    }
}