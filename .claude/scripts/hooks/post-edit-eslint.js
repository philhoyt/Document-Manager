#!/usr/bin/env node
'use strict';

/**
 * PostToolUse hook: runs ESLint on edited JS/JSX/TS/TSX files.
 *
 * Reads the hook payload as JSON on stdin. When ESLint reports problems,
 * prints a PostToolUse JSON object with `additionalContext` to stdout so
 * the findings are fed back to Claude as context (exit 0 = non-blocking).
 * Always exits 0 — this hook surfaces findings, it never blocks.
 */

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const JS_EXTENSIONS = new Set(['.js', '.jsx', '.ts', '.tsx', '.mjs', '.cjs']);

// additionalContext is capped at 10,000 characters by Claude Code.
const MAX_CONTEXT_LENGTH = 9000;

let raw = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', chunk => { raw += chunk; });
process.stdin.on('end', () => {
    try {
        run(raw);
    } catch (e) {
        process.stderr.write('[wp-eslint] hook error: ' + e.message + '\n');
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

    if (!filePath) return;

    const ext = path.extname(filePath).toLowerCase();
    if (!JS_EXTENSIONS.has(ext)) return;

    // Skip generated/vendor files
    if (/\/(build|vendor|node_modules)\//.test(filePath)) return;
    if (filePath.endsWith('.min.js') || filePath.endsWith('.asset.php')) return;

    const cwd = input.cwd || process.cwd();

    // Prefer local wp-scripts eslint, fall back to bare eslint
    const eslintBin = findBin(cwd, [
        'node_modules/.bin/wp-scripts',
        'node_modules/.bin/eslint',
    ]);

    if (!eslintBin) {
        process.stderr.write('[wp-eslint] ESLint not found — run /wp-setup to install\n');
        return;
    }

    const args = eslintBin.endsWith('wp-scripts')
        ? ['lint-js', filePath]
        : [filePath];

    let report;
    try {
        execFileSync(eslintBin, args, {
            cwd,
            encoding: 'utf8',
            stdio: ['ignore', 'pipe', 'pipe'],
        });
        return; // Exit code 0 — no problems found.
    } catch (e) {
        // ESLint exits non-zero when problems are found — report is on stdout.
        report = ((e.stdout || '') + (e.stderr || '')).trim();
        if (!report) {
            process.stderr.write('[wp-eslint] eslint failed: ' + e.message + '\n');
            return;
        }
    }

    emitAdditionalContext(
        '[wp-eslint] ESLint problems in ' + filePath + ':\n' +
        report + '\n' +
        'Fix these in the file you just edited.'
    );
}

function findBin(cwd, candidates) {
    for (const candidate of candidates) {
        const full = path.join(cwd, candidate);
        if (fs.existsSync(full)) return full;
    }
    return null;
}
