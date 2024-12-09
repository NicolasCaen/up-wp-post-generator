( function( wp ) {
    const { createElement } = wp.element;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { PanelBody, TextareaControl, Button } = wp.components;
    const { select, dispatch } = wp.data;

    const ChatGPTContentGenerator = () => {
        const [prompt, setPrompt] = React.useState('');
        const [isGenerating, setIsGenerating] = React.useState(false);

        const generateContent = async () => {
            if (!prompt) return;

            setIsGenerating(true);
            const editor = select('core/editor');
            const blocks = editor.getBlocks();
            
            try {
                const response = await fetch('/wp-json/chatgpt-content-generator/v1/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': wpApiSettings.nonce
                    },
                    body: JSON.stringify({
                        content: editor.getEditedPostContent(),
                        prompt: prompt
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    dispatch('core/editor').resetBlocks(wp.blocks.parse(data.content));
                } else {
                    console.error('Erreur lors de la génération:', data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                setIsGenerating(false);
            }
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
                    rows: 4
                }),
                createElement(Button, {
                    isPrimary: true,
                    onClick: generateContent,
                    isBusy: isGenerating,
                    disabled: isGenerating
                }, isGenerating ? 'Génération...' : 'Générer le contenu')
            )
        );
    };

    wp.plugins.registerPlugin('chatgpt-content-generator', {
        icon: 'admin-comments',
        render: ChatGPTContentGenerator
    });
})( window.wp );