/**
 * Translation Bundle Settings Panel
 */
pimcore.registerNS("pimcore.bundle.translation.startup");
pimcore.registerNS("pimcore.bundle.translation.settings");

// Settings Panel Class
pimcore.bundle.translation.settings = Class.create({
    
    initialize: function() {
        this.panel = null;
    },
    
    activate: function() {
        var tabPanel = Ext.getCmp('pimcore_panel_tabs');
        var tab = tabPanel.getComponent('translation_bundle_settings');
        
        if (!tab) {
            tab = new Ext.Panel({
                id: 'translation_bundle_settings',
                title: t('translation_bundle_settings'),
                iconCls: 'pimcore_icon_translations',
                border: false,
                layout: 'fit',
                closable: true,
                items: [this.getPanel()]
            });
            
            tabPanel.add(tab);
        }
        
        tabPanel.setActiveTab(tab);
        
        // ★ V1.1: 設定を読み込み
        this.loadSettings();
    },
    
    getPanel: function() {
        if (!this.panel) {
            this.panel = new Ext.form.FormPanel({
                bodyStyle: 'padding: 20px;',
                autoScroll: true,
                defaults: {
                    labelWidth: 200,
                    width: 600
                },
                items: [
                    this.getSourceLanguageSection(),  // ★ V1.1: 新規追加
                    this.getGeminiSection(),          // ★ V1.1: 新規追加
                    this.getLicenseInfoSection(),
                    this.getLicenseActivationSection(),
                    this.getUsageStatsSection()
                ],
                buttons: [
                    {
                        text: 'Save Settings',
                        handler: this.saveSettings.bind(this),
                        iconCls: 'pimcore_icon_apply'
                    }
                ]
            });
            
            this.loadData();
        }
        
        return this.panel;
    },
    
    // ★ V1.1: 翻訳元言語選択セクション
    getSourceLanguageSection: function() {
        var languages = [
            { code: 'en', label: 'English' },
            { code: 'ja', label: '日本語' },
            { code: 'zh', label: '中文' },
            { code: 'ko', label: '한국어' },
            { code: 'de', label: 'Deutsch' },
            { code: 'fr', label: 'Français' },
            { code: 'es', label: 'Español' },
            { code: 'it', label: 'Italiano' },
            { code: 'pt', label: 'Português' },
            { code: 'ru', label: 'Русский' }
        ];
        
        var radioItems = [];
        languages.forEach(function(lang) {
            radioItems.push({
                boxLabel: lang.label + ' (' + lang.code + ')',
                name: 'source_language',
                inputValue: lang.code
            });
        });
        
        return {
            xtype: 'fieldset',
            title: 'Source Language',
            itemId: 'sourceLanguageSection',
            items: [
                {
                    xtype: 'displayfield',
                    value: 'Select the default source language for translation:',
                    style: 'margin-bottom: 10px;'
                },
                {
                    xtype: 'radiogroup',
                    itemId: 'sourceLanguageRadio',
                    columns: 1,
                    vertical: true,
                    items: radioItems
                }
            ]
        };
    },
    
    // ★ V1.1: Gemini設定セクション
    getGeminiSection: function() {
        return {
            xtype: 'fieldset',
            title: 'Gemini Settings',
            itemId: 'geminiSection',
            items: [
                {
                    xtype: 'textarea',
                    fieldLabel: 'Additional Prompt (Optional)',
                    itemId: 'geminiPrompt',
                    name: 'gemini_prompt',
                    height: 100,
                    maxLength: 200,
                    value: '',
                    emptyText: 'e.g., Use formal business tone. Preserve technical terms.',
                    anchor: '100%'
                },
                {
                    xtype: 'displayfield',
                    value: 'Max 200 characters',
                    style: 'font-size: 11px; color: #666; margin-top: 5px;'
                }
            ]
        };
    },
    
    getLicenseInfoSection: function() {
        return {
            xtype: 'fieldset',
            title: t('license_information'),
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: t('current_plan'),
                    itemId: 'tierDisplay',
                    value: 'Loading...'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: t('license_expires'),
                    itemId: 'expiresDisplay',
                    value: '-',
                    hidden: true
                }
            ]
        };
    },
    
    getLicenseActivationSection: function() {
        return {
            xtype: 'fieldset',
            title: t('activate_pro_license'),
            items: [
                {
                    xtype: 'textfield',
                    fieldLabel: t('license_key'),
                    name: 'license_key',
                    itemId: 'licenseKeyField',
                    emptyText: 'XXXX-XXXX-XXXX-XXXX',
                    width: 600
                },
                {
                    xtype: 'toolbar',
                    items: [
                        {
                            xtype: 'button',
                            text: t('validate_license'),
                            iconCls: 'pimcore_icon_apply',
                            handler: this.validateLicense.bind(this)
                        },
                        '-',
                        {
                            xtype: 'button',
                            text: t('clear_cache'),
                            iconCls: 'pimcore_icon_clear_cache',
                            handler: this.clearCache.bind(this)
                        }
                    ]
                }
            ]
        };
    },
    
    getUsageStatsSection: function() {
        return {
            xtype: 'fieldset',
            title: t('usage_statistics'),
            itemId: 'usageStats',
            items: [
                {
                    xtype: 'displayfield',
                    fieldLabel: 'Gemini This Month',
                    itemId: 'geminiUsage',
                    value: '0 / 10'
                },
                {
                    xtype: 'displayfield',
                    fieldLabel: 'DeepL This Month', 
                    itemId: 'deeplUsage',
                    value: '0 / unlimited'
                }
            ]
        };
    },
    
    loadData: function() {
        Ext.Ajax.request({
            url: '/admin/translation-bundle/license-info',
            method: 'GET',
            success: function(response) {
                var data = Ext.decode(response.responseText);
                this.updateDisplay(data);
            }.bind(this),
            failure: function() {
                Ext.Msg.alert(t('error'), t('failed_to_load_license_info'));
            }
        });
    },
    
    // ★ V1.1: 設定を読み込み
    loadSettings: function() {
        var me = this;
        
        Ext.Ajax.request({
            url: '/admin/translation-bundle/get-settings',
            method: 'GET',
            success: function(response) {
                var data = Ext.decode(response.responseText);
                
                if (data.success) {
                    // 翻訳元言語を設定
                    var radioGroup = me.panel.down('#sourceLanguageRadio');
                    if (radioGroup && data.settings.selected_source_language) {
                        radioGroup.setValue({
                            source_language: data.settings.selected_source_language
                        });
                    }
                    
                    // Geminiプロンプトを設定
                    var geminiPrompt = me.panel.down('#geminiPrompt');
                    if (geminiPrompt && data.settings.gemini) {
                        geminiPrompt.setValue(data.settings.gemini.additional_prompt || '');
                    }
                }
            },
            failure: function() {
                Ext.MessageBox.alert('Error', 'Failed to load settings');
            }
        });
    },
    
    // ★ V1.1: 設定を保存
    saveSettings: function() {
        var me = this;
        var radioGroup = this.panel.down('#sourceLanguageRadio');
        var geminiPrompt = this.panel.down('#geminiPrompt');
        
        if (!radioGroup || !geminiPrompt) {
            Ext.MessageBox.alert('Error', 'Form components not found');
            return;
        }
        
        var selectedLanguage = radioGroup.getValue().source_language;
        var geminiPromptValue = geminiPrompt.getValue();
        
        if (!selectedLanguage) {
            Ext.MessageBox.alert('Error', 'Please select a source language');
            return;
        }
        
        Ext.Ajax.request({
            url: '/admin/translation-bundle/save-settings',
            method: 'POST',
            jsonData: {
                selected_source_language: selectedLanguage,
                gemini_additional_prompt: geminiPromptValue
            },
            success: function(response) {
                var data = Ext.decode(response.responseText);
                
                if (data.success) {
                    Ext.MessageBox.alert('Success', 'Settings saved successfully!');
                } else {
                    Ext.MessageBox.alert('Error', data.error || 'Failed to save settings');
                }
            },
            failure: function() {
                Ext.MessageBox.alert('Error', 'Failed to save settings');
            }
        });
    },
    
    updateDisplay: function(data) {
        var license = data.license || {};
        var usage = data.usage || {};
        
        var tierText = license.tier === 'pro' 
            ? 'Pro Plan (Unlimited)' 
            : 'Free Plan (10 Gemini/month)';
        this.panel.down('#tierDisplay').setValue(tierText);
        
        if (license.expires_at) {
            this.panel.down('#expiresDisplay').show();
            this.panel.down('#expiresDisplay').setValue(license.expires_at);
        }
        
        var geminiUsage = (usage.gemini_used || 0) + ' / ' + 
            (usage.gemini_limit === 'unlimited' ? 'unlimited' : (usage.gemini_limit || 10));
        this.panel.down('#geminiUsage').setValue(geminiUsage);
        
        var deeplUsage = (usage.deepl_used || 0) + ' / unlimited';
        this.panel.down('#deeplUsage').setValue(deeplUsage);
    },
    
    validateLicense: function() {
        var licenseKey = this.panel.down('#licenseKeyField').getValue();
        
        if (!licenseKey || licenseKey.trim() === '') {
            Ext.Msg.alert(t('error'), 'Please enter license key');
            return;
        }
        
        this.panel.setLoading('Validating license...');
        
        Ext.Ajax.request({
            url: '/admin/translation-bundle/validate-license',
            method: 'POST',
            params: {
                license_key: licenseKey
            },
            success: function(response) {
                this.panel.setLoading(false);
                var data = Ext.decode(response.responseText);
                
                Ext.Msg.alert('Success', data.message, function() {
                    this.loadData();
                }.bind(this));
            }.bind(this),
            failure: function(response) {
                this.panel.setLoading(false);
                var data = Ext.decode(response.responseText);
                Ext.Msg.alert('Error', data.message || 'Validation failed');
            }.bind(this)
        });
    },
    
    clearCache: function() {
        this.panel.setLoading('Clearing cache...');
        
        Ext.Ajax.request({
            url: '/admin/translation-bundle/clear-cache',
            method: 'POST',
            success: function(response) {
                this.panel.setLoading(false);
                var data = Ext.decode(response.responseText);
                
                Ext.Msg.alert('Success', data.message, function() {
                    this.loadData();
                }.bind(this));
            }.bind(this),
            failure: function() {
                this.panel.setLoading(false);
                Ext.Msg.alert('Error', 'Failed to clear cache');
            }
        });
    }
});

