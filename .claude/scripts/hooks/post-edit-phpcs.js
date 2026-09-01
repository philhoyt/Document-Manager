#!/usr/bin/env node
'use strict';

/**
 * PostToolUse hook: runs phpcs on edited PHP files.
 *
 * Reads the hook payload as JSON on stdin. When phpcs reports violations,
 * prints a PostToolUse JSON object with `additionalContext` to stdout so
 * the findings are fed back to Claude as context (exit 0 = non-blocking).
 * Always exits 0 — this hook surfaces findings, it never blocks.
 */

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

// additionalContext is capped at 10,000 characters by Claude Code.
const MAX_CONTEXT_LENGTH = 9000;

let raw = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', chunk => { raw += chunk; });
process.stdin.on('end', () => {
    try {
        run(raw);
    } catch (e) {
        process.stderr.write('[wp-phpcs] hook error: ' + e.message + '\n');
    }
    process.exit(0);
});

function emitAdditionalContext(message) {
    const context = message.length > MAX_CONTEXT_LENGTH
        ? message.slice(0, MAX_CONTEXT_LENGTH) + '\n… (output truncated)'
        : message;
    process.stdout.write(JSON.stringify({
        hookSpecificOutput: {
            hookEventName: 'PostToolUse',
            additionalContext: context,
        },
    }) + '\n');
}

function run(raw) {
    const input = JSON.parse(raw);
    const filePath = input.tool_input?.file_path || input.tool_input?.path;

    if (!filePath || !filePath.endsWith('.php')) return;

    const cwd = input.cwd || process.cwd();
    const phpcs = path.join(cwd, 'vendor', 'bin', 'phpcs');

    if (!fs.existsSync(phpcs)) {
        process.stderr.write('[wp-phpcs] vendor/bin/phpcs not found — skipping lint\n');
        return;
    }

    let report;
    try {
        execFileSync(phpcs, ['--standard=WordPress', '--report=emacs', filePath], {
            cwd,
            encoding: 'utf8',
            stdio: ['ignore', 'pipe', 'pipe'],
        });
        return; // Exit code 0 — no violations found.
    } catch (e) {
        // phpcs exits non-zero when violations are found — report is on stdout.
        report = ((e.stdout || '') + (e.stderr || '')).trim();
        if (!report) {
            process.stderr.write('[wp-phpcs] phpcs failed: ' + e.message + '\n');
            return;
        }
    }

    emitAdditionalContext(
        '[wp-phpcs] WordPress coding standards violations in ' + filePath + ':\n' +
        report + '\n' +
        'Fix these in the file you just edited (or run vendor/bin/phpcbf for auto-fixable ones).'
    );
}
