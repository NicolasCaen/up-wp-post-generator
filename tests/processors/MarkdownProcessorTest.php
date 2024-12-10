<?php
use PHPUnit\Framework\TestCase;

class MarkdownProcessorTest extends TestCase {
    private $markdownProcessor;

    protected function setUp(): void {
        $this->markdownProcessor = new Markdown_Processor();
    }

    public function testConvertToMarkdown() {
        // Test pour h2
        $input1 = '<!-- wp:heading {"level":2} --><h2>Mon titre</h2><!-- /wp:heading -->';
        $expected1 = "## Mon titre\n\n";
        
        // Test pour h3
        $input2 = '<!-- wp:heading {"level":3} --><h3>Sous-titre</h3><!-- /wp:heading -->';
        $expected2 = "### Sous-titre\n\n";
        
        // Test pour paragraphe
        $input3 = '<!-- wp:paragraph --><p>Mon paragraphe</p><!-- /wp:paragraph -->';
        $expected3 = "Mon paragraphe\n\n";
        
        // Test pour liste
        $input4 = '<!-- wp:list-item --><li>Item de liste</li><!-- /wp:list-item -->';
        $expected4 = "- Item de liste\n";
        
        // Test pour paragraphe simple
        $input5 = '<!-- wp:paragraph --><p>Tesla : L\'Innovation Électrique Qui Révolutionne l\'Automobile</p><!-- /wp:paragraph -->';
        $expected5 = "Tesla : L'Innovation Électrique Qui Révolutionne l'Automobile\n\n";
        $result5 = $this->markdownProcessor->process($input5);
        $this->assertEquals($expected5, $result5);
        

    }

    public function testConvertEmptyContent() {
        $result = $this->markdownProcessor->process('');
        $this->assertEquals('', $result);
    }

    public function testConvertInvalidContent() {
        $result = $this->markdownProcessor->process('<!-- wp:invalid -->');
        $this->assertEquals('', $result);
    }
}
