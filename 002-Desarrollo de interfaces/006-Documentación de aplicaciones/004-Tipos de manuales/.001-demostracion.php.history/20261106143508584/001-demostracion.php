<?php

require_once __DIR__ . '/MarkdownRenderer.php';

$renderer = new MarkdownRenderer();

$markdown = file_get_contents(__DIR__ . '/jocarsa-curriculum-comercial.md');

echo $renderer->renderDocument($markdown, 'Demo Markdown');