// Startup - Menu Integration
pimcore.bundle.translation.startup = Class.create({
    initialize: function () {
        document.addEventListener(pimcore.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = pimcore.globalmanager.get('user');

        // Settingsメニューにサブメニューを追加
        if (menu.settings && user.isAllowed("plugins")) {
            menu.settings.items.push({
                text: "Translation Bundle",
                iconCls: "pimcore_icon_translations",
                priority: 100,
                itemId: 'pimcore_menu_settings_translation',
                handler: this.openSettings,
            });
        }
    },

    openSettings: function() {
        setTimeout(function() {
            // 既存のインスタンスがあれば削除
            var settings = pimcore.globalmanager.get("translation_bundle_settings");
            if (settings) {
                pimcore.globalmanager.remove("translation_bundle_settings");
            }
            
            // 常に新しいインスタンスを作成
            settings = new pimcore.bundle.translation.settings();
            pimcore.globalmanager.add("translation_bundle_settings", settings);
            settings.activate();
        }, 100);
    }

});

// Initialize on load - IMMEDIATELY
(function() {
    const translationBundleStartup = new pimcore.bundle.translation.startup();
    window.translationBundleStartup = translationBundleStartup;
    console.log('Translation Bundle: Startup initialized', translationBundleStartup);
    console.log('Event listener registered for:', pimcore.events.preMenuBuild);
})();