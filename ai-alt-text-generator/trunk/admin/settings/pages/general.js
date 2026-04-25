import React, { useState, useEffect, useRef, useCallback } from 'react';
import { useSettings } from '../contexts/SettingsContext';
import { useUpdateSettings } from '../hooks/useUpdateSettings';
import { useUpdateStateSettings } from '../hooks/useUpdateStateSettings';
import { validateAPIKey } from '../utils/validateAPIKey';
import { useContext } from '@wordpress/element';
import { debounce, isEqual } from 'lodash';
import apiFetch from '@wordpress/api-fetch';
import { Card, CardBody, Spacer, TextControl, SelectControl, ToggleControl, TextareaControl, Button, Notice, Spinner, Flex, FlexBlock, Heading, Text, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { image as imageIcon } from '@wordpress/icons';
import ProgressBar from '../../../admin/settings/atoms/progressBar.js';
import { startBulkProcessing, processNextImage, stopBulkProcessing } from '../../../api/media.js';

const GeneralSettings = () => {
    const { useSettings, useUpdateStateSettings, useUpdateSettings, useIsPending, useNotice, useHasError, useCanSave } = useContext(SettingsContext);

    // Local state for all settings, acting as a "draft"
    const [localSettings, setLocalSettings] = useState(useSettings);
    const [isDirty, setIsDirty] = useState(false);

    const [isProcessing, setIsProcessing] = useState(false);
    const [progress, setProgress] = useState({ total: 0, current: 0 });
    const [model, setModel] = useState(useSettings?.model || '');
    const [allAlt, setAllAlt] = useState(useSettings?.all_alt_text || false);
    const [onUploadAltText, setOnUploadAltText] = useState(useSettings?.on_upload_alt_text || false);
    const [prompt, setPrompt] = useState(useSettings?.prompt || '');
    const [language, setLanguage] = useState(useSettings?.language || 'english');
    const [aiProvider, setAiProvider] = useState(useSettings?.ai_provider || 'openai');
    const [openaiKey, setOpenaiKey] = useState(useSettings?.openai_key || '');
    const [anthropicKey, setAnthropicKey] = useState(useSettings?.anthropic_key || '');
    const [isChangingProvider, setIsChangingProvider] = useState(false);
    const [isGeneratingTest, setIsGeneratingTest] = useState(false);
    const [testError, setTestError] = useState('');
    const [isKeyValid, setIsKeyValid] = useState(true);
    const [keyValidationMessage, setKeyValidationMessage] = useState('');
    const [selectedImage, setSelectedImage] = useState(null);
    const [testAltText, setTestAltText] = useState('');

    // Use a ref to prevent race conditions during saves
    const isSaving = useRef(false);

    // Sync local state when the main context settings change (e.g., after a save)
    useEffect(() => {
        setLocalSettings(useSettings);
    }, [useSettings]);

    // Check for unsaved changes to enable/disable the save button
    useEffect(() => {
        setIsDirty(!isEqual(useSettings, localSettings));
    }, [useSettings, localSettings]);

    // Generic handler to update a setting in the local state
    const handleSettingChange = (key, value) => {
        setLocalSettings(prev => ({ ...prev, [key]: value }));
    };

    const handleSaveAllSettings = async () => {
        try {
            await useUpdateSettings(localSettings);
        } catch (error) {
            console.error('Failed to save settings:', error);
        }
    };

    const handleProviderChange = (newProvider) => {
        // When provider changes, also update the model to its default
        const newModel = useSettings?.default_models[newProvider] || '';
        useUpdateStateSettings('ai_provider', newProvider);
        useUpdateStateSettings('model', newModel);

        const keyForNewProvider = useSettings[`${newProvider}_key`];
        validateAPIKey(keyForNewProvider, newProvider);
    };

    const handleKeyChange = (newKey, provider = aiProvider) => {
        // Update the appropriate key state immediately for UI
        if (provider === 'openai') {
            setOpenaiKey(newKey);
        } else if (provider === 'anthropic') {
            setAnthropicKey(newKey);
        }
        useUpdateStateSettings(provider + '_key', newKey);
    };

    const handleLanguageChange = (newVal) => {
        setLanguage(newVal);
        useUpdateStateSettings('language', newVal);
    };

    const handleModelChange = (newVal) => {
        setModel(newVal);
        useUpdateStateSettings('model', newVal);
    };

    const handlePromptChange = (newVal) => {
        setPrompt(newVal);
        useUpdateStateSettings('prompt', newVal);
    };

    const handleUploadToggle = (newVal) => {
        setOnUploadAltText(newVal);
        useUpdateStateSettings('on_upload_alt_text', newVal);
    };

    const handleAllAltToggle = (newVal) => {
        setAllAlt(newVal);
        useUpdateStateSettings('all_alt_text', newVal);
    };

    const handleTestGeneration = async () => {
        if (!selectedImage) {
            setTestError('Please select an image first');
            return;
        }

        if (!isKeyValid) {
            setTestError(`Please enter a valid ${useSettings?.available_providers?.[aiProvider] || aiProvider} API key first`);
            return;
        }

        setIsGeneratingTest(true);
        setTestError('');
        setTestAltText('');

        try {
            const response = await apiFetch({
                path: '/ai-alt-text-generator/v1/generate-test',
                method: 'POST',
                data: { image_id: selectedImage.id, prompt: prompt }
            });

            if (response.success) {
                setTestAltText(response.alt_text);
            } else {
                setTestError(response.message || 'Failed to generate alt text');
            }
        } catch (error) {
            setTestError(error.message || 'Error generating alt text');
        } finally {
            setIsGeneratingTest(false);
        }
    };

    const openMediaModal = () => {
        const frame = window.wp.media({
            title: __('Select or Upload Image', 'ai-alt-text-generator'),
            library: { type: 'image' },
            multiple: false,
            button: { text: __('Use this image', 'ai-alt-text-generator') }
        });
        frame.on('select', () => {
            const attachment = frame.state().get('selection').first().toJSON();
            setSelectedImage({ id: attachment.id, url: attachment.url, alt: attachment.alt || '' });
        });
        frame.open();
    };

    const handleStartProcessing = async () => {
        // ... same logic as before
    };

    const handleStopProcessing = async () => {
        // ... same logic as before
    };

    const validateAPIKey = useCallback(async (key, provider) => {
        if (!key || key.length < 10) {
            setIsKeyValid(true);
            setKeyValidationMessage('');
            return;
        }
        setIsKeyValid(false);
        setKeyValidationMessage('');
        try {
            const response = await apiFetch({
                path: '/ai-alt-text-generator/v1/validate-key',
                method: 'POST',
                data: { key, provider }
            });
            setIsKeyValid(response.valid);
            setKeyValidationMessage(response.message);
            if (response.valid) {
                setTimeout(() => setKeyValidationMessage(''), 3000);
            }
        } catch (error) {
            setIsKeyValid(false);
            setKeyValidationMessage(error.message || 'Failed to validate API key');
        }
    }, []);

    const debouncedValidate = useCallback(debounce(validateAPIKey, 500), [validateAPIKey]);

    // When provider changes, also validate its key if it exists
    useEffect(() => {
        if (localSettings.ai_provider) {
            const key = localSettings[localSettings.ai_provider + '_key'];
            if (key && key.length > 10) {
                validateAPIKey(key, localSettings.ai_provider);
            } else {
                setIsKeyValid(false);
                setKeyValidationMessage('');
            }
        }
    }, [localSettings.ai_provider]);

    return (
        <>
            <Card>
                <CardBody>
                    <Spacer marginBottom="4">
                        <SelectControl
                            label={__('AI Provider', 'ai-alt-text-generator')}
                            value={localSettings.ai_provider}
                            options={Object.entries(useSettings?.available_providers || {}).map(([key, label]) => ({
                                label,
                                value: key
                            }))}
                            onChange={(value) => handleSettingChange('ai_provider', value)}
                        />
                    </Spacer>

                    <TextControl
                        label={__(`${useSettings?.available_providers?.[localSettings.ai_provider] || localSettings.ai_provider} API Key`, 'ai-alt-text-generator')}
                        placeholder={__('Enter API key here', 'ai-alt-text-generator')}
                        value={localSettings[localSettings.ai_provider + '_key'] || ''}
                        onChange={(value) => handleSettingChange(localSettings.ai_provider + '_key', value)}
                        type="password"
                    />
                    {isProcessing && <Spinner />}
                    {keyValidationMessage && (
                        <Notice status={isKeyValid ? 'success' : 'error'}>
                            {keyValidationMessage}
                        </Notice>
                    )}
                    {localSettings.ai_provider === 'openai' && (
                        <a href="https://help.openai.com/en/articles/4936850-where-do-i-find-my-openai-api-key" target="_blank" rel="noreferrer noopener">
                            {__('Get help for the OpenAI key here', 'ai-alt-text-generator')}
                        </a>
                    )}
                    {localSettings.ai_provider === 'anthropic' && (
                        <a href="https://docs.anthropic.com/en/api/getting-started" target="_blank" rel="noreferrer noopener">
                            {__('Get help for the Anthropic key here', 'ai-alt-text-generator')}
                        </a>
                    )}

                    <Spacer marginTop={4}>
                        <TextControl
                            label={__('Model', 'ai-alt-text-generator')}
                            help={__('Enter the exact model name (e.g., gpt-4o-mini, claude-3-haiku-20240307)', 'ai-alt-text-generator')}
                            value={model}
                            onChange={(value) => useUpdateStateSettings('model', value)}
                        />
                    </Spacer>

                    <Spacer marginTop="8">
                        <TextareaControl
                            label={__('Prompt Template', 'ai-alt-text-generator')}
                            help={__('Customize the prompt used to generate alt text', 'ai-alt-text-generator')}
                            value={prompt}
                            onChange={(value) => useUpdateStateSettings('prompt', value)}
                            rows={4}
                        />
                    </Spacer>

                    <Spacer marginTop={8}>
                        <Flex gap={4} align="flex-start">
                            <FlexBlock>
                                <Card>
                                    <CardBody>
                                        <Heading level={3}>{__('Test Prompt', 'ai-alt-text-generator')}</Heading>
                                        <Text>{__('Test your prompt with a sample image', 'ai-alt-text-generator')}</Text>
                                        <Placeholder
                                            icon={imageIcon}
                                            label={__('Test Image', 'ai-alt-text-generator')}
                                        >
                                            <Button isPrimary onClick={openMediaModal}>{__('Select or Upload Image', 'ai-alt-text-generator')}</Button>
                                        </Placeholder>
                                        {selectedImage && (
                                            <div style={{ marginTop: '10px' }}>
                                                <img src={selectedImage.url} alt="Test" style={{ maxWidth: '100px', height: 'auto' }} />
                                                <Button isPrimary onClick={handleTestGeneration} disabled={isGeneratingTest}>
                                                    {isGeneratingTest ? <Spinner /> : __('Generate Alt Text', 'ai-alt-text-generator')}
                                                </Button>
                                            </div>
                                        )}
                                        {testError && <Notice status="error">{testError}</Notice>}
                                        {testAltText && <Notice status="success">{testAltText}</Notice>}
                                    </CardBody>
                                </Card>
                            </FlexBlock>
                        </Flex>
                    </Spacer>

                    <Spacer marginTop={4}>
                        <ToggleControl
                            label={__('Generate on Upload', 'ai-alt-text-generator')}
                            help={__('Automatically generate alt text when images are uploaded', 'ai-alt-text-generator')}
                            checked={onUploadAltText}
                            onChange={(value) => useUpdateStateSettings('on_upload_alt_text', value)}
                        />
                    </Spacer>

                    <Spacer marginTop={3}>
                        <ToggleControl
                            label={__('Process All Images', 'ai-alt-text-generator')}
                            help={__('Generate alt text for all images, even if they already have alt text', 'ai-alt-text-generator')}
                            checked={allAlt}
                            onChange={(value) => useUpdateStateSettings('all_alt_text', value)}
                        />
                    </Spacer>
                </CardBody>
            </Card>

            <Spacer marginTop={4}>
                <Card>
                    <CardBody>
                        <Heading level={3}>{__('Bulk Generation', 'ai-alt-text-generator')}</Heading>
                        <Text>{__('Generate alt text for multiple images at once', 'ai-alt-text-generator')}</Text>

                        <Spacer marginTop={3}>
                            <ToggleControl
                                label={__('Process All Images', 'ai-alt-text-generator')}
                                help={__('Generate alt text for all images, even if they already have alt text', 'ai-alt-text-generator')}
                                checked={allAlt}
                                onChange={(value) => useUpdateStateSettings('all_alt_text', value)}
                            />
                        </Spacer>
                        <Button isPrimary onClick={handleStartProcessing} disabled={isProcessing}>
                            {isProcessing ? __('Processing...', 'ai-alt-text-generator') : __('Start Bulk Generation', 'ai-alt-text-generator')}
                        </Button>
                        {isProcessing && <Button isSecondary onClick={handleStopProcessing}>{__('Stop', 'ai-alt-text-generator')}</Button>}
                        {progress.total > 0 && <ProgressBar progress={(progress.current / progress.total) * 100} />}
                        {processMessage && <Notice status="success">{processMessage}</Notice>}
                        {processError && <Notice status="error">{processError}</Notice>}
                    </CardBody>
                </Card>
            </Spacer>

            <div style={{ position: 'sticky', bottom: '20px', padding: '10px', backgroundColor: '#fff', borderTop: '1px solid #ddd', boxShadow: '0 -2px 5px rgba(0,0,0,0.1)', zIndex: 100, display: 'flex', alignItems: 'center' }}>
                <Button
                    isPrimary
                    onClick={handleSaveAllSettings}
                    disabled={!isDirty || isProcessing || isKeyValid}
                >
                    {__('Save Changes', 'ai-alt-text-generator')}
                </Button>
                {useIsPending && <Spinner style={{ marginLeft: '10px' }} />}
                {useNotice && <span style={{ marginLeft: '10px', color: useHasError ? 'red' : 'green' }}>{useNotice}</span>}
            </div>
        </>
    );
};

export default GeneralSettings; 