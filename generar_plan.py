#!/usr/bin/env python3
# JOCARSA plan generator
# Version 2026.08.25-jocarsa-identity

from pathlib import Path
import html
import re

INTRO_FILENAME = "000-Introducción.md"
OUTPUT_FILENAME = "plan.html"


class Node:
    def __init__(self, title="", node_type="content"):
        self.title = title
        self.node_type = node_type
        self.children = []

    def add(self, node):
        self.children.append(node)
        return node


def clean_markdown_inline(text):
    text = html.escape(text.strip())
    text = re.sub(r"`([^`]+)`", r"<code>\1</code>", text)
    text = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", text)
    text = re.sub(r"__(.+?)__", r"<strong>\1</strong>", text)
    text = re.sub(r"\*(.+?)\*", r"<em>\1</em>", text)
    text = re.sub(
        r"\[([^\]]+)\]\(([^)]+)\)",
        r'<a href="\2" target="_blank" rel="noopener">\1</a>',
        text,
    )
    return text


def text_only(value):
    value = re.sub(r"<[^>]+>", "", value)
    return html.unescape(value).strip()


def normalize_title(value):
    value = text_only(value)
    value = re.sub(r"^\s*\d+\s*[-_.:)]*\s*", "", value)
    value = re.sub(r"\s+", " ", value)
    return value.strip().casefold()


def indentation_value(prefix):
    """
    We only need a relative indentation order, not an exact Markdown width.

    Important for source files that mix tabs and spaces:
        - parent item:      "- HTML"
        - child category:   "\\t- Eventos de ratón"
        - grandchild:       "  - click"

    Counting a tab as ONE indentation step means:
        0 < 1 < 2

    so the intended hierarchy is preserved.
    """
    return sum(1 for ch in prefix if ch == "\t") + sum(
        1 for ch in prefix if ch == " "
    )


def parse_markdown(md_text):
    """
    Parse headings and Markdown list items into a single tree.

    Blank lines are ignored structurally. They NEVER split or reset a list.
    Only indentation changes hierarchy.
    """
    root = Node("ROOT", "root")
    heading_stack = [(0, root)]
    list_stack = []  # [(indent_value, Node)]

    for raw_line in md_text.splitlines():

        # Blank lines are visual spacing only.
        if not raw_line.strip():
            continue

        # Ignore Markdown horizontal rules.
        if re.match(r"^\s*(-{3,}|\*{3,}|_{3,})\s*$", raw_line):
            continue

        # --------------------------------------------------------
        # HEADINGS
        # --------------------------------------------------------
        heading_match = re.match(r"^\s*(#{1,6})\s+(.+?)\s*$", raw_line)

        if heading_match:
            level = len(heading_match.group(1))
            title = heading_match.group(2)

            # A heading creates a new structural context.
            list_stack = []

            while heading_stack and heading_stack[-1][0] >= level:
                heading_stack.pop()

            parent = heading_stack[-1][1]
            node = Node(clean_markdown_inline(title), "heading")
            parent.add(node)
            heading_stack.append((level, node))
            continue

        # --------------------------------------------------------
        # LIST ITEMS
        # --------------------------------------------------------
        list_match = re.match(
            r"^([ \t]*)(?:[-+*]|\d+[.)])(?:[ \t]+)(.+?)\s*$",
            raw_line
        )

        if list_match:
            prefix = list_match.group(1)
            indent = indentation_value(prefix)
            title = list_match.group(2)

            node = Node(clean_markdown_inline(title), "list-item")

            # Remove same-level/deeper items until the nearest valid parent.
            while list_stack and list_stack[-1][0] >= indent:
                list_stack.pop()

            parent = list_stack[-1][1] if list_stack else heading_stack[-1][1]
            parent.add(node)
            list_stack.append((indent, node))
            continue

        # --------------------------------------------------------
        # ORDINARY TEXT
        # --------------------------------------------------------
        # Treat ordinary prose as content under the active list item if
        # one exists, otherwise under the active heading.
        parent = list_stack[-1][1] if list_stack else heading_stack[-1][1]
        parent.add(Node(clean_markdown_inline(raw_line), "paragraph"))

    return root.children


