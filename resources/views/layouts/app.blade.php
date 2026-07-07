<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        <style>
            :root {
                color-scheme: light;
                --bg: #f5efe4;
                --panel: #fffaf0;
                --ink: #1f1a17;
                --muted: #6f655c;
                --accent: #b6592d;
                --accent-dark: #7d3514;
                --line: #dfd1bf;
                --danger: #992d2d;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family: Georgia, "Times New Roman", serif;
                background:
                    radial-gradient(circle at top left, #e8d6b5 0, transparent 34%),
                    linear-gradient(180deg, #f7f1e7 0%, var(--bg) 100%);
                color: var(--ink);
            }

            a {
                color: var(--accent-dark);
            }

            .shell {
                max-width: 1120px;
                margin: 0 auto;
                padding: 32px 20px 48px;
            }

            .shell.shell-wide {
                max-width: min(100vw - 20px, 1920px);
                padding: 14px 10px 22px;
            }

            .card {
                background: color-mix(in srgb, var(--panel) 92%, white 8%);
                border: 1px solid var(--line);
                border-radius: 24px;
                box-shadow: 0 20px 40px rgba(73, 51, 31, 0.08);
                overflow: hidden;
            }

            .card.card-wide {
                min-height: calc(100vh - 28px);
            }

            .masthead {
                padding: 28px 28px 18px;
                border-bottom: 1px solid var(--line);
                background:
                    linear-gradient(135deg, rgba(182, 89, 45, 0.16), transparent 40%),
                    linear-gradient(180deg, rgba(255, 255, 255, 0.88), rgba(255, 250, 240, 0.72));
            }

            .brand {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
            }

            .brand h1 {
                margin: 0;
                font-size: 32px;
                line-height: 1.1;
            }

            .brand p {
                margin: 8px 0 0;
                color: var(--muted);
            }

            .badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 14px;
                border-radius: 999px;
                background: rgba(182, 89, 45, 0.12);
                color: var(--accent-dark);
                font-size: 14px;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
            }

            .app-menu {
                display: grid;
                gap: 12px;
                margin-top: 18px;
                padding-top: 16px;
                border-top: 1px solid rgba(0, 0, 0, 0.08);
            }

            .menu-section {
                display: grid;
                gap: 8px;
            }

            .menu-label {
                margin: 0;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .menu-links,
            .menu-meta {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                align-items: center;
            }

            .menu-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 9px 12px;
                border-radius: 10px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                background: rgba(255, 255, 255, 0.72);
                color: var(--accent-dark);
                font-size: 14px;
                font-weight: 700;
                line-height: 1;
                text-decoration: none;
            }

            .menu-link.is-active {
                background: rgba(182, 89, 45, 0.12);
                border-color: rgba(182, 89, 45, 0.22);
            }

            .menu-spacer {
                flex: 1 1 auto;
            }

            .menu-user {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 12px;
                border-radius: 10px;
                background: rgba(255, 255, 255, 0.58);
                color: var(--muted);
                font-size: 14px;
                font-weight: 700;
            }

            .menu-form {
                margin: 0;
            }

            .menu-button {
                padding: 9px 12px;
                border-radius: 10px;
            }

            .brand-mark {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                border-radius: 12px;
                background: linear-gradient(135deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.06));
                color: #ffffff;
                font-size: 14px;
                font-weight: 800;
                letter-spacing: 0.08em;
            }

            .content-area {
                min-width: 0;
            }

            .page-head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 18px;
                padding: 22px 24px 18px;
                border-bottom: 1px solid var(--line);
            }

            .page-title-wrap {
                display: grid;
                gap: 8px;
            }

            .page-title {
                margin: 0;
                font-size: 32px;
                line-height: 1.1;
            }

            .page-subtitle {
                margin: 0;
                color: var(--muted);
                font-size: 16px;
                line-height: 1.65;
            }

            .content {
                padding: 28px;
            }

            .content.content-wide {
                padding: 20px;
            }

            .stack {
                display: grid;
                gap: 18px;
            }

            .lead {
                margin: 0;
                color: var(--muted);
                font-size: 18px;
                line-height: 1.7;
            }

            .grid {
                display: grid;
                gap: 18px;
            }

            @media (min-width: 760px) {
                .grid.two {
                    grid-template-columns: 1fr 1fr;
                }
            }

            .panel {
                padding: 18px;
                border-radius: 18px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.72);
            }

            .panel h2,
            .panel h3 {
                margin: 0 0 12px;
                font-size: 22px;
            }

            .panel p,
            .panel li {
                color: var(--muted);
                line-height: 1.7;
            }

            .form-grid {
                display: grid;
                gap: 14px;
            }

            label {
                display: grid;
                gap: 8px;
                font-weight: 700;
            }

            input,
            textarea,
            select {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid #cdbca8;
                border-radius: 12px;
                background: white;
                font: inherit;
                color: var(--ink);
            }

            textarea {
                min-height: 300px;
                resize: vertical;
                font-family: "Courier New", Courier, monospace;
                font-size: 14px;
                line-height: 1.6;
            }

            input:focus,
            textarea:focus,
            select:focus {
                outline: 2px solid rgba(182, 89, 45, 0.25);
                border-color: var(--accent);
            }

            .actions {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }

            button,
            .button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 0;
                border-radius: 12px;
                padding: 12px 18px;
                background: linear-gradient(135deg, var(--accent), var(--accent-dark));
                color: #fffaf0;
                font: inherit;
                font-weight: 700;
                cursor: pointer;
                text-decoration: none;
            }

            .button.secondary {
                background: transparent;
                color: var(--accent-dark);
                border: 1px solid #cdbca8;
            }

            .flash,
            .errors {
                padding: 14px 16px;
                border-radius: 14px;
                border: 1px solid var(--line);
            }

            .flash {
                background: rgba(88, 138, 82, 0.12);
                color: #245c24;
            }

            .errors {
                background: rgba(153, 45, 45, 0.08);
                color: var(--danger);
            }

            .errors ul {
                margin: 0;
                padding-left: 20px;
            }

            .meta {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .pill {
                display: inline-flex;
                align-items: center;
                padding: 8px 12px;
                border-radius: 999px;
                background: #f1e6d7;
                color: var(--accent-dark);
                font-size: 14px;
                font-weight: 700;
            }

            .nav {
                display: flex;
                gap: 12px;
                flex-wrap: wrap;
                align-items: center;
            }

            .inline-form {
                display: inline;
            }

            .helper {
                margin: 0;
                color: var(--muted);
                font-size: 14px;
                line-height: 1.6;
            }

            .mono {
                font-family: "Courier New", Courier, monospace;
            }

            .status {
                display: inline-flex;
                align-items: center;
                padding: 8px 12px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .status-draft {
                background: rgba(164, 116, 31, 0.12);
                color: #8f5b0f;
            }

            .status-published {
                background: rgba(64, 130, 88, 0.14);
                color: #1f6a38;
            }

            .status-archived {
                background: rgba(73, 51, 31, 0.08);
                color: #6a5544;
            }

            .table-wrap {
                overflow-x: auto;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.72);
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                padding: 14px 16px;
                text-align: left;
                border-bottom: 1px solid var(--line);
                vertical-align: top;
            }

            th {
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: var(--muted);
            }

            tr:last-child td {
                border-bottom: 0;
            }

            .stats {
                display: grid;
                gap: 16px;
            }

            @media (min-width: 760px) {
                .stats {
                    grid-template-columns: repeat(3, 1fr);
                }
            }

            .stat {
                padding: 18px;
                border-radius: 18px;
                border: 1px solid var(--line);
                background: rgba(255, 255, 255, 0.76);
            }

            .stat strong {
                display: block;
                font-size: 34px;
                line-height: 1;
                margin-bottom: 8px;
            }

            pre.code-block {
                margin: 0;
                padding: 18px;
                overflow-x: auto;
                border-radius: 16px;
                border: 1px solid #cfbeab;
                background: #fffdf8;
                color: #3a2d22;
                font-family: "Courier New", Courier, monospace;
                font-size: 13px;
                line-height: 1.7;
                white-space: pre-wrap;
                word-break: break-word;
            }

            body.theme-operaton {
                --bg: #edf1f5;
                --panel: #ffffff;
                --ink: #172430;
                --muted: #61707e;
                --accent: #1f6b49;
                --accent-dark: #123725;
                --line: #d7dee5;
                background: linear-gradient(180deg, #f5f7f9 0%, #e8edf2 100%);
                font-family: "Trebuchet MS", "Segoe UI", Tahoma, sans-serif;
            }

            body.theme-operaton a {
                color: #1b6d4a;
            }

            .theme-operaton .shell {
                max-width: min(100vw - 14px, 1760px);
                padding: 10px 7px 24px;
            }

            .theme-operaton .card {
                display: grid;
                grid-template-columns: 290px minmax(0, 1fr);
                min-height: calc(100vh - 20px);
                background: #ecf1f5;
                border: 1px solid #c7d2dc;
                border-radius: 14px;
                box-shadow: 0 16px 36px rgba(15, 27, 37, 0.08);
                overflow: hidden;
            }

            .theme-operaton .card.card-wide {
                min-height: calc(100vh - 20px);
            }

            .theme-operaton .masthead {
                position: relative;
                display: flex;
                flex-direction: column;
                gap: 18px;
                padding: 22px 18px 18px;
                border-right: 1px solid #20364d;
                background: linear-gradient(180deg, #17314b 0%, #0f2234 100%);
                color: #f4f8fb;
                overflow: hidden;
            }

            .theme-operaton .masthead::after {
                content: "";
                position: absolute;
                inset: 0 auto 0 0;
                width: 3px;
                background: linear-gradient(180deg, #14563b 0%, #2b8c5d 55%, #83c59e 100%);
            }

            .theme-operaton .brand {
                justify-content: flex-start;
                align-items: center;
                gap: 12px;
            }

            .theme-operaton .brand h1 {
                font-size: 24px;
                line-height: 1.15;
                letter-spacing: 0.01em;
                color: #f5fbff;
            }

            .theme-operaton .brand p {
                margin: 6px 0 0;
                color: #c2cfda;
                font-size: 14px;
            }

            .theme-operaton .badge {
                border-radius: 6px;
                border: 1px solid rgba(129, 197, 159, 0.42);
                background: rgba(255, 255, 255, 0.08);
                color: #ebf7f0;
                font-size: 12px;
                letter-spacing: 0.08em;
                padding: 8px 11px;
            }

            .theme-operaton .app-menu {
                flex: 1 1 auto;
                align-content: start;
                gap: 16px;
                margin-top: 2px;
                padding-top: 16px;
                border-top: 1px solid rgba(194, 207, 218, 0.18);
            }

            .theme-operaton .menu-section {
                gap: 10px;
            }

            .theme-operaton .menu-label {
                color: #8ea5b9;
                font-size: 11px;
                letter-spacing: 0.12em;
            }

            .theme-operaton .menu-link {
                width: 100%;
                justify-content: flex-start;
                border-radius: 5px;
                border-color: rgba(194, 207, 218, 0.24);
                background: rgba(255, 255, 255, 0.06);
                color: #d6e6f2;
                font-size: 13px;
                letter-spacing: 0.01em;
            }

            .theme-operaton .menu-link:hover {
                background: rgba(255, 255, 255, 0.12);
            }

            .theme-operaton .menu-link.is-active {
                border-color: rgba(129, 197, 159, 0.46);
                background: rgba(43, 140, 93, 0.26);
                color: #f3fbf7;
            }

            .theme-operaton .menu-links {
                flex-direction: column;
                align-items: stretch;
            }

            .theme-operaton .menu-user {
                border-radius: 5px;
                border: 1px solid rgba(194, 207, 218, 0.18);
                background: rgba(255, 255, 255, 0.05);
                color: #c8d6e2;
            }

            .theme-operaton .menu-meta {
                display: grid;
                gap: 12px;
                margin-top: auto;
                padding-top: 14px;
                border-top: 1px solid rgba(194, 207, 218, 0.14);
            }

            .theme-operaton .menu-button {
                padding: 9px 12px;
                border-radius: 5px;
                width: 100%;
            }

            .theme-operaton .content-area {
                display: grid;
                grid-template-rows: auto 1fr;
                background: linear-gradient(180deg, #f6f9fb 0%, #edf2f6 100%);
            }

            .theme-operaton .page-head {
                padding: 22px 24px 18px;
                border-bottom: 1px solid #d5dde5;
                background:
                    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 252, 0.92)),
                    linear-gradient(90deg, rgba(43, 140, 93, 0.08), transparent 22%);
            }

            .theme-operaton .page-head .badge {
                align-self: start;
                border-color: rgba(43, 140, 93, 0.18);
                background: #e8f4ec;
                color: #1d6947;
            }

            .theme-operaton .page-head .menu-user {
                border-color: #d7e0e7;
                background: #ffffff;
                color: #365164;
            }

            .theme-operaton .page-title {
                font-size: 31px;
                color: #14212d;
                letter-spacing: -0.01em;
            }

            .theme-operaton .page-subtitle {
                max-width: 78ch;
                color: #61707e;
            }

            .theme-operaton .content {
                padding: 18px 22px 22px;
                gap: 14px;
            }

            .theme-operaton .content.content-wide {
                padding: 18px 22px 22px;
            }

            .theme-operaton .panel {
                padding: 16px 18px;
                border-radius: 8px;
                border: 1px solid var(--line);
                background: #ffffff;
                box-shadow: 0 1px 0 rgba(15, 27, 37, 0.04);
            }

            .theme-operaton .panel h2,
            .theme-operaton .panel h3 {
                margin-bottom: 10px;
                font-size: 20px;
                font-weight: 600;
                color: #14212d;
            }

            .theme-operaton .panel p,
            .theme-operaton .panel li,
            .theme-operaton .helper,
            .theme-operaton .lead {
                color: var(--muted);
            }

            .theme-operaton .lead {
                font-size: 16px;
                line-height: 1.65;
            }

            .theme-operaton .nav {
                gap: 10px;
            }

            .theme-operaton .stats {
                gap: 14px;
            }

            .theme-operaton .stat {
                position: relative;
                padding: 22px 18px 18px;
                border-radius: 8px;
                border: 1px solid var(--line);
                background: #ffffff;
            }

            .theme-operaton .stat::before {
                content: "";
                position: absolute;
                inset: 0 0 auto;
                height: 3px;
                background: linear-gradient(90deg, #14563b 0%, #2b8c5d 55%, #83c59e 100%);
                border-radius: 8px 8px 0 0;
            }

            .theme-operaton .stat strong {
                font-size: 28px;
                color: #173247;
            }

            .theme-operaton label {
                color: #203140;
                font-weight: 600;
            }

            .theme-operaton input,
            .theme-operaton textarea,
            .theme-operaton select {
                border: 1px solid #c0c9d2;
                border-radius: 4px;
                background: #ffffff;
                color: #192a37;
                font-family: inherit;
            }

            .theme-operaton textarea {
                background: #fbfdfe;
            }

            .theme-operaton input:focus,
            .theme-operaton textarea:focus,
            .theme-operaton select:focus {
                outline: 2px solid rgba(43, 140, 93, 0.16);
                border-color: #2b8c5d;
            }

            .theme-operaton button,
            .theme-operaton .button {
                border-radius: 4px;
                padding: 10px 14px;
                background: linear-gradient(180deg, #277c53 0%, #1a593c 100%);
                color: #f9fcfa;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.1);
            }

            .theme-operaton .button.secondary {
                background: #f7f9fb;
                color: #193146;
                border: 1px solid #c6cfd8;
                box-shadow: none;
            }

            .theme-operaton .flash,
            .theme-operaton .errors {
                border-radius: 6px;
                border-color: #d6dde4;
            }

            .theme-operaton .flash {
                background: #ecf7f0;
                color: #1d5f40;
            }

            .theme-operaton .errors {
                background: #fff1f1;
            }

            .theme-operaton .pill {
                border-radius: 4px;
                border: 1px solid #dde4ea;
                background: #f3f6f8;
                color: #2c495d;
                font-weight: 600;
            }

            .theme-operaton .status {
                border-radius: 4px;
                border: 1px solid transparent;
                letter-spacing: 0.03em;
            }

            .theme-operaton .status-draft {
                background: #f4f6f8;
                border-color: #dde2e8;
                color: #5a6775;
            }

            .theme-operaton .status-published {
                background: #e8f4ec;
                border-color: #b9d9c8;
                color: #1d6947;
            }

            .theme-operaton .status-archived {
                background: #edf1f4;
                border-color: #d9dfe5;
                color: #576675;
            }

            .theme-operaton .table-wrap {
                border-radius: 8px;
                border: 1px solid var(--line);
                background: #ffffff;
            }

            .theme-operaton table {
                background: #ffffff;
            }

            .theme-operaton th {
                background: #f6f9fb;
                color: #536271;
                font-size: 12px;
                font-weight: 700;
            }

            .theme-operaton td {
                color: #233544;
            }

            .theme-operaton tbody tr:hover td {
                background: #fbfcfd;
            }

            .theme-operaton pre.code-block {
                border-radius: 6px;
                border: 1px solid #d4dbe2;
                background: #f7fafb;
                color: #1d3344;
            }

            .theme-operaton .bpmn-toolbar,
            .theme-operaton .bpmn-status,
            .theme-operaton .bpmn-note,
            .theme-operaton .process-meta-card {
                border-radius: 8px;
                border: 1px solid #d7dee5;
                background: #ffffff;
            }

            .theme-operaton .bpmn-toolbar {
                padding: 10px 12px;
                border-top: 3px solid #1f6b49;
            }

            .theme-operaton .bpmn-status {
                background: #f7fbf8;
                color: #4f6b5f;
            }

            .theme-operaton .bpmn-note {
                border-left: 3px solid #5aa377;
                color: #566672;
            }

            .theme-operaton .bpmn-canvas {
                border: 1px solid #cfdbd5;
                border-radius: 8px;
                background: linear-gradient(180deg, #ffffff 0%, #f7fbf8 100%);
            }

            .theme-operaton .process-studio-panel {
                padding: 18px;
            }

            .theme-operaton .process-studio-head {
                margin-bottom: 16px;
            }

            .theme-operaton .process-meta-card {
                gap: 6px;
            }

            @media (max-width: 980px) {
                .theme-operaton .card {
                    grid-template-columns: 1fr;
                }

                .theme-operaton .masthead {
                    border-right: 0;
                    border-bottom: 1px solid #20364d;
                }

                .theme-operaton .masthead::after {
                    inset: auto 0 0;
                    width: auto;
                    height: 3px;
                    background: linear-gradient(90deg, #14563b 0%, #2b8c5d 55%, #83c59e 100%);
                }

                .theme-operaton .menu-links {
                    flex-direction: row;
                    flex-wrap: wrap;
                }

                .theme-operaton .menu-link {
                    width: auto;
                }

                .theme-operaton .menu-meta {
                    grid-template-columns: 1fr;
                }

                .theme-operaton .page-head {
                    flex-direction: column;
                    align-items: stretch;
                    padding: 20px 18px 16px;
                }

                .theme-operaton .content,
                .theme-operaton .content.content-wide {
                    padding: 16px 14px 18px;
                }
            }
        </style>
    </head>
    <body class="{{ $bodyClass ?? 'theme-operaton' }}">
        @php
            $pageTitle = $title ?? config('app.name');
            $pageSubtitle = $subtitle ?? 'Incremental BPMS build with runnable checkpoints.';
            $pageBadge = $badge ?? 'Access Control';
            $menuSections = [];

            if (auth()->check()) {
                $workspaceItems = [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'active' => request()->routeIs('dashboard')],
                ];

                $menuSections[] = [
                    'label' => 'Workspace',
                    'items' => $workspaceItems,
                ];

                if (auth()->user()->isAdmin()) {
                    $menuSections[0]['items'][] = ['label' => 'Admin', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.dashboard')];
                    $menuSections[] = [
                        'label' => 'Design',
                        'items' => [
                            ['label' => 'Processes', 'route' => 'process-definitions.index', 'active' => request()->routeIs('process-definitions.*')],
                        ],
                    ];
                    $menuSections[] = [
                        'label' => 'Execution',
                        'items' => [
                            ['label' => 'Operaton', 'route' => 'operaton.dashboard', 'active' => request()->routeIs('operaton.dashboard')],
                            ['label' => 'Runtime', 'route' => 'runtime.instances.index', 'active' => request()->routeIs('runtime.*')],
                            ['label' => 'Tasks', 'route' => 'tasks.index', 'active' => request()->routeIs('tasks.*')],
                        ],
                    ];
                }
            } else {
                $menuSections[] = [
                    'label' => 'Access',
                    'items' => [
                        ['label' => 'Login', 'route' => 'login', 'active' => request()->routeIs('login')],
                        ['label' => 'Register', 'route' => 'register', 'active' => request()->routeIs('register')],
                    ],
                ];
            }
        @endphp

        <div class="shell {{ $shellClass ?? '' }}">
            <div class="card {{ $cardClass ?? '' }}">
                <div class="masthead">
                    <div class="brand">
                        <div>
                            <div class="brand-mark">BP</div>
                        </div>
                        <div>
                            <h1>{{ config('app.name') }}</h1>
                            <p>Operaton-style Laravel workspace</p>
                        </div>
                    </div>

                    @if ($menuSections !== [])
                        <div class="app-menu">
                            @foreach ($menuSections as $section)
                                <section class="menu-section">
                                    <p class="menu-label">{{ $section['label'] }}</p>
                                    <div class="menu-links">
                                        @foreach ($section['items'] as $item)
                                            <a class="menu-link {{ $item['active'] ? 'is-active' : '' }}"
                                                href="{{ route($item['route']) }}">
                                                {{ $item['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach

                            @auth
                                <div class="menu-meta">
                                    <div class="menu-user">
                                        <span>{{ auth()->user()->name }}</span>
                                        <span>|</span>
                                        <span>{{ auth()->user()->role }}</span>
                                    </div>

                                    <form class="menu-form" method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="menu-button" type="submit">Logout</button>
                                    </form>
                                </div>
                            @endauth
                        </div>
                    @endif
                </div>

                <main class="content-area">
                    <div class="page-head">
                        <div class="page-title-wrap">
                            <div class="badge">{{ $pageBadge }}</div>
                            <h2 class="page-title">{{ $pageTitle }}</h2>
                            <p class="page-subtitle">{{ $pageSubtitle }}</p>
                        </div>

                        @auth
                            <div class="menu-user">
                                <span>{{ auth()->user()->email }}</span>
                            </div>
                        @endauth
                    </div>

                    <div class="content stack {{ $contentClass ?? '' }}">
                        @if (session('status'))
                            <div class="flash">{{ session('status') }}</div>
                        @endif

                        @if ($errors->any())
                            <div class="errors">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
