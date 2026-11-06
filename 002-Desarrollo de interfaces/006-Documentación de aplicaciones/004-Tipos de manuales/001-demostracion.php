<?php

require_once __DIR__ . '/MarkdownRenderer.php';

$renderer = new MarkdownRenderer();

$markdown = file_get_contents(__DIR__ . '/jocarsa_curriculum_comercial.md');

echo $renderer->renderDocument($markdown, 'Demo Markdown');