def remove_repeated_folder_title(nodes, folder_name):
    """
    Do not repeat a folder title when 000-Introducción.md starts with
    the same heading/text.

    Example:
        002-Desarrollo de interfaces
            # Desarrollo de interfaces

    becomes simply:
        002-Desarrollo de interfaces
    """
    target = normalize_title(folder_name)
    result = []
    removed = False

    for node in nodes:
        if not removed and normalize_title(node.title) == target:
            # Preserve anything nested below the repeated title.
            result.extend(node.children)
            removed = True
        else:
            result.append(node)

    return result


def folder_node_type(depth):
    if depth == 0:
        return "subject"
    if depth == 1:
        return "unit"
    return "subunit"


def scan_folder(folder, depth=0):
    node = Node(folder.name, folder_node_type(depth))

    intro_file = folder / INTRO_FILENAME

    if intro_file.exists():
        try:
            markdown_nodes = parse_markdown(
                intro_file.read_text(encoding="utf-8")
            )
            node.children.extend(
                remove_repeated_folder_title(markdown_nodes, folder.name)
            )
        except Exception as exc:
            node.add(
                Node(
                    f"Error leyendo {INTRO_FILENAME}: {html.escape(str(exc))}",
                    "error",
                )
            )

    directories = sorted(
        [
            p for p in folder.iterdir()
            if p.is_dir() and not p.name.startswith(".")
        ],
        key=lambda p: p.name.casefold(),
    )

    for directory in directories:
        node.add(scan_folder(directory, depth + 1))

    return node


def scan_root(root):
    directories = sorted(
        [
            p for p in root.iterdir()
            if p.is_dir() and not p.name.startswith(".")
        ],
        key=lambda p: p.name.casefold(),
    )

    return [scan_folder(directory, 0) for directory in directories]


def node_icon(node):
    return {
        "subject": "●",
        "unit": "◆",
        "subunit": "■",
        "heading": "▸",
        "list-item": "•",
        "paragraph": "",
        "error": "!",
    }.get(node.node_type, "•")


def render_node(node, level=0):
    has_children = bool(node.children)

    classes = ["tree-node", f"type-{node.node_type}"]
    if has_children:
        classes.append("has-children")

    toggle = (
        '<span class="toggle" aria-hidden="true"></span>'
        if has_children
        else '<span class="toggle empty" aria-hidden="true"></span>'
    )

    children_html = ""
    if has_children:
        children_html = (
            '<div class="children">'
            + "".join(render_node(child, level + 1) for child in node.children)
            + "</div>"
        )

    return f"""
    <div class="{' '.join(classes)}" data-level="{level}">
        <div class="node-row">
            {toggle}
            <span class="node-icon">{node_icon(node)}</span>
            <div class="node-title">{node.title}</div>
        </div>
        {children_html}
    </div>
    """


