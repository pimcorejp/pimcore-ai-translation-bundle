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

## 📋 Requirements

- **PIMCORE:** 12.x or higher
- **PHP:** 8.1 or higher
- **Composer:** 2.x
- **Database:** MySQL 5.7+ / MariaDB 10.3+

### Required API Keys (You must obtain separately)

- **DeepL API Key** - Get from https://www.deepl.com/pro-api
- **Google Gemini API Key** - Get from https://ai.google.dev/

## 🚀 Installation

### Step 1: Install via Composer
```bash
composer require pimcorejp/pimcore-ai-translation-bundle
```

### Step 2: Register the Bundle

**Edit `config/bundles.php`:**
```php
return [
    // ... existing bundles
    \pimcorejp\TranslationBundle\TranslationBundle::class => ['all' => true],
];
```

**Edit `src/Kernel.php`:**
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

**Edit `config/routes.yaml`:**
```yaml
translation_bundle_controllers:
    resource: '../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Controller/'
    type: attribute
```

### Step 3: Create Symlink
```bash
ln -sf ../../vendor/pimcorejp/pimcore-ai-translation-bundle/src/Resources/public public/bundles/translation
```

### Step 4: Install Bundle
```bash
bin/console pimcore:bundle:install TranslationBundle
```

### Step 5: Configure API Keys

Add to your `.env` file:
```env
###> pimcorejp/pimcore-ai-translation-bundle ###
DEEPL_API_KEY=your-deepl-api-key-here
DEEPL_FREE_API=true

GEMINI_API_KEY=your-gemini-api-key-here
###< pimcorejp/pimcore-ai-translation-bundle ###
```

### Step 6: Clear Cache
```bash
bin/console cache:clear
```

## 🎯 Usage

### Settings Configuration

Navigate to: **Settings > Translation Bundle**

1. Select Source Language (English, Japanese, German, etc.)
2. Configure Gemini Additional Prompt (Optional)
3. View Usage Statistics

### Translating Data Objects

1. Open any Data Object with Localized Fields
2. Click **Show Translation Buttons**
3. Navigate to target language tab
4. Click **DeepL Translate** or **Gemini Translate**

## 💰 Pricing & Limits

### Free Tier
- ✅ 10 Gemini translations per month
- ✅ Unlimited DeepL translations (subject to your DeepL API plan)

### Pro Tier (Coming Soon)
- ✅ Unlimited Gemini translations
- ✅ Priority technical support

## 🤝 Support

- **GitHub Issues:** https://github.com/pimcorejp/pimcore-ai-translation-bundle/issues
- **Email:** support@pimcorejp.com

## 📄 License

This software is proprietary. See [LICENSE](LICENSE) file for details.

## ⚠️ Disclaimer

This is an independent third-party bundle and is **NOT** an official PIMCORE product.

The developer is not affiliated with, endorsed by, or sponsored by PIMCORE.
"PIMCORE" is a registered trademark of Pimcore GmbH.

For official PIMCORE products and support, visit https://pimcore.com

---

Developed by Takeshi.H | https://pimcorejp.com
