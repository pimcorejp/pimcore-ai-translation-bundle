<?php

namespace pimcorejp\TranslationBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Yaml;

class TranslationExtension extends Extension
{
    /**
     * サービス定義を読み込む
     * 
     * @param array $configs 設定配列
     * @param ContainerBuilder $container サービスコンテナ
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        
        $loader->load('services.yaml');
        
        // translation_bundle.yaml を読み込み
        $this->loadBundleConfiguration($container);
    }
    
    /**
     * Bundle設定ファイルを読み込んでパラメータとして登録
     */
    private function loadBundleConfiguration(ContainerBuilder $container): void
    {
        // PIMCORE 12の設定ディレクトリパス
        if (!defined('PIMCORE_CONFIGURATION_DIRECTORY')) {
            // フォールバック: プロジェクトルートから推測
            $projectRoot = dirname(__DIR__, 5);
            $configDir = $projectRoot . '/var/config/pimcore';
        } else {
            $configDir = PIMCORE_CONFIGURATION_DIRECTORY . '/pimcore';
        }
        
        $configFile = $configDir . '/translation_bundle.yaml';
        
        if (file_exists($configFile)) {
            try {
                $bundleConfig = Yaml::parseFile($configFile);
                
                if (isset($bundleConfig['translation_bundle'])) {
                    $config = $bundleConfig['translation_bundle'];
                    
                    // パラメータとして登録
                    $container->setParameter(
                        'translation_bundle.selected_source_language',
                        $config['selected_source_language'] ?? 'en'
                    );
                    
                    $container->setParameter(
                        'translation_bundle.source_languages',
                        $config['source_languages'] ?? []
                    );
                    
                    $container->setParameter(
                        'translation_bundle.gemini.additional_prompt',
                        $config['gemini']['additional_prompt'] ?? ''
                    );
                }
            } catch (\Exception $e) {
                // 設定ファイル読み込み失敗時はデフォルト値を使用
                $this->setDefaultParameters($container);
            }
        } else {
            // 設定ファイルが存在しない場合はデフォルト値を使用
            $this->setDefaultParameters($container);
        }
    }
    
    /**
     * デフォルトパラメータを設定
     */
    private function setDefaultParameters(ContainerBuilder $container): void
    {
        $container->setParameter('translation_bundle.selected_source_language', 'en');
        $container->setParameter('translation_bundle.source_languages', []);
        $container->setParameter('translation_bundle.gemini.additional_prompt', '');
    }
}