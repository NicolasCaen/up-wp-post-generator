( function( wp ) {
    const { createElement } = wp.element;
    const { PluginSidebar } = wp.editor; 
    const { PanelBody, TextareaControl, Button, SelectControl } = wp.components;
    const { select, dispatch } = wp.data;
    const { registerPlugin } = wp.plugins;


    // Créer un composant d'édition séparé
    const EditableContent = React.memo(React.forwardRef(({ initialContent, onSave }, ref) => {
        const editorRef = React.useRef(null);
        const cmRef = React.useRef(null);

        // Initialisation de CodeMirror
        React.useEffect(() => {
            console.log('Initialisation de CodeMirror');
            if (editorRef.current && !cmRef.current && window.CodeMirror) {
                try {
                    cmRef.current = window.CodeMirror.fromTextArea(editorRef.current, {
                        mode: 'markdown',
                        theme: 'monokai',
                        lineNumbers: false,
                        lineWrapping: true,
                        viewportMargin: Infinity,
                        autofocus: true,
                        inputStyle: 'contenteditable',
                        undoDepth: 200,
                        tabSize: 2,
                        dragDrop: true,
                        extraKeys: {
                            "Enter": "newlineAndIndentContinueMarkdownList"
                        }
                    });

                    cmRef.current.setValue(initialContent);
                    console.log('CodeMirror initialisé avec succès');
                } catch (error) {
                    console.error('Erreur lors de l\'initialisation de CodeMirror:', error);
                }
            }

            return () => {
                if (cmRef.current) {
                    cmRef.current.toTextArea();
                    cmRef.current = null;
                }
            };
        }, [initialContent]);

        // Exposer la méthode getValue
        React.useImperativeHandle(ref, () => ({
            getValue: () => {
                console.log('getValue appelé');
                return cmRef.current ? cmRef.current.getValue() : '';
            }
        }), []);

        return createElement('textarea', {
            ref: editorRef,
            defaultValue: initialContent,
            style: {
                width: '100%',
                minHeight: '200px'
            }
        });
    }));

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
                
                type: instructionType,
                ...(prompt && { prompt: prompt }),
                ...(followUpPrompt && { follow_up_prompt: followUpPrompt })
                };
        };

        const buttonAction = async () => {
            if (!instructionType) {
                throw new Error("Paramètre(s) manquant(s) : type");
            }
            if (isChatGPTProcessor() && !prompt) {
                dispatch('core/notices').createErrorNotice(
                    'Veuillez saisir des instructions',
                    { type: 'snackbar' }
                );
                return;
            }

            setIsGenerating(true);
            try {
                const preparedData = prepareData();
                const response = await fetch(chatgptSettings.restUrl + 'generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': chatgptSettings.nonce
                    },
                    body: JSON.stringify(preparedData)
                });

  
              
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Erreur lors de la génération du contenu');
                }

                const data = await response.json();

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

        // Modifier le PreviewDialog pour utiliser le nouveau composant
        const PreviewDialog = () => {
            if (!showDialog) return null;
            
            const editorRef = React.useRef(null);

            const handleApply = React.useCallback(async () => {
                try {
                    if (editorRef.current) {
                        // 1. Récupérer et logger le contenu
                        const markdownContent = editorRef.current.getValue();
                        console.log('1. Markdown content:', markdownContent);

                        // 2. Récupérer et logger les blocs
                        const currentBlocks = select('core/block-editor').getBlocks();
                        console.log('2. Current blocks:', currentBlocks);

                        // 3. Sérialiser les blocs
                        const serializedBlocks = wp.blocks.serialize(currentBlocks);
                        console.log('3. Serialized blocks:', serializedBlocks);

                        // 4. Créer FormData
                        const formData = new FormData();
                        formData.append('content', markdownContent);
                        formData.append('original_blocks', serializedBlocks);
                        formData.append('type', 'markdown_update_blocks');

                        // Logger le contenu de FormData
                        for (let pair of formData.entries()) {
                            console.log('4. FormData entry:', pair[0], pair[1].length);
                        }

                        // 5. Envoyer la requête
                        console.log('5. Sending request to:', chatgptSettings.restUrl + 'generate');
                        const response = await fetch(chatgptSettings.restUrl + 'generate', {
                            method: 'POST',
                            headers: {
                                'X-WP-Nonce': chatgptSettings.nonce
                            },
                            body: formData
                        });

                        // 6. Logger la réponse brute
                        const responseText = await response.text();
                        console.log('6. Raw response:', responseText);

                        // 7. Parser la réponse
                        let data;
                        try {
                            data = JSON.parse(responseText);
                            console.log('7. Parsed response:', data);
                        } catch (e) {
                            console.error('8. JSON parse error:', e);
                            throw new Error('Réponse invalide du serveur');
                        }

                        if (!response.ok) {
                            throw new Error(data.message || 'Erreur lors de la mise à jour');
                        }

                        if (data.success && data.content) {
                            console.log('10. Updating blocks with:', data.content);
                            const updatedBlocks = wp.blocks.parse(data.content);
                            dispatch('core/block-editor').resetBlocks(updatedBlocks);
                            setShowDialog(false);
                        } else {
                            throw new Error('Aucun contenu retourné par le serveur');
                        }
                    }
                } catch (error) {
                    console.error('Erreur complète:', error);
                    dispatch('core/notices').createErrorNotice(
                        `Erreur lors de la mise à jour: ${error.message}`,
                        { type: 'snackbar' }
                    );
                }
            }, []);

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
            createElement(EditableContent, {
                ref: editorRef,
                initialContent: generatedContent,
                onSave: () => {}
            }),
            createElement('div', {
                style: {
                    display: 'flex',
                    gap: '10px',
                    marginTop: '20px'
                }
            },
                createElement(Button, {
                    isPrimary: true,
                    onClick: handleApply
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
            return currentOption?.requiresPrompt || false;
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
                // N'afficher le champ d'instructions que si requiresPrompt est true
                isChatGPTProcessor() && createElement(TextareaControl, {
                    label: 'Instructions pour ChatGPT',
                    value: prompt,
                    onChange: setPrompt,
                    rows: 4
                }),
                createElement(Button, {
                    isPrimary: true,
                    onClick: buttonAction,
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