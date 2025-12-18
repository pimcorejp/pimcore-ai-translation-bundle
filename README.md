# PIMCORE AI Translation Bundle

AI-powered automatic translation for PIMCORE Data Objects using Google Gemini and DeepL APIs.

[![License](https://img.shields.io/badge/license-Proprietary-blue.svg)](LICENSE)
[![PIMCORE](https://img.shields.io/badge/PIMCORE-12.x-green.svg)](https://pimcore.com)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)

## ✨ Features

- 🤖 **Google Gemini AI** - Advanced AI-powered translation with context understanding
- 🌐 **DeepL API** - High-quality translation (Free/Pro)
- 📝 **HTML Preservation** - Maintains WYSIWYG field structure during translation
- 🎯 **Multiple Field Types** - Support for Input, Textarea, WYSIWYG fields
- 📊 **Usage Tracking** - Monthly usage monitoring for free tier management
- ⚙️ **Easy Configuration** - Simple Settings UI for language and prompt management
- 🔒 **License Management** - Free tier (10 Gemini/month) and Pro tier (unlimited)

## 📋 Requirements / 必要要件

- **PIMCORE:** 12.x or higher / 12.x以上
- **PHP:** 8.1 or higher / 8.1以上
- **Composer:** 2.x
- **Database / データベース:** MySQL 5.7+ / MariaDB 10.3+

### Required API Keys / 必要なAPIキー (You must obtain separately / 別途取得が必要)

- **DeepL API Key** - Get from / 取得先: https://www.deepl.com/pro-api
- **Google Gemini API Key** - Get from / 取得先: https://ai.google.dev/

## 🚀 Installation / インストール

### Step 1: Install via Composer / Composerでインストール

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
cd /path/to/your/pimcore
docker compose exec php composer require pimcorejp/pimcore-ai-translation-bundle
```

**For host environment / ホスト環境の場合:**
```bash
cd /path/to/your/pimcore
composer require pimcorejp/pimcore-ai-translation-bundle
```

---

### Step 2: Register the Bundle / Bundleを登録

**Edit `config/bundles.php` / `config/bundles.php`を編集:**
```php
return [
    // ... existing bundles
    pimcorejp\TranslationBundle\TranslationBundle::class => ['all' => true],
];
```

**Edit `src/Kernel.php` / `src/Kernel.php`を編集:**
```php
use pimcorejp\TranslationBundle\TranslationBundle;

class Kernel extends PimcoreKernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        // ... existing bundles
        
        if (class_exists(TranslationBundle::class)) {
            $collection->addBundle(new TranslationBundle());
        }
    }
}
```

**Edit `config/routes.yaml` / `config/routes.yaml`を編集:**
```yaml
translation_bundle_controllers:
    resource: '../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Controller/'
    type: attribute
```

---

### Step 3: Install Bundle / Bundleをインストール

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
docker compose exec php bin/console pimcore:bundle:install TranslationBundle
```

**For host environment / ホスト環境の場合:**
```bash
bin/console pimcore:bundle:install TranslationBundle
```

---

### Step 4: Create Symlink / Symlinkを作成

**Important: Run this AFTER Bundle installation. The bundle install command may remove symlinks.**  
**重要: Bundleインストールの後に実行してください。Bundle installコマンドがSymlinkを削除する場合があります。**
```bash
ln -sf ../../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Resources/public public/bundles/translation
```

**Verify symlink / Symlink確認:**
```bash
ls -la public/bundles/translation
```

---

### Step 5: Configure API Keys / APIキーを設定

**Note: API keys are optional at installation time. You can configure them later when ready to use translation features.**  
**注意: APIキーはインストール時には必須ではありません。翻訳機能を使用する準備ができたら設定してください。**

Add to your `.env` file / `.env`ファイルに追加:
```env
###> pimcorejp/pimcore-ai-translation-bundle ###
DEEPL_API_KEY=your-deepl-api-key-here
GEMINI_API_KEY=your-gemini-api-key-here
###< pimcorejp/pimcore-ai-translation-bundle ###
```

---

### Step 6: Clear Cache / キャッシュをクリア

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
docker compose exec php bin/console cache:clear
```

**For host environment / ホスト環境の場合:**
```bash
bin/console cache:clear
```

---

## 🎯 Usage / 使い方

### Settings Configuration / 設定

Navigate to: **Settings > Translation Bundle**いえ、すべての文章で英/日を併記してほしいです。20:49了解です! 👍
全文を英/日併記にした完全版を作成します。

📝 README.md 完全版（全文英/日併記）
markdown# PIMCORE AI Translation Bundle

AI-powered automatic translation for PIMCORE Data Objects using Google Gemini and DeepL APIs.  
Google GeminiとDeepL APIを使用した、PIMCORE Data Objectの自動翻訳Bundle

[![License](https://img.shields.io/badge/license-Proprietary-blue.svg)](LICENSE)
[![PIMCORE](https://img.shields.io/badge/PIMCORE-12.x-green.svg)](https://pimcore.com)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)

## ✨ Features / 機能

- 🤖 **Google Gemini AI** - Advanced AI-powered translation with context understanding / 文脈を理解する高度なAI翻訳
- 🌐 **DeepL API** - High-quality translation (Free/Pro) / 高品質な翻訳（無料版/有料版）
- 📝 **HTML Preservation** - Maintains WYSIWYG field structure during translation / WYSIWYG フィールドのHTML構造を保持
- 🎯 **Multiple Field Types** - Support for Input, Textarea, WYSIWYG fields / Input、Textarea、WYSIWYGフィールドに対応
- 📊 **Usage Tracking** - Monthly usage monitoring for free tier management / 月間使用量トラッキング（無料版管理用）
- ⚙️ **Easy Configuration** - Simple Settings UI for language and prompt management / シンプルな設定画面で言語とプロンプトを管理
- 🔒 **License Management** - Free tier (10 Gemini/month) and Pro tier (unlimited) / ライセンス管理（無料版: Gemini月10回、Pro版: 無制限）

## 📋 Requirements / 必要要件

- **PIMCORE:** 12.x or higher / 12.x以上
- **PHP:** 8.1 or higher / 8.1以上
- **Composer:** 2.x
- **Database / データベース:** MySQL 5.7+ / MariaDB 10.3+

### Required API Keys / 必要なAPIキー

**You must obtain API keys separately from the following providers:**  
**以下のプロバイダーから個別にAPIキーを取得する必要があります:**

- **DeepL API Key** - Get from / 取得先: https://www.deepl.com/pro-api
- **Google Gemini API Key** - Get from / 取得先: https://ai.google.dev/

## 🚀 Installation / インストール

### Prerequisites / 前提条件

- Docker & Docker Compose (recommended) / Docker & Docker Compose（推奨）

---

### Step 1: Install via Composer / Composerでインストール

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
cd /path/to/your/pimcore
docker compose exec php composer require pimcorejp/pimcore-ai-translation-bundle
```

**For host environment / ホスト環境の場合:**
```bash
cd /path/to/your/pimcore
composer require pimcorejp/pimcore-ai-translation-bundle
```

---

### Step 2: Register the Bundle / Bundleを登録

**Edit `config/bundles.php` / `config/bundles.php`を編集:**
```php
return [
    // ... existing bundles / 既存のバンドル
    pimcorejp\TranslationBundle\TranslationBundle::class => ['all' => true],
];
```

**Edit `src/Kernel.php` / `src/Kernel.php`を編集:**
```php
use pimcorejp\TranslationBundle\TranslationBundle;

class Kernel extends PimcoreKernel
{
    public function registerBundlesToCollection(BundleCollection $collection): void
    {
        // ... existing bundles / 既存のバンドル
        
        if (class_exists(TranslationBundle::class)) {
            $collection->addBundle(new TranslationBundle());
        }
    }
}
```

**Edit `config/routes.yaml` / `config/routes.yaml`を編集:**
```yaml
translation_bundle_controllers:
    resource: '../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Controller/'
    type: attribute
```

---

### Step 3: Install Bundle / Bundleをインストール

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
docker compose exec php bin/console pimcore:bundle:install TranslationBundle
```

**For host environment / ホスト環境の場合:**
```bash
bin/console pimcore:bundle:install TranslationBundle
```

---

### Step 4: Create Symlink / Symlinkを作成

**Important: Run this AFTER Bundle installation. The bundle install command may remove symlinks.**  
**重要: Bundleインストールの後に実行してください。Bundle installコマンドがSymlinkを削除する場合があります。**
```bash
ln -sf ../../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Resources/public public/bundles/translation
```

**Verify symlink / Symlink確認:**
```bash
ls -la public/bundles/translation
```

---

### Step 5: Configure API Keys / APIキーを設定

**Note: API keys are optional at installation time. You can configure them later when ready to use translation features.**  
**注意: APIキーはインストール時には必須ではありません。翻訳機能を使用する準備ができたら設定してください。**

Add to your `.env` file / `.env`ファイルに追加:
```env
###> pimcorejp/pimcore-ai-translation-bundle ###
DEEPL_API_KEY=your-deepl-api-key-here
GEMINI_API_KEY=your-gemini-api-key-here
###< pimcorejp/pimcore-ai-translation-bundle ###
```

---

### Step 6: Clear Cache / キャッシュをクリア

**For Docker environment (Recommended) / Docker環境の場合（推奨）:**
```bash
docker compose exec php bin/console cache:clear
```

**For host environment / ホスト環境の場合:**
```bash
bin/console cache:clear
```

---

## 🎯 Usage / 使い方

### Settings Configuration / 設定画面

Navigate to: **Settings > Translation Bundle**  
移動先: **Settings > Translation Bundle**

1. Select Source Language (English, Japanese, German, etc.) / ソース言語を選択（英語、日本語、ドイツ語など）
2. Configure Gemini Additional Prompt (Optional) / Gemini追加プロンプトを設定（オプション）
3. View Usage Statistics / 使用統計を表示

### Translating Data Objects / Data Objectの翻訳

1. Open any Data Object with Localized Fields / Localized Fieldsを持つData Objectを開く
2. Click **Show Translation Buttons** / **Show Translation Buttons**をクリック
3. Navigate to target language tab / ターゲット言語タブに移動
4. Click **DeepL Translate** or **Gemini Translate** / **DeepL Translate**または**Gemini Translate**をクリック

---

## 💰 Pricing & Limits / 料金と制限

### Free Tier / 無料版
- ✅ 10 Gemini translations per month / Gemini翻訳 月10回
- ✅ Unlimited DeepL translations (subject to your DeepL API plan) / DeepL翻訳 無制限（DeepL APIプランに準拠）

### Pro Tier (Coming Soon) / Pro版（近日公開）
- ✅ Unlimited Gemini translations / Gemini翻訳 無制限
- ✅ Priority technical support / 優先技術サポート

---

## 🤝 Support / サポート

- **GitHub Issues:** https://github.com/pimcorejp/pimcore-ai-translation-bundle/issues
- **Email / メール:** support@pimcorejp.com

---

## 📄 License / ライセンス

This software is proprietary. See [LICENSE](LICENSE) file for details.  
このソフトウェアは商用ライセンスです。詳細は[LICENSE](LICENSE)ファイルをご覧ください。

---

## ⚠️ Disclaimer / 免責事項

This is an independent third-party bundle and is **NOT** an official PIMCORE product.  
これは独立したサードパーティBundleであり、PIMCORE公式製品では**ありません**。

The developer is not affiliated with, endorsed by, or sponsored by PIMCORE.  
開発者はPIMCOREと提携、承認、スポンサー関係にありません。

"PIMCORE" is a registered trademark of Pimcore GmbH.  
「PIMCORE」はPimcore GmbHの登録商標です。

For official PIMCORE products and support, visit https://pimcore.com  
公式PIMCOREの製品とサポートについては https://pimcore.com をご覧ください。

---

Developed by Takeshi.H | https://pimcorejp.com