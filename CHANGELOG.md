# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-12-17

### Added
- Settings screen for source language configuration
- Gemini additional prompt management in Settings UI
- Automatic locale matching for translation (finds best matching locale)
- Simplified translation buttons UI (removed dropdown menus)
- Support for 10 predefined source languages (en, ja, zh, ko, de, fr, es, it, pt, ru)
- Real-time settings updates without server restart

### Changed
- Source language management moved from `.env` to Settings UI
- Gemini additional prompt moved from `.env` to Settings UI
- Translation buttons now use simple click-to-translate design
- Improved user experience with dynamic language detection

### Deprecated
- Environment variables: `TRANSLATION_SOURCE_LANG_01` through `TRANSLATION_SOURCE_LANG_10`
- Environment variable: `GEMINI_TRANSLATION_CONTEXT`
- These will be removed in version 2.0.0

### Fixed
- Locale matching now handles regional variants correctly (e.g., en_US, en_GB)
- Settings screen now properly validates Gemini prompt length (200 char limit)

## [1.0.0] - 2025-12-01

### Added
- Initial release
- DeepL API integration (Free and Pro tier support)
- Google Gemini API integration (gemini-2.0-flash-exp model)
- HTML-preserving WYSIWYG translation
- Support for Input, Textarea, and WYSIWYG field types
- Monthly usage tracking for Gemini free tier (10 translations/month)
- Database table for usage statistics (`bundle_translation_usage`)
- License management framework (inactive, awaiting PIMCORE Store integration)
- Multi-language admin UI (English and Japanese)
- Copy fields functionality (non-translation copy)

### Technical Details
- Compatible with PIMCORE 12.x
- Requires PHP 8.1 or higher
- Uses Symfony DependencyInjection for service management
- ExtJS-based admin interface
- AJAX-powered translation execution

## [Unreleased]


### Under Consideration
- License key validation with PIMCORE Store API
- Support for Image field alt text translation
- Integration with more AI providers (Claude, GPT-4)
- Custom language addition in Settings
- Translation memory/cache system
- CSV export/import for translations
- Terminology management

---

## Version History Summary

| Version | Release Date | Key Features |
|---------|--------------|--------------|
| 1.1.0   | 2025-12-17  | Settings UI, Simplified buttons, Auto locale matching |
| 1.0.0   | 2025-12-01  | Initial release, DeepL + Gemini, HTML preservation |

---

For detailed technical specifications, see [SPECIFICATION.md](docs/SPECIFICATION.md)
