
<?php

require_once __DIR__ . '/MarkdownRenderer.php';

$renderer = new MarkdownRenderer();

$markdown = file_get_contents(__DIR__ . '/documento.md');

echo $renderer->renderDocument($markdown, 'Demo Markdown');
