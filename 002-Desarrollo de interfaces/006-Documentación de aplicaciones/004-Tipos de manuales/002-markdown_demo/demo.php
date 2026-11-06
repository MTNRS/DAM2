<?php
require_once __DIR__ . '/MarkdownRenderer.php';
$renderer = new MarkdownRenderer();
$markdown = file_get_contents(__DIR__ . '/jocarsa_curriculum_comercial.md');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demo Markdown</title>
    <link rel="stylesheet" href="markdown.css">
</head>
<body>
    <main class="markdown-body">
        <?= $renderer->render($markdown) ?>
    </main>
</body>
</html>
