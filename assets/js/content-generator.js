( function( wp ) {
    const { createElement } = wp.element;
    const { PluginSidebar } = wp.editor; // Mise à jour de l'import
    const { PanelBody, TextareaControl, Button } = wp.components;
    const { select, dispatch } = wp.data;
    const ChatGPTContentGenerator = () => {
        const [prompt, setPrompt] = React.useState('');
        const [isGenerating, setIsGenerating] = React.useState(false);
        const [parsedContent, setParsedContent] = React.useState('');
        const [showParsed, setShowParsed] = React.useState(false);
        const [replacementContent, setReplacementContent] = React.useState('');
    
        const applyReplacementContent = () => {
            const editor = select('core/block-editor');
            const blocks = editor.getBlocks();
            const newTextLines = replacementContent.split('\n').filter(line => line.trim() !== '');
    
            let lineIndex = 0;
    
            const replaceTextInBlocks = (blocks) => {
                return blocks.map(block => {
                    if (lineIndex >= newTextLines.length) {
                        return block;
                    }
    
                    const currentLine = newTextLines[lineIndex].trim();
    
                    switch (block.name) {
                        case 'core/paragraph':
                            if (!currentLine.startsWith('#') && !currentLine.startsWith('-')) {
                                block.attributes.content = currentLine || block.attributes.content;
                                lineIndex++;
                            }
                            break;
                        case 'core/heading':
                            if (currentLine.startsWith('#')) {
                                const headingText = currentLine.replace(/^#+\s*/, '');
                                block.attributes.content = headingText || block.attributes.content;
                                lineIndex++;
                            }
                            break;
                        case 'core/list-item':
                            if (currentLine.startsWith('-')) {
                                const listItemText = currentLine.replace(/^-+\s*/, '');
                                block.attributes.content = listItemText || block.attributes.content;
                                lineIndex++;
                            }
                            break;
                        default:
                            if (block.innerBlocks && block.innerBlocks.length > 0) {
                                block.innerBlocks = replaceTextInBlocks(block.innerBlocks);
                            }
                            break;
                    }
                    return block;
                });
            };
    
            const updatedBlocks = replaceTextInBlocks(blocks);
            dispatch('core/block-editor').resetBlocks(updatedBlocks);
            console.log('Texte remplacé dans les blocs');
        };

        const convertToMarkdown = (blocks) => {
            const processBlock = (block) => {
                console.log('Traitement du bloc:', block.name);
                
                // Traiter d'abord les blocs enfants s'ils existent
                let innerContent = '';
                if (block.innerBlocks && block.innerBlocks.length > 0) {
                    innerContent = block.innerBlocks
                        .map(innerBlock => processBlock(innerBlock))
                        .filter(content => content)
                        .join('\n\n');
                }
        
                // Traiter le bloc actuel
                switch (block.name) {
                    case 'core/paragraph':
                        return block.attributes.content || '';
                    
                    case 'core/heading':
                        const level = block.attributes.level || 2;
                        return '#'.repeat(level) + ' ' + (block.attributes.content || '');
                    
                    case 'core/columns':
                        return innerContent; // Retourner le contenu des colonnes
                    
                    case 'core/column':
                        return innerContent; // Retourner le contenu de la colonne
                    
                    case 'core/list':
                        // Traiter les éléments de la liste
                        return block.innerBlocks.map(listItem => {
                            if (listItem.name === 'core/list-item') {
                                return `- ${listItem.attributes.content || ''}`;
                            }
                            return '';
                        }).join('\n');
                    
                    default:
                        console.log('Type de bloc non géré:', block.name);
                        return innerContent || '';
                }
            };
        
            const markdownContent = blocks
                .map(block => processBlock(block))
                .filter(content => content)
                .join('\n\n');
        
            console.log('Markdown final:', markdownContent);
            return markdownContent;
        };
        const generateContent = async () => {
            setIsGenerating(true);
            // Utilisation du nouveau sélecteur block-editor
            const editor = select('core/block-editor');
            const blocks = editor.getBlocks();
            const markdownContent = convertToMarkdown(blocks);
            setParsedContent(markdownContent);
        
            try {
                const response = await fetch('/wp-json/chatgpt-content-generator/v1/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    body: JSON.stringify({
                        content: markdownContent,
                        prompt: prompt
                    })
                });
        
                const data = await response.json();
                
                if (data.success) {
                    const newBlocks = wp.blocks.parse(data.content);
                    // Utilisation du nouveau dispatch block-editor
                    dispatch('core/block-editor').resetBlocks(newBlocks);
                } else {
                    console.error('Erreur lors de la génération:', data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                setIsGenerating(false);
            }
        };

        const handleShowParsed = () => {
            console.log('Bouton cliqué');
            // Utilisation du nouveau sélecteur block-editor
            const editor = select('core/block-editor');
            const blocks = editor.getBlocks();
            console.log('Blocs récupérés:', blocks); // Debug
            const markdownContent = convertToMarkdown(blocks);
            console.log('Contenu Markdown:', markdownContent);
            setParsedContent(markdownContent);
            setShowParsed(!showParsed);
        };



        return createElement(
            PluginSidebar,
            {
                name: 'chatgpt-content-generator',
                title: 'ChatGPT Generator'
            },
            createElement(
                PanelBody,
                {},
                createElement(TextareaControl, {
                    label: 'Instructions pour ChatGPT',
                    value: prompt,
                    onChange: setPrompt,
                    rows: 4,
                    __nextHasNoMarginBottom: true
                }),
                createElement(Button, {
                    isPrimary: true,
                    onClick: generateContent,
                    isBusy: isGenerating,
                    disabled: isGenerating
                }, isGenerating ? 'Génération...' : 'Générer le contenu'),
                createElement(Button, {
                    isSecondary: true,
                    onClick: handleShowParsed,
                    style: { marginTop: '10px' }
                }, showParsed ? 'Masquer le contenu brut' : 'Voir le contenu brut'),
                showParsed && createElement('pre', {
                    style: { 
                        marginTop: '10px',
                        whiteSpace: 'pre-wrap',
                        backgroundColor: '#f0f0f0',
                        padding: '10px',
                        borderRadius: '4px'
                    }
                }, parsedContent || 'Aucun contenu à afficher'),
                createElement(TextareaControl, {
                    label: 'Contenu de remplacement',
                    value: replacementContent,
                    onChange: setReplacementContent,
                    rows: 10,
                    style: { marginTop: '10px' }
                }),
                createElement(Button, {
                    isPrimary: true,
                    onClick: applyReplacementContent,
                    style: { marginTop: '10px' }
                }, 'Appliquer le nouveau texte')
            )
        );
    };

    wp.plugins.registerPlugin('chatgpt-content-generator', {
        icon: 'admin-comments',
        render: ChatGPTContentGenerator
    });
})( window.wp );