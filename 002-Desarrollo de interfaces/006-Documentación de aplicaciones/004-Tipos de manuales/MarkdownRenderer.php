<?php
declare(strict_types=1);

/** Markdown renderer autónomo, sin dependencias. PHP 8.0+. */
final class MarkdownRenderer
{
    private array $references = [];
    private array $footnotes = [];
    private array $usedFootnotes = [];
    private array $headingIds = [];
    private int $listDepth = 0;

    public function renderFile(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("No se puede leer: $path");
        }
        $source = file_get_contents($path);
        if ($source === false) {
            throw new RuntimeException("Error leyendo: $path");
        }
        return $this->render($source);
    }

    public function render(string $markdown): string
    {
        $this->references = $this->footnotes = $this->usedFootnotes = $this->headingIds = [];
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $markdown = str_replace("\t", '    ', $markdown);
        $lines = explode("\n", $markdown);
        $filtered = [];
        foreach ($lines as $line) {
            if (preg_match('/^ {0,3}\[\^([^\]]+)\]:\s*(.*)$/u', $line, $m)) {
                $this->footnotes[$m[1]] = $m[2];
                continue;
            }
            if (preg_match('/^ {0,3}\[([^\]]+)\]:\s*<?([^\s>]+)>?(?:\s+["\']([^"\']+)["\'])?\s*$/u', $line, $m)) {
                $this->references[strtolower(trim($m[1]))] = [$m[2], $m[3] ?? ''];
                continue;
            }
            $filtered[] = $line;
        }
        $html = $this->blocks($filtered);
        if ($this->usedFootnotes) {
            $html .= "<section class=\"footnotes\"><hr><ol>\n";
            foreach ($this->usedFootnotes as $id => $number) {
                $body = $this->inline($this->footnotes[$id] ?? '');
                $html .= '<li id="fn-' . $this->esc($id) . '">' . $body . ' <a href="#fnref-' . $this->esc($id) . '" aria-label="Volver">↩</a></li>' . "\n";
            }
            $html .= "</ol></section>\n";
        }
        return $html;
    }

    public function renderDocument(string $markdown, string $title = 'Documento Markdown'): string
    {
        $body = $this->render($markdown);
        $title = $this->esc($title);
        return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
            . $title . '</title><style>' . self::css() . '</style></head><body><main class="markdown-body">'
            . $body . '</main></body></html>';
    }

    public static function css(): string
    {
        return <<<'CSS'
*{box-sizing:border-box}body{margin:0;background:#f5f6f8;color:#24292f;font:16px/1.7 system-ui,sans-serif}
.markdown-body{max-width:980px;margin:32px auto;padding:42px;background:#fff;border-radius:12px;overflow-wrap:anywhere}
.markdown-body h1,.markdown-body h2,.markdown-body h3{line-height:1.3;margin:1.5em 0 .6em}
.markdown-body h1,.markdown-body h2{padding-bottom:.3em;border-bottom:1px solid #ddd}
.markdown-body a{color:#0969da}.markdown-body img{max-width:100%;height:auto}
.markdown-body blockquote{border-left:4px solid #aaa;padding:4px 18px;margin:16px 0;color:#57606a;background:#f6f8fa}
.markdown-body code{background:#eff1f3;padding:2px 5px;border-radius:4px}
.markdown-body pre{padding:18px;overflow:auto;background:#161b22;color:#e6edf3;border-radius:8px}
.markdown-body pre code{background:none;padding:0}.markdown-body table{border-collapse:collapse;display:block;overflow-x:auto;margin:20px 0}
.markdown-body td,.markdown-body th{border:1px solid #d0d7de;padding:8px 12px}.markdown-body th{background:#f6f8fa}
.markdown-body tr:nth-child(even){background:#fafbfc}.markdown-body li{margin:4px 0}
.markdown-body hr{border:0;border-top:1px solid #d0d7de;margin:28px 0}
.markdown-body .footnotes{font-size:.9em}.markdown-body input[type=checkbox]{margin-right:8px}
@media(max-width:700px){.markdown-body{margin:0;padding:20px;border-radius:0}}
@media print{body{background:white}.markdown-body{margin:0;padding:0;max-width:none}}
CSS;
    }

    private function blocks(array $lines): string
    {
        $out = [];
        $n = count($lines);
        for ($i = 0; $i < $n;) {
            $line = $lines[$i];
            if (trim($line) === '') { $i++; continue; }

            // Bloques cercados: ```php, ~~~js
            if (preg_match('/^ {0,3}(`{3,}|~{3,})([^`]*)$/', $line, $m)) {
                $fence = $m[1]; $char = $fence[0]; $length = strlen($fence);
                $language = trim($m[2]); $code = []; $i++;
                while ($i < $n && !preg_match('/^ {0,3}' . preg_quote($char, '/') . '{' . $length . ',}\s*$/', $lines[$i])) {
                    $code[] = $lines[$i++];
                }
                if ($i < $n) $i++;
                $lang = preg_split('/\s+/', $language)[0] ?? '';
                $attr = $lang !== '' ? ' class="language-' . $this->esc($lang) . '"' : '';
                $out[] = '<pre><code' . $attr . '>' . $this->esc(implode("\n", $code)) . '</code></pre>';
                continue;
            }
            // Código indentado
            if (preg_match('/^ {4}/', $line)) {
                $code = [];
                while ($i < $n && (preg_match('/^ {4}/', $lines[$i]) || trim($lines[$i]) === '')) {
                    $code[] = preg_replace('/^ {4}/', '', $lines[$i++]);
                }
                $out[] = '<pre><code>' . $this->esc(rtrim(implode("\n", $code), "\n")) . '</code></pre>';
                continue;
            }
            if (preg_match('/^ {0,3}(#{1,6})(?:\s+|$)(.*?)\s*#*\s*$/u', $line, $m)) {
                $level = strlen($m[1]); $content = trim($m[2]);
                $id = $this->headingId($content);
                $out[] = "<h$level id=\"" . $this->esc($id) . "\">" . $this->inline($content) . "</h$level>";
                $i++; continue;
            }
            if ($i + 1 < $n && trim($line) !== '' && preg_match('/^ {0,3}(=+|-+)\s*$/', $lines[$i+1], $m)) {
                $level = $m[1][0] === '=' ? 1 : 2;
                $id = $this->headingId(trim($line));
                $out[] = "<h$level id=\"" . $this->esc($id) . "\">" . $this->inline(trim($line)) . "</h$level>";
                $i += 2; continue;
            }
            if (preg_match('/^ {0,3}(?:\*\s*){3,}$|^ {0,3}(?:-\s*){3,}$|^ {0,3}(?:_\s*){3,}$/', $line)) {
                $out[] = '<hr>'; $i++; continue;
            }
            if (preg_match('/^ {0,3}>/', $line)) {
                $quote = [];
                while ($i < $n && preg_match('/^ {0,3}> ?(.*)$/', $lines[$i], $m)) {
                    $quote[] = $m[1]; $i++;
                }
                $out[] = "<blockquote>\n" . $this->blocks($quote) . "</blockquote>";
                continue;
            }
            if ($i + 1 < $n && str_contains($line, '|') && $this->tableDelimiter($lines[$i+1])) {
                $headers = $this->splitTableRow($line);
                $aligns = $this->splitTableRow($lines[$i+1]);
                $table = '<table><thead><tr>';
                foreach ($headers as $k => $cell) {
                    $align = $this->alignment($aligns[$k] ?? '');
                    $table .= '<th' . $align . '>' . $this->inline($cell) . '</th>';
                }
                $table .= '</tr></thead><tbody>'; $i += 2;
                while ($i < $n && trim($lines[$i]) !== '' && str_contains($lines[$i], '|')) {
                    $cells = $this->splitTableRow($lines[$i++]); $table .= '<tr>';
                    foreach ($headers as $k => $_) {
                        $table .= '<td' . $this->alignment($aligns[$k] ?? '') . '>' . $this->inline($cells[$k] ?? '') . '</td>';
                    }
                    $table .= '</tr>';
                }
                $out[] = $table . '</tbody></table>';
                continue;
            }
            if ($this->listMatch($line) !== null) {
                $out[] = $this->parseList($lines, $i);
                continue;
            }
            // Párrafos con continuación de línea
            $paragraph = [$line]; $i++;
            while ($i < $n && trim($lines[$i]) !== '' && !$this->startsBlock($lines, $i)) {
                $paragraph[] = $lines[$i++];
            }
            $content = implode("\n", $paragraph);
            $out[] = '<p>' . $this->inline($content) . '</p>';
        }
        return implode("\n", $out) . (count($out) ? "\n" : '');
    }

    private function startsBlock(array $lines, int $i): bool
    {
        $line = $lines[$i];
        if (preg_match('/^ {0,3}(?:#{1,6}(?:\s|$)|>|`{3,}|~{3,})/', $line)) return true;
        if (preg_match('/^ {0,3}(?:\*\s*){3,}$|^ {0,3}(?:-\s*){3,}$|^ {0,3}(?:_\s*){3,}$/', $line)) return true;
        if ($this->listMatch($line) !== null) return true;
        if ($i + 1 < count($lines) && str_contains($line, '|') && $this->tableDelimiter($lines[$i+1])) return true;
        return false;
    }

    private function listMatch(string $line): ?array
    {
        if (!preg_match('/^( *)([-+*]|\d{1,9}[.)])\s+(.*)$/u', $line, $m)) return null;
        return ['indent' => strlen($m[1]), 'ordered' => ctype_digit($m[2][0]), 'marker' => $m[2], 'text' => $m[3]];
    }

    private function parseList(array $lines, int &$i): string
    {
        $first = $this->listMatch($lines[$i]);
        if ($first === null) return '';
        $indent = $first['indent']; $ordered = $first['ordered'];
        $tag = $ordered ? 'ol' : 'ul';
        $start = $ordered ? (int) $first['marker'] : 1;
        $attr = $ordered && $start !== 1 ? ' start="' . $start . '"' : '';
        $out = '<' . $tag . $attr . ">\n";
        $n = count($lines);
        while ($i < $n) {
            $match = $this->listMatch($lines[$i]);
            if ($match === null || $match['indent'] !== $indent || $match['ordered'] !== $ordered) break;
            $text = $match['text']; $i++;
            $checkbox = '';
            if (preg_match('/^\[([ xX])\]\s*(.*)$/s', $text, $m)) {
                $checkbox = '<input type="checkbox" disabled' . (strtolower($m[1]) === 'x' ? ' checked' : '') . '> ';
                $text = $m[2];
            }
            $continuation = [];
            while ($i < $n) {
                if (trim($lines[$i]) === '') {
                    if ($i + 1 < $n && preg_match('/^ {' . ($indent + 2) . ',}\S/', $lines[$i+1])) {
                        $continuation[] = ''; $i++; continue;
                    }
                    break;
                }
                $next = $this->listMatch($lines[$i]);
                if ($next !== null && $next['indent'] <= $indent) break;
                if (preg_match('/^ {' . ($indent + 2) . ',}(.*)$/', $lines[$i], $m)) {
                    $continuation[] = substr($lines[$i], min(strlen($lines[$i]) - strlen(ltrim($lines[$i])), $indent + 2));
                    $i++; continue;
                }
                break;
            }
            $out .= '<li>' . $checkbox . $this->inline($text);
            if ($continuation) {
                $nested = $this->blocks($continuation);
                $out .= "\n" . $nested;
            }
            $out .= "</li>\n";
            if ($i < $n && trim($lines[$i]) === '') {
                $j = $i;
                while ($j < $n && trim($lines[$j]) === '') $j++;
                $next = $j < $n ? $this->listMatch($lines[$j]) : null;
                if ($next && $next['indent'] === $indent && $next['ordered'] === $ordered) $i = $j;
                else break;
            }
        }
        return $out . '</' . $tag . '>';
    }

    private function tableDelimiter(string $line): bool
    {
        $cells = $this->splitTableRow($line);
        if (!$cells) return false;
        foreach ($cells as $cell) {
            if (!preg_match('/^:?-{3,}:?$/', trim($cell))) return false;
        }
        return true;
    }

    private function splitTableRow(string $line): array
    {
        $line = trim($line);
        $line = preg_replace('/^\|/', '', $line);
        $line = preg_replace('/(?<!\\\\)\|$/', '', $line);
        return array_map('trim', preg_split('/(?<!\\\\)\|/', $line));
    }

    private function alignment(string $delimiter): string
    {
        $delimiter = trim($delimiter);
        $left = str_starts_with($delimiter, ':');
        $right = str_ends_with($delimiter, ':');
        $value = $left && $right ? 'center' : ($right ? 'right' : ($left ? 'left' : ''));
        return $value ? ' style="text-align:' . $value . '"' : '';
    }

    private function headingId(string $heading): string
    {
        $text = strip_tags($heading);
        $text = strtolower($text);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) $text = $converted;
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $text), '-');
        if ($slug === '') $slug = 'seccion';
        $base = $slug; $suffix = 2;
        while (isset($this->headingIds[$slug])) $slug = $base . '-' . $suffix++;
        $this->headingIds[$slug] = true;
        return $slug;
    }

    private function inline(string $text): string
    {
        $tokens = [];
        $hold = static function (string $html) use (&$tokens): string {
            $key = "\x1A" . count($tokens) . "\x1A";
            $tokens[$key] = $html;
            return $key;
        };
        // Escapes de Markdown
        $text = preg_replace_callback('/\\\\([\\\\`*_{}\[\]()#+.!>~|\\-])/',
            fn($m) => $hold($this->esc($m[1])), $text);
        // Código inline (delimitadores de uno o varios backticks)
        $text = preg_replace_callback('/(`+)(.+?)\1/s',
            fn($m) => $hold('<code>' . $this->esc(trim(str_replace("\n", ' ', $m[2]))) . '</code>'), $text);
        // Imágenes y enlaces directos, con título opcional
        $text = preg_replace_callback('/(!?)\[([^\]\n]*)\]\(\s*(<[^>]*>|[^\s)]+)(?:\s+["\']([^"\']*)["\'])?\s*\)/u',
            function ($m) use ($hold) {
                $url = trim($m[3], '<>');
                if (!$this->safeUrl($url)) return $hold($this->esc($m[0]));
                $urlAttr = $this->esc($url);
                $title = isset($m[4]) ? ' title="' . $this->esc($m[4]) . '"' : '';
                if ($m[1] === '!') return $hold('<img src="' . $urlAttr . '" alt="' . $this->esc($m[2]) . '"' . $title . '>');
                return $hold('<a href="' . $urlAttr . '"' . $title . '>' . $this->inline($m[2]) . '</a>');
            }, $text);
        // Referencias [texto][id] e imágenes ![alt][id]
        $text = preg_replace_callback('/(!?)\[([^\]\n]+)\]\[([^\]\n]*)\]/u', function ($m) use ($hold) {
            $key = strtolower(trim($m[3] !== '' ? $m[3] : $m[2]));
            if (!isset($this->references[$key])) return $m[0];
            [$url, $title] = $this->references[$key];
            if (!$this->safeUrl($url)) return $hold($this->esc($m[0]));
            $titleAttr = $title !== '' ? ' title="' . $this->esc($title) . '"' : '';
            return $hold($m[1] === '!'
                ? '<img src="' . $this->esc($url) . '" alt="' . $this->esc($m[2]) . '"' . $titleAttr . '>'
                : '<a href="' . $this->esc($url) . '"' . $titleAttr . '>' . $this->inline($m[2]) . '</a>');
        }, $text);
        // Notas al pie
        $text = preg_replace_callback('/\[\^([^\]]+)\]/u', function ($m) use ($hold) {
            if (!isset($this->footnotes[$m[1]])) return $m[0];
            if (!isset($this->usedFootnotes[$m[1]])) $this->usedFootnotes[$m[1]] = count($this->usedFootnotes) + 1;
            $id = $this->esc($m[1]); $number = $this->usedFootnotes[$m[1]];
            return $hold('<sup id="fnref-' . $id . '"><a href="#fn-' . $id . '">' . $number . '</a></sup>');
        }, $text);
        // Autolinks entre ángulos
        $text = preg_replace_callback('/<((?:https?:\/\/|mailto:)[^\s<>]+)>/i', function ($m) use ($hold) {
            if (!$this->safeUrl($m[1])) return $m[0];
            return $hold('<a href="' . $this->esc($m[1]) . '">' . $this->esc($m[1]) . '</a>');
        }, $text);
        // Autolinks URL simples
        $text = preg_replace_callback('~(?<![\w"=/])(https?://[^\s<>]+)~i', function ($m) use ($hold) {
            $url = rtrim($m[1], '.,;:!?');
            $tail = substr($m[1], strlen($url));
            return $hold('<a href="' . $this->esc($url) . '">' . $this->esc($url) . '</a>') . $tail;
        }, $text);
        // Escape HTML antes de generar etiquetas de formato
        $text = $this->esc($text);
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/s', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/___(.+?)___/s', '<strong><em>$1</em></strong>', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/s', '<em>$1</em>', $text);
        $text = preg_replace('/(?<!\w)_([^_\n]+)_(?!\w)/u', '<em>$1</em>', $text);
        $text = preg_replace('/~~(.+?)~~/s', '<del>$1</del>', $text);
        // Dos espacios o barra invertida al final de línea = salto duro
        $text = preg_replace('/(?: {2,}|\\\\)\n/', "<br>\n", $text);
        $text = str_replace("\n", "\n", $text);
        // Restauración de tokens (los tokens no son HTML proporcionado por el usuario)
        return strtr($text, $tokens);
    }

    private function safeUrl(string $url): bool
    {
        $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $compact = preg_replace('/[\x00-\x20\x7f]+/', '', $url);
        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $compact, $m)) {
            return in_array(strtolower(substr($m[0], 0, -1)), ['http', 'https', 'mailto'], true);
        }
        return !str_starts_with($compact, '//');
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
