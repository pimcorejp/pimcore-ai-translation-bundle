pimcore.registerNS("pimcore.bundle.translation.button");

pimcore.bundle.translation.button = Class.create({
    processedTabs: new Set(),
    selectedSourceLanguage: null,

    initialize: function () {
        console.log("Translation Bundle: Button module initialized");
        document.addEventListener(
            pimcore.events.pimcoreReady,
            this.pimcoreReady.bind(this)
        );
    },

    pimcoreReady: function () {
        console.log("Pimcore ready - Translation Button");

        // ★ V1.1: Settings画面から選択された言語を読み込み
        this.loadSelectedLanguage();

        document.addEventListener(pimcore.events.postOpenObject, (e) => {
            console.log("Object opened - adding menu button");
            this.addMenuButton();
        });
    },

    // ★ V1.1: Settingsから選択された翻訳元言語を取得
    loadSelectedLanguage: function () {
        Ext.Ajax.request({
            url: "/admin/translation/source-languages",
            method: "GET",
            success: (response) => {
                const data = Ext.decode(response.responseText);
                if (data.success && data.selected_language) {
                    this.selectedSourceLanguage = data.selected_language.code;
                    console.log(
                        "Selected source language loaded:",
                        this.selectedSourceLanguage
                    );
                } else {
                    this.selectedSourceLanguage = 'en';
                    console.warn('Using default source language: en');
                }
            },
            failure: () => {
                console.warn("Failed to load source language, using default: en");
                this.selectedSourceLanguage = 'en';
            },
        });
    },

    addMenuButton: function () {
        if (Ext.getCmp("translation-show-buttons-btn")) {
            console.log("Menu button already exists");
            return;
        }

        const toolbar = Ext.ComponentQuery.query(
            "toolbar[cls~=pimcore_main_toolbar]"
        )[0];

        if (!toolbar) {
            console.log("Main toolbar not found");
            return;
        }

        toolbar.add({
            id: "translation-show-buttons-btn",
            xtype: "button",
            text: "Show Translation Buttons",
            iconCls: "pimcore_icon_translations",
            handler: () => {
                this.showTranslationButtons();
            },
        });

        console.log("Menu button added to toolbar");
    },

    showTranslationButtons: function () {
        console.log("=== Show translation buttons clicked ===");

        const tabPanel = Ext.ComponentQuery.query(
            "tabpanel[cls~=object_field]"
        )[0];

        if (!tabPanel) {
            Ext.Msg.alert(
                "Error",
                "Object tab panel not found. Please open an object."
            );
            return;
        }

        if (!tabPanel.items || tabPanel.items.length === 0) {
            Ext.Msg.alert("Error", "No tabs found");
            return;
        }

        let addedCount = 0;
        let skippedCount = 0;

        tabPanel.items.each((tab, index) => {
            if (!tab.title) {
                return;
            }

            const tabId = tab.id;
            console.log(`Processing tab: "${tab.title}" (id: ${tabId})`);

            if (tab.down("button[itemId=deeplTranslateBtn]")) {
                console.log(`Tab "${tab.title}": buttons already exist`);
                skippedCount++;
                return;
            }

            let toolbar = tab.getDockedItems('toolbar[dock="top"]')[0];

            if (!toolbar) {
                console.log(`Creating toolbar for tab "${tab.title}"`);
                try {
                    toolbar = tab.addDocked({
                        xtype: "toolbar",
                        dock: "top",
                    })[0];
                } catch (e) {
                    console.error("Error creating toolbar:", e);
                    return;
                }
            }

            try {
                // ★ V1.1: DeepL Translate Button（シンプルボタン）
                toolbar.add({
                    xtype: "button",
                    itemId: "deeplTranslateBtn",
                    text: "DeepL Translate",
                    iconCls: "pimcore_icon_translations",
                    handler: () => {
                        this.translateTab(tab, tabPanel, null, "deepl");
                    },
                });

                // ★ V1.1: Gemini Translate Button（シンプルボタン）
                toolbar.add({
                    xtype: "button",
                    itemId: "geminiTranslateBtn",
                    text: "Gemini Translate",
                    iconCls: "pimcore_icon_translations",
                    style: "margin-left: 5px;",
                    handler: () => {
                        this.translateTab(tab, tabPanel, null, "gemini");
                    },
                });

                // ★ V1.1: Copy Button（シンプルボタン）
                toolbar.add({
                    xtype: "button",
                    itemId: "copyFieldsBtn",
                    text: "Copy Fields",
                    iconCls: "pimcore_icon_copy",
                    style: "margin-left: 5px;",
                    handler: () => {
                        this.copyTab(tab, tabPanel, null);
                    },
                });

                addedCount++;
                console.log(`Buttons added to tab "${tab.title}"`);
            } catch (e) {
                console.error(`Error adding buttons to tab "${tab.title}":`, e);
            }
        });

        console.log(
            `=== Completed: Added ${addedCount} tabs, Skipped ${skippedCount} tabs ===`
        );

        if (addedCount > 0) {
            Ext.Msg.alert(
                "Success",
                `Translation buttons added to ${addedCount} tab(s)!`
            );
        } else if (skippedCount > 0) {
            Ext.Msg.alert("Info", "All tabs already have translation buttons");
        } else {
            Ext.Msg.alert(
                "Info",
                "No tabs available to add translation buttons"
            );
        }
    },

translateTab: function (targetTab, tabPanel, sourceLang, provider) {
    const me = this;
    
    // sourceLangがnullの場合、Settingsから動的に取得
    if (!sourceLang) {
        Ext.Ajax.request({
            url: "/admin/translation/source-languages",
            method: "GET",
            success: (response) => {
                const data = Ext.decode(response.responseText);
                if (data.success && data.selected_language) {
                    sourceLang = data.selected_language.code;
                    console.log('Fetched source language:', sourceLang);
                    // ロケール自動選択を実行
                    me.executeTranslationWithLocaleMatch(targetTab, tabPanel, sourceLang, provider);
                } else {
                    Ext.Msg.alert('Error', 'Please configure source language in Settings > Translation Bundle');
                }
            },
            failure: () => {
                Ext.Msg.alert('Error', 'Failed to load source language settings');
            }
        });
    } else {
        // sourceLangが指定されている場合は直接実行
        this.executeTranslationWithLocaleMatch(targetTab, tabPanel, sourceLang, provider);
    }
},

    // ★ V1.1: ロケール自動選択ロジック
    executeTranslationWithLocaleMatch: function(targetTab, tabPanel, languageCode, provider) {
        console.log("=== Starting translation with locale match ===");
        console.log("Language code:", languageCode);
        console.log("Provider:", provider);

        // オブジェクトの利用可能なロケールを取得
        const availableLocales = pimcore.settings.websiteLanguages || [];
        console.log("Available locales:", availableLocales);
        
        // 該当するロケールを検索（最左一致）
        let sourceLocale = null;
        for (let i = 0; i < availableLocales.length; i++) {
            const locale = availableLocales[i];
            // 完全一致 or プレフィックス一致
            if (locale === languageCode || locale.indexOf(languageCode + '_') === 0) {
                sourceLocale = locale;
                console.log("Matched locale:", sourceLocale);
                break;
            }
        }
        
        if (!sourceLocale) {
            Ext.Msg.alert(
                'Error',
                'Source language "' + languageCode + '" content not found in this object'
            );
            return;
        }
        
        // 翻訳実行（既存のロジックを使用）
        this.executeTranslation(targetTab, tabPanel, sourceLocale, provider);
    },

    // ★ 既存の翻訳ロジック（メソッド名変更）
    executeTranslation: function (targetTab, tabPanel, sourceLang, provider) {
        console.log("=== Starting translation execution ===");
        console.log("Target tab:", targetTab.title);
        console.log("Source locale:", sourceLang);
        console.log("Provider:", provider);

        console.log("=== Searching for Object ID ===");
        let objectId = null;

        // Object ID 取得
        if (tabPanel) {
            let parent = tabPanel.up();
            let depth = 0;

            while (parent && !objectId && depth < 10) {
                if (
                    parent.initialConfig &&
                    parent.initialConfig.object &&
                    parent.initialConfig.object.id
                ) {
                    objectId = parent.initialConfig.object.id;
                    console.log(
                        `✓ Object ID from parent ${depth} initialConfig:`,
                        objectId
                    );
                    break;
                }

                if (
                    parent.data &&
                    parent.data.general &&
                    parent.data.general.o_id
                ) {
                    objectId = parent.data.general.o_id;
                    console.log(`✓ Object ID from parent ${depth}:`, objectId);
                    break;
                }

                parent = parent.up();
                depth++;
            }
        }

        if (!objectId) {
            Ext.Msg.alert(
                "Error",
                "Object ID not found. Please reload the object."
            );
            return;
        }

        console.log("Final Object ID:", objectId);
        this.currentObjectId = objectId;

        // ターゲット言語を取得
        let targetTabIndex = -1;
        tabPanel.items.each((tab, index) => {
            if (tab === targetTab) {
                targetTabIndex = index;
            }
        });

        const targetLang = this.getLanguageFromTab(targetTab, targetTabIndex);
        console.log("Target language:", targetLang);

        if (sourceLang === targetLang) {
            Ext.Msg.alert("Error", "Source and target languages are the same.");
            return;
        }

        // ★ V1.1: ソースタブを探す（ロケール情報から判定）
        let sourceTab = null;
        const sourceLangName = this.getLanguageName(sourceLang);

        tabPanel.items.each((tab, index) => {
            if (!sourceTab) {
                // タブのロケール情報を取得
                const tabLocale = this.getTabLocale(tab);
                console.log(`Tab "${tab.title}" locale:`, tabLocale);
                
                if (tabLocale === sourceLang) {
                    sourceTab = tab;
                    console.log(
                        "Found source tab:",
                        tab.title,
                        "for locale:",
                        sourceLang
                    );
                }
            }
        });

        if (!sourceTab) {
            Ext.Msg.alert("Error", "Source tab for locale '" + sourceLang + "' not found");
            return;
        }

        // ソースタブからフィールドを収集
        console.log("=== Collecting source fields ===");
        const sourceTranslatableFields = [];
        const sourceCopyableFields = [];
        const sourceQuillFields = [];

        const allSourceComponents = sourceTab.query("*");
        console.log("Total source components:", allSourceComponents.length);

        allSourceComponents.forEach((component) => {
            if (this.isTranslatableField(component)) {
                try {
                    const value = component.getValue();
                    if (
                        value !== null &&
                        value !== undefined &&
                        typeof value === "string" &&
                        value.trim() !== ""
                    ) {
                        sourceTranslatableFields.push({
                            component: component,
                            value: value,
                            xtype: component.getXType(),
                            fieldLabel: component.fieldLabel || "N/A",
                        });
                    }
                } catch (e) {
                    console.warn(
                        "Error getting value from translatable component:",
                        e
                    );
                }
            } else if (this.isCopyableField(component)) {
                try {
                    const value = component.getValue();
                    if (value !== null && value !== undefined) {
                        sourceCopyableFields.push({
                            component: component,
                            value: value,
                            xtype: component.getXType(),
                            fieldLabel: component.fieldLabel || "N/A",
                        });
                    }
                } catch (e) {
                    console.warn(
                        "Error getting value from copyable component:",
                        e
                    );
                }
            }
        });

        // Quillエディタ検出
        const sourceTabEl = sourceTab.getEl();
        if (sourceTabEl) {
            const quillEditors = sourceTabEl.dom.querySelectorAll(".ql-editor");
            console.log(
                "Found Quill editors in source tab:",
                quillEditors.length
            );

            quillEditors.forEach((editorEl) => {
                const html = editorEl.innerHTML;
                if (
                    html &&
                    html.trim() !== "" &&
                    html.trim() !== "<p><br></p>"
                ) {
                    sourceQuillFields.push({
                        element: editorEl,
                        value: html,
                    });
                }
            });
        }

        console.log(
            "Source translatable fields:",
            sourceTranslatableFields.length
        );
        console.log("Source copyable fields:", sourceCopyableFields.length);
        console.log("Source Quill fields:", sourceQuillFields.length);

        // ターゲットタブからフィールドを収集
        console.log("=== Collecting target fields ===");
        const targetTranslatableFields = [];
        const targetCopyableFields = [];
        const targetQuillFields = [];

        const allTargetComponents = targetTab.query("*");
        console.log("Total target components:", allTargetComponents.length);

        allTargetComponents.forEach((component) => {
            if (this.isTranslatableField(component)) {
                targetTranslatableFields.push({
                    component: component,
                    xtype: component.getXType(),
                    fieldLabel: component.fieldLabel || "N/A",
                });
            } else if (this.isCopyableField(component)) {
                targetCopyableFields.push({
                    component: component,
                    xtype: component.getXType(),
                    fieldLabel: component.fieldLabel || "N/A",
                });
            }
        });

        // Quillエディタ検出
        const targetTabEl = targetTab.getEl();
        if (targetTabEl) {
            const targetQuillEditors =
                targetTabEl.dom.querySelectorAll(".ql-editor");
            console.log(
                "Found Quill editors in target tab:",
                targetQuillEditors.length
            );

            targetQuillEditors.forEach((editorEl) => {
                targetQuillFields.push({
                    element: editorEl,
                });
            });
        }

        console.log(
            "Target translatable fields:",
            targetTranslatableFields.length
        );
        console.log("Target copyable fields:", targetCopyableFields.length);
        console.log("Target Quill fields:", targetQuillFields.length);

        // フィールドマッピング作成
        console.log("=== Creating field mapping ===");
        const fieldsToTranslate = {};
        const fieldTypes = {};
        const fieldMapping = [];
        const copyMapping = [];
        let totalCharacters = 0;
        let fieldCounter = 0;

        // テキストフィールドをマッピング
        for (
            let i = 0;
            i <
            Math.min(
                sourceTranslatableFields.length,
                targetTranslatableFields.length
            );
            i++
        ) {
            const sourceValue = sourceTranslatableFields[i].value;

            if (sourceValue && sourceValue.trim() !== "") {
                const fieldKey = "field_" + fieldCounter++;
                fieldsToTranslate[fieldKey] = sourceValue;
                fieldTypes[fieldKey] = "text";
                totalCharacters += sourceValue.length;

                fieldMapping.push({
                    key: fieldKey,
                    targetField: targetTranslatableFields[i],
                    sourceValue: sourceValue,
                    type: "extjs",
                });
            }
        }

        // Quillフィールドをマッピング
        for (
            let i = 0;
            i < Math.min(sourceQuillFields.length, targetQuillFields.length);
            i++
        ) {
            let sourceValue = sourceQuillFields[i].value;

            let valueToTranslate;
            if (provider === "gemini") {
                valueToTranslate = sourceValue;
            } else {
                valueToTranslate = this.stripHtmlTags(sourceValue);
            }

            if (
                valueToTranslate &&
                valueToTranslate.trim() !== "" &&
                valueToTranslate.trim() !== "<p><br></p>"
            ) {
                const fieldKey = "field_" + fieldCounter++;
                fieldsToTranslate[fieldKey] = valueToTranslate;
                fieldTypes[fieldKey] = "wysiwyg";
                totalCharacters += valueToTranslate.length;

                fieldMapping.push({
                    key: fieldKey,
                    targetField: targetQuillFields[i],
                    sourceValue: valueToTranslate,
                    type: "quill",
                    preserveHtml: provider === "gemini",
                });
            }
        }

        // コピー可能なフィールドをマッピング
        for (
            let i = 0;
            i <
            Math.min(sourceCopyableFields.length, targetCopyableFields.length);
            i++
        ) {
            const sourceValue = sourceCopyableFields[i].value;

            if (sourceValue !== null && sourceValue !== undefined) {
                copyMapping.push({
                    sourceField: sourceCopyableFields[i],
                    targetField: targetCopyableFields[i],
                    value: sourceValue,
                });
            }
        }

        console.log(
            "Fields to translate:",
            Object.keys(fieldsToTranslate).length
        );
        console.log("Fields to copy:", copyMapping.length);
        console.log("Total characters to translate:", totalCharacters);

        if (
            Object.keys(fieldsToTranslate).length === 0 &&
            copyMapping.length === 0
        ) {
            Ext.Msg.alert(
                "Info",
                "No translatable or copyable data found in " +
                    sourceLangName +
                    " tab"
            );
            return;
        }

        // 翻訳とコピーを実行
        this.executeTranslationAndCopy(
            fieldsToTranslate,
            fieldTypes,
            fieldMapping,
            copyMapping,
            [],
            sourceLang,
            targetLang,
            targetTab,
            provider
        );
    },

    executeTranslationAndCopy: function (
        fieldsToTranslate,
        fieldTypes,
        fieldMapping,
        copyMapping,
        imageMapping,
        sourceLang,
        targetLang,
        targetTab,
        provider
    ) {
        const providerName = provider === "gemini" ? "Gemini" : "DeepL";

        const loadingMask = new Ext.LoadMask({
            msg: "Translating with " + providerName + "...",
            target: targetTab,
        });
        loadingMask.show();

        const objectId = this.getObjectId();

        if (!objectId) {
            loadingMask.hide();
            Ext.Msg.alert("Error", "Object ID not available");
            return;
        }

        if (Object.keys(fieldsToTranslate).length === 0) {
            this.executeBackendCopy(
                objectId,
                sourceLang,
                targetLang,
                loadingMask,
                targetTab,
                0,
                "",
                imageMapping
            );
            return;
        }

        Ext.Ajax.request({
            url: "/admin/translation/translate-fields",
            method: "POST",
            jsonData: {
                fields: fieldsToTranslate,
                fieldTypes: fieldTypes,
                sourceLang: sourceLang,
                targetLang: targetLang,
                formality: "default",
                provider: provider,
            },
            success: (response) => {
                const data = Ext.decode(response.responseText);

                if (data.success) {
                    let updateCount = 0;

                    fieldMapping.forEach((mapping) => {
                        const translatedText = data.translations[mapping.key];

                        if (translatedText) {
                            if (mapping.type === "extjs") {
                                mapping.targetField.component.setValue(
                                    translatedText
                                );
                                mapping.targetField.component.fireEvent(
                                    "change",
                                    mapping.targetField.component,
                                    translatedText
                                );
                                updateCount++;
                            } else if (mapping.type === "quill") {
                                let htmlToSet;
                                if (mapping.preserveHtml) {
                                    htmlToSet = translatedText;
                                } else {
                                    htmlToSet =
                                        "<p>" +
                                        translatedText
                                            .replace(/\n\n/g, "</p><p>")
                                            .replace(/\n/g, "<br>") +
                                        "</p>";
                                }

                                mapping.targetField.element.innerHTML =
                                    htmlToSet;

                                const event = new Event("input", {
                                    bubbles: true,
                                });
                                mapping.targetField.element.dispatchEvent(
                                    event
                                );

                                const changeEvent = new Event("text-change", {
                                    bubbles: true,
                                });
                                mapping.targetField.element.dispatchEvent(
                                    changeEvent
                                );

                                updateCount++;
                            }
                        }
                    });

                    console.log(
                        `=== Translation completed: ${updateCount} fields ===`
                    );

                    this.executeBackendCopy(
                        objectId,
                        sourceLang,
                        targetLang,
                        loadingMask,
                        targetTab,
                        updateCount,
                        providerName,
                        imageMapping
                    );
                } else {
                    loadingMask.hide();
                    Ext.Msg.alert("Error", "Translation error: " + data.error);
                }
            },
            failure: (response) => {
                loadingMask.hide();
                Ext.Msg.alert(
                    "Error",
                    providerName + " translation request failed"
                );
            },
        });
    },

    executeBackendCopy: function (
        objectId,
        sourceLang,
        targetLang,
        loadingMask,
        targetTab,
        translatedCount,
        providerName,
        imageMapping
    ) {
        translatedCount = translatedCount || 0;
        providerName = providerName || "";
        imageMapping = imageMapping || [];

        console.log("=== Preparing backend copy request ===");
        console.log("Image mappings to send:", imageMapping.length);

        const imageFields = {};
        imageMapping.forEach((mapping) => {
            imageFields[mapping.fieldName] = mapping.assetId;
        });

        Ext.Ajax.request({
            url: "/admin/translation/copy-fields",
            method: "POST",
            jsonData: {
                objectId: objectId,
                sourceLanguage: sourceLang,
                targetLanguage: targetLang,
                imageFields: imageFields,
            },
            success: (response) => {
                loadingMask.hide();
                const data = Ext.decode(response.responseText);

                if (data.success) {
                    const copiedCount = data.copiedCount || 0;

                    console.log(
                        `=== Backend copy completed: ${copiedCount} fields ===`
                    );

                    let message = "";
                    if (translatedCount > 0) {
                        message += `${translatedCount} field(s) translated with ${providerName}!<br>`;
                    }
                    if (copiedCount > 0) {
                        message += `${copiedCount} field(s) copied!<br>`;
                    }
                    message += "<br>Click 'Save & Publish' to save changes.";

                    Ext.Msg.alert("Success", message);
                } else {
                    Ext.Msg.alert("Error", "Copy error: " + data.error);
                }
            },
            failure: (response) => {
                loadingMask.hide();
                Ext.Msg.alert("Error", "Copy request failed");
            },
        });
    },

    getObjectId: function () {
        if (this.currentObjectId) {
            console.log("Using cached object ID:", this.currentObjectId);
            return parseInt(this.currentObjectId);
        }

        console.error(
            "Object ID not available. This should have been set in translateTab()"
        );
        return null;
    },

    isTranslatableField: function (component) {
        if (
            !component ||
            typeof component.getValue !== "function" ||
            typeof component.setValue !== "function"
        ) {
            return false;
        }

        const xtype = component.getXType ? component.getXType() : "";
        const translatableTypes = ["textfield", "textareafield", "wysiwyg"];

        return translatableTypes.includes(xtype);
    },

    isCopyableField: function (component) {
        if (
            !component ||
            typeof component.getValue !== "function" ||
            typeof component.setValue !== "function"
        ) {
            return false;
        }

        if (this.isTranslatableField(component)) {
            return false;
        }

        const xtype = component.getXType ? component.getXType() : "";

        const copyableTypes = [
            "combo",
            "combobox",
            "numberfield",
            "datefield",
            "timefield",
            "datetimefield",
            "checkbox",
            "checkboxgroup",
            "radio",
            "radiogroup",
            "hidden",
            "displayfield",
            "slider",
        ];

        return copyableTypes.includes(xtype);
    },

    stripHtmlTags: function (html) {
        if (!html) return "";

        const tmp = document.createElement("div");
        tmp.innerHTML = html;
        const text = tmp.textContent || tmp.innerText || "";

        return text.replace(/\s+/g, " ").trim();
    },

    getLanguageFromTab: function (tab, tabIndex) {
        const title = tab.title.toLowerCase();

        if (
            title.includes("japanese") ||
            title.includes("日本語") ||
            title === "ja"
        )
            return "ja";
        if (
            title.includes("korean") ||
            title.includes("韓国") ||
            title === "ko"
        )
            return "ko";
        if (
            title.includes("simplified") ||
            title.includes("简体") ||
            title === "zh-cn" ||
            title === "zh" ||
            title === "chinese"
        )
            return "zh";
        if (
            title.includes("traditional") ||
            title.includes("繁体") ||
            title.includes("繁體") ||
            title === "zh-hant" ||
            title === "zh-tw" ||
            title === "zh-hk"
        )
            return "zh-hant";
        if (
            title.includes("english") ||
            title.includes("英語") ||
            title.startsWith("en")
        )
            return "en";
        if (
            title.includes("german") ||
            title.includes("ドイツ") ||
            title === "de"
        )
            return "de";
        if (
            title.includes("french") ||
            title.includes("フランス") ||
            title === "fr"
        )
            return "fr";
        if (
            title.includes("spanish") ||
            title.includes("スペイン") ||
            title === "es"
        )
            return "es";
        if (
            title.includes("italian") ||
            title.includes("イタリア") ||
            title === "it"
        )
            return "it";

        return "en";
    },

    // ★ V1.1: タブの実際のロケール情報を取得
    getTabLocale: function(tab) {
        // 方法1: tab.locale
        if (tab.locale) {
            return tab.locale;
        }
        
        // 方法2: tab.data.locale
        if (tab.data && tab.data.locale) {
            return tab.data.locale;
        }
        
        // 方法3: tab.initialConfig.language
        if (tab.initialConfig && tab.initialConfig.language) {
            return tab.initialConfig.language;
        }
        
        // 方法4: tab.language
        if (tab.language) {
            return tab.language;
        }
        
        // フォールバック: タブタイトルから判定
        const index = -1;
        return this.getLanguageFromTab(tab, index);
    },

    getLanguageName: function (langCode) {
        const languageNames = {
            ja: "Japanese",
            en: "English",
            de: "German",
            fr: "French",
            es: "Spanish",
            it: "Italian",
            zh: "Chinese (Simplified)",
            "zh-hant": "Chinese (Traditional)",
            ko: "Korean",
            pt: "Portuguese",
            ru: "Russian",
        };

        return languageNames[langCode] || langCode.toUpperCase();
    },

copyTab: function (targetTab, tabPanel, sourceLang) {
    const me = this;
    
    // sourceLangがnullの場合、Settingsから動的に取得
    if (!sourceLang) {
        Ext.Ajax.request({
            url: "/admin/translation/source-languages",
            method: "GET",
            success: (response) => {
                const data = Ext.decode(response.responseText);
                if (data.success && data.selected_language) {
                    sourceLang = data.selected_language.code;
                    console.log('Fetched source language:', sourceLang);
                    // ロケール自動選択を実行
                    me.executeCopyWithLocaleMatch(targetTab, tabPanel, sourceLang);
                } else {
                    Ext.Msg.alert('Error', 'Please configure source language in Settings > Translation Bundle');
                }
            },
            failure: () => {
                Ext.Msg.alert('Error', 'Failed to load source language settings');
            }
        });
    } else {
        // sourceLangが指定されている場合は直接実行
        this.executeCopyWithLocaleMatch(targetTab, tabPanel, sourceLang);
    }
},

    // ★ V1.1: コピー用ロケール自動選択
    executeCopyWithLocaleMatch: function(targetTab, tabPanel, languageCode) {
        console.log("=== Starting copy with locale match ===");
        console.log("Language code:", languageCode);

        // オブジェクトの利用可能なロケールを取得
        const availableLocales = pimcore.settings.websiteLanguages || [];
        console.log("Available locales:", availableLocales);
        
        // 該当するロケールを検索（最左一致）
        let sourceLocale = null;
        for (let i = 0; i < availableLocales.length; i++) {
            const locale = availableLocales[i];
            if (locale === languageCode || locale.indexOf(languageCode + '_') === 0) {
                sourceLocale = locale;
                console.log("Matched locale:", sourceLocale);
                break;
            }
        }
        
        if (!sourceLocale) {
            Ext.Msg.alert(
                'Error',
                'Source language "' + languageCode + '" content not found in this object'
            );
            return;
        }
        
        // コピー実行（既存のロジックを使用）
        this.executeCopy(targetTab, tabPanel, sourceLocale);
    },

    // ★ 既存のコピーロジック（メソッド名変更）
    executeCopy: function (targetTab, tabPanel, sourceLang) {
        console.log("=== Starting copy execution ===");
        console.log("Target tab:", targetTab.title);
        console.log("Source locale:", sourceLang);

        console.log("=== Searching for Object ID ===");
        let objectId = null;

        // Object ID 取得
        if (tabPanel) {
            let parent = tabPanel.up();
            let depth = 0;

            while (parent && !objectId && depth < 10) {
                if (
                    parent.initialConfig &&
                    parent.initialConfig.object &&
                    parent.initialConfig.object.id
                ) {
                    objectId = parent.initialConfig.object.id;
                    console.log(
                        `✓ Object ID from parent ${depth} initialConfig:`,
                        objectId
                    );
                    break;
                }

                if (
                    parent.data &&
                    parent.data.general &&
                    parent.data.general.o_id
                ) {
                    objectId = parent.data.general.o_id;
                    console.log(`✓ Object ID from parent ${depth}:`, objectId);
                    break;
                }

                parent = parent.up();
                depth++;
            }
        }

        if (!objectId) {
            Ext.Msg.alert(
                "Error",
                "Object ID not found. Please reload the object."
            );
            return;
        }

        this.currentObjectId = objectId;
        console.log("=== Object ID confirmed:", objectId, "===");

        // ターゲット言語を取得
        let targetTabIndex = -1;
        tabPanel.items.each((tab, index) => {
            if (tab === targetTab) {
                targetTabIndex = index;
            }
        });

        const targetLang = this.getLanguageFromTab(targetTab, targetTabIndex);
        console.log("Target language:", targetLang);
        console.log("Source locale:", sourceLang);

        if (sourceLang === targetLang) {
            Ext.Msg.alert(
                "Error",
                "Source and target languages are the same. Please select a different source language."
            );
            return;
        }

        // ローディングマスク表示
        const loadingMask = new Ext.LoadMask({
            target: targetTab,
            msg: "Copying fields...",
        });
        loadingMask.show();

        // バックエンドにコピーリクエスト送信
        Ext.Ajax.request({
            url: "/admin/translation/copy-fields",
            method: "POST",
            jsonData: {
                objectId: objectId,
                sourceLanguage: sourceLang,
                targetLanguage: targetLang,
            },
            success: (response) => {
                loadingMask.hide();
                const data = Ext.decode(response.responseText);

                if (data.success) {
                    const copiedCount = data.copiedCount || 0;
                    console.log(
                        `=== Copy completed: ${copiedCount} fields ===`
                    );

                    Ext.Msg.alert(
                        "Success",
                        `${copiedCount} field(s) copied!<br><br>Reloading object...`,
                        function () {
                            pimcore.helpers.closeObject(objectId);
                            setTimeout(function () {
                                pimcore.helpers.openObject(objectId, "object");
                            }, 300);
                        }
                    );
                } else {
                    Ext.Msg.alert("Error", "Copy error: " + data.error);
                }
            },
            failure: (response) => {
                loadingMask.hide();
                Ext.Msg.alert("Error", "Copy request failed");
            },
        });
    },
});

// Initialize button module
const translationBundleButton = new pimcore.bundle.translation.button();
console.log(
    "Translation Bundle: Button module created",
    translationBundleButton
);