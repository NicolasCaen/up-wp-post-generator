( function( wp ) {
    const { createElement } = wp.element;
    const { PluginSidebar } = wp.editPost; // Changement ici : wp.editPost au lieu de wp.editor
    const { PanelBody, TextareaControl, Button, SelectControl } = wp.components;
    const { select, dispatch } = wp.data;
    const { registerPlugin } = wp.plugins;

    const ChatGPTContentGenerator = () => {
        const [prompt, setPrompt] = React.useState('');
        const [isGenerating, setIsGenerating] = React.useState(false);
        const [instructionType, setInstructionType] = React.useState('new_content');
        const [generatedContent, setGeneratedContent] = React.useState('');
        const [showDialog, setShowDialog] = React.useState(false);
        const [followUpPrompt, setFollowUpPrompt] = React.useState('');

        const prepareData = () => {
            const editor = select('core/block-editor');
            const blocks = editor.getBlocks();
            const content = wp.blocks.serialize(blocks);

            return {
                content: content,
                prompt: prompt,
                instruction_type: instructionType,
                follow_up_prompt: followUpPrompt
            };
        };

        const generateContent = async () => {
            if (!prompt) {
                dispatch('core/notices').createErrorNotice(
                    'Veuillez saisir des instructions',
                    { type: 'snackbar' }
                );
                return;
            }

            setIsGenerating(true);
            try {
                const preparedData = prepareData();
                const response = await fetch('/wp-json/chatgpt-content-generator/v1/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': chatgptSettings.nonce
                    },
                    body: JSON.stringify(preparedData)
                });

                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'Erreur lors de la génération du contenu');
                }

                if (data.success) {
                    setGeneratedContent(data.content);
                    setShowDialog(true);
                } else {
                    throw new Error(data.message || 'Échec de la génération du contenu');
                }
            } catch (error) {
                console.error('Erreur:', error);
                dispatch('core/notices').createErrorNotice(
                    `Erreur lors de la génération: ${error.message}`,
                    { type: 'snackbar' }
                );
            } finally {
                setIsGenerating(false);
            }
        };

        // Composant Dialog pour prévisualiser
        const PreviewDialog = () => {
            if (!showDialog) return null;

            return createElement('div', {
                className: 'chatgpt-preview-dialog',
                style: {
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    backgroundColor: 'white',
                    padding: '20px',
                    borderRadius: '8px',
                    boxShadow: '0 2px 10px rgba(0,0,0,0.1)',
                    zIndex: 1000,
                    maxWidth: '80%',
                    maxHeight: '80vh',
                    overflow: 'auto'
                }
            },
            createElement('h2', {}, 'Prévisualisation du contenu'),
            createElement('pre', {
                style: {
                    whiteSpace: 'pre-wrap',
                    backgroundColor: '#f5f5f5',
                    padding: '10px',
                    borderRadius: '4px'
                }
            }, generatedContent),
            createElement('div', {
                style: {
                    display: 'flex',
                    gap: '10px',
                    marginTop: '20px'
                }
            },
                createElement(Button, {
                    isPrimary: true,
                    onClick: () => {
                        const blocks = wp.blocks.parse(generatedContent);
                        dispatch('core/block-editor').resetBlocks(blocks);
                        setShowDialog(false);
                    }
                }, 'Appliquer'),
                createElement(Button, {
                    isSecondary: true,
                    onClick: () => setShowDialog(false)
                }, 'Fermer')
            ));
        };

        // Utiliser l'information fournie par le serveur
        const isChatGPTProcessor = () => {
            const currentOption = chatgptSettings.instructionOptions.find(
                option => option.value === instructionType
            );
            return currentOption?.uses_chatgpt || false;
        };

        return createElement(
            PluginSidebar,
            {
                name: 'chatgpt-content-generator',
                title: 'Générateur de contenu',
                icon: 'admin-comments'
            },
            createElement(
                PanelBody,
                {},
                createElement(SelectControl, {
                    label: 'Type de génération',
                    value: instructionType,
                    options: chatgptSettings.instructionOptions || [],
                    onChange: setInstructionType
                }),
                // N'afficher le champ d'instructions que si nécessaire
                isChatGPTProcessor() && createElement(TextareaControl, {
                    label: 'Instructions pour ChatGPT',
                    value: prompt,
                    onChange: setPrompt,
                    rows: 4
                }),
                createElement(Button, {
                    isPrimary: true,
                    onClick: generateContent,
                    isBusy: isGenerating,
                    disabled: isGenerating || (isChatGPTProcessor() && !prompt)
                }, isGenerating ? 'Génération...' : 'Générer'),
                createElement(PreviewDialog)
            )
        );
    };

    // Enregistrement du plugin
    if (typeof wp !== 'undefined' && wp.plugins) {
        registerPlugin('chatgpt-content-generator', {
            render: ChatGPTContentGenerator
        });
    } else {
        console.error('WordPress plugins API non disponible');
    }

})( window.wp );