def generate_html(nodes, source_path):
    tree_html = "".join(render_node(node) for node in nodes)

    return f"""<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="generator" content="JOCARSA plan generator 2026.08.25">
<title>Plan · JOCARSA</title>

<style>
:root {{
    /* Manual de Identidad Corporativa JOCARSA 2026.1 */
    --indigo: #4B0082;
    --orange: #FF7900;
    --black: #111111;
    --warm-white: #F7F5F2;

    --white: #FFFFFF;
    --muted: #6F6B73;
    --line: #DED8E2;
    --indigo-soft: #EEE7F3;
    --orange-soft: #FFF0E2;
}}

* {{
    box-sizing: border-box;
}}

html,
body {{
    margin: 0;
    min-height: 100%;
}}

body {{
    background: var(--warm-white);
    color: var(--black);
    font-family: Ubuntu, "Ubuntu Sans", system-ui, -apple-system,
                 BlinkMacSystemFont, "Segoe UI", sans-serif;
}}

header {{
    position: sticky;
    top: 0;
    z-index: 20;

    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;

    min-height: 86px;
    padding: 16px 28px;

    background: var(--indigo);
    color: var(--white);
    border-bottom: 4px solid var(--orange);
}}

.brand {{
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}}

.brand-mark {{
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    position: relative;
}}

.brand-mark::before {{
    content: "";
    position: absolute;
    inset: 4px;
    border: 3px solid var(--white);
    transform: rotate(30deg) skewY(-5deg);
}}

.brand-copy {{
    min-width: 0;
}}

.brand-kicker {{
    margin-bottom: 2px;
    color: var(--orange);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .13em;
    text-transform: uppercase;
}}

.brand h1 {{
    margin: 0;
    color: var(--white);
    font-size: 24px;
    font-weight: 700;
    letter-spacing: -.02em;
}}

.brand .source {{
    margin-top: 3px;
    color: rgba(255,255,255,.66);
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 10px;
}}

.actions {{
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}}

button {{
    border: 1px solid rgba(255,255,255,.28);
    border-radius: 5px;
    background: rgba(255,255,255,.08);
    color: var(--white);
    padding: 8px 12px;
    font: inherit;
    font-size: 12px;
    cursor: pointer;
}}

button:hover {{
    background: var(--orange);
    border-color: var(--orange);
    color: var(--black);
}}

main {{
    max-width: 1500px;
    margin: 0 auto;
    padding: 34px 30px 70px;
}}

.tree {{
    background: var(--white);
    border: 1px solid var(--line);
    border-radius: 4px;
    padding: 24px 26px 30px;
}}

.tree-node {{
    position: relative;
}}

.children {{
    margin-left: 22px;
    padding-left: 18px;
    border-left: 1px solid var(--line);
}}

.tree-node.collapsed > .children {{
    display: none;
}}

.node-row {{
    min-height: 34px;
    display: flex;
    align-items: center;
    border-radius: 4px;
    padding: 4px 7px;
}}

.has-children > .node-row {{
    cursor: pointer;
}}

.node-row:hover {{
    background: var(--orange-soft);
}}

.toggle {{
    flex: 0 0 16px;
    width: 16px;
    height: 16px;
    margin-right: 4px;
    position: relative;
}}

.toggle::before {{
    content: "";
    position: absolute;
    left: 3px;
    top: 4px;
    width: 6px;
    height: 6px;
    border-right: 1.5px solid var(--indigo);
    border-bottom: 1.5px solid var(--indigo);
    transform: rotate(45deg);
    transition: transform .12s ease;
}}

.collapsed > .node-row > .toggle::before {{
    transform: rotate(-45deg);
}}

.toggle.empty::before {{
    display: none;
}}

.node-icon {{
    width: 18px;
    margin-right: 6px;
    color: var(--orange);
    text-align: center;
    font-size: 9px;
}}

.node-title {{
    line-height: 1.42;
}}

.type-subject {{
    margin-bottom: 20px;
}}

.type-subject > .node-row {{
    min-height: 50px;
    border-bottom: 2px solid var(--indigo-soft);
}}

.type-subject > .node-row > .node-title {{
    color: var(--indigo);
    font-size: 22px;
    font-weight: 700;
}}

.type-subject > .node-row > .node-icon {{
    color: var(--orange);
}}

.type-unit {{
    margin-top: 5px;
}}

.type-unit > .node-row > .node-title {{
    color: var(--black);
    font-size: 17px;
    font-weight: 700;
}}

.type-subunit > .node-row > .node-title {{
    color: #2f2933;
    font-size: 14px;
    font-weight: 600;
}}

.type-heading > .node-row > .node-title {{
    color: var(--indigo);
    font-size: 14px;
    font-weight: 600;
}}

.type-list-item > .node-row {{
    min-height: 28px;
}}

.type-list-item > .node-row > .node-title {{
    color: #514b55;
    font-size: 13px;
}}

.type-list-item > .node-row > .node-icon {{
    color: var(--orange);
}}

.type-paragraph > .node-row {{
    cursor: default;
    max-width: 1050px;
    padding-top: 7px;
    padding-bottom: 7px;
}}

.type-paragraph > .node-row > .node-title {{
    color: var(--muted);
    font-size: 13px;
    line-height: 1.65;
}}

.type-paragraph .node-icon,
.type-paragraph .toggle {{
    display: none;
}}

.type-error > .node-row > .node-title {{
    color: #A32638;
}}

code {{
    background: var(--indigo-soft);
    color: var(--indigo);
    border-radius: 3px;
    padding: 2px 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}}

a {{
    color: var(--indigo);
    text-decoration-color: var(--orange);
}}

body.presentation header {{
    display: none;
}}

body.presentation main {{
    max-width: none;
    padding: 4vw;
}}

body.presentation .tree {{
    border: 0;
}}

body.presentation .type-subject > .node-row > .node-title {{
    font-size: clamp(30px, 3vw, 56px);
}}

body.presentation .type-unit > .node-row > .node-title {{
    font-size: clamp(22px, 2vw, 38px);
}}

body.presentation .type-subunit > .node-row > .node-title {{
    font-size: clamp(17px, 1.35vw, 27px);
}}

body.presentation .type-heading > .node-row > .node-title {{
    font-size: clamp(16px, 1.15vw, 22px);
}}

body.presentation .type-list-item > .node-row > .node-title {{
    font-size: clamp(14px, 1vw, 20px);
}}

.help {{
    position: fixed;
    right: 17px;
    bottom: 13px;
    color: #8d8790;
    font-size: 10px;
    pointer-events: none;
}}

.version {{
    position: fixed;
    left: 14px;
    bottom: 11px;
    color: #aaa3ad;
    font-size: 9px;
    pointer-events: none;
}}

@media (max-width: 700px) {{
    header {{
        align-items: flex-start;
        flex-direction: column;
    }}

    main {{
        padding: 14px;
    }}

    .tree {{
        padding: 12px;
    }}

    .children {{
        margin-left: 8px;
        padding-left: 12px;
    }}
}}
</style>
</head>

<body>

<header>
    <div class="brand">
        <div class="brand-mark" aria-hidden="true"></div>
        <div class="brand-copy">
            
            <h1>Plan</h1>
            <div class="source">{html.escape(str(source_path))}</div>
        </div>
    </div>

    <div class="actions">
        <button onclick="expandAll()">Desplegar todo</button>
        <button onclick="collapseAll()">Plegar todo</button>
        <button onclick="togglePresentation()">Presentación</button>
    </div>
</header>

<main>
    <div class="tree">
        {tree_html}
    </div>
</main>

<div class="version">JOCARSA plan generator · 2026.08.25</div>
<div class="help">clic: plegar/desplegar · P: presentación · E: expandir · C: plegar</div>

<script>
document
    .querySelectorAll(".has-children > .node-row")
    .forEach(row => {{
        row.addEventListener("click", event => {{
            if (event.target.closest("a") || event.target.closest("button")) {{
                return;
            }}
            row.parentElement.classList.toggle("collapsed");
        }});
    }});

function expandAll() {{
    document
        .querySelectorAll(".tree-node")
        .forEach(node => node.classList.remove("collapsed"));
}}

function collapseAll() {{
    document
        .querySelectorAll(".has-children")
        .forEach(node => node.classList.add("collapsed"));
}}

function togglePresentation() {{
    document.body.classList.toggle("presentation");
}}

document.addEventListener("keydown", event => {{
    const key = event.key.toLowerCase();
    if (key === "p") togglePresentation();
    if (key === "e") expandAll();
    if (key === "c") collapseAll();
}});
</script>

</body>
</html>
"""


def main():
    root = Path.cwd()

    print()
    print("JOCARSA plan generator · 2026.08.25")
    print(f"Origen: {root}")
    print()

    nodes = scan_root(root)
    output = root / OUTPUT_FILENAME
    output.write_text(generate_html(nodes, root), encoding="utf-8")

    print(f"Asignaturas encontradas: {len(nodes)}")
    print(f"Generado: {output}")
    print()


if __name__ == "__main__":
    main()
