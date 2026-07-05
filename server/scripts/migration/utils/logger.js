'use strict';

/**
 * utils/logger.js
 * Migration-specific logger with ANSI colours, timing, progress bars,
 * and a final summary report.  No external dependencies — pure Node.js.
 */

// ─── ANSI colour helpers ───────────────────────────────────────────────────────
const ANSI = {
  reset:   '\x1b[0m',
  bold:    '\x1b[1m',
  dim:     '\x1b[2m',
  // foreground
  red:     '\x1b[31m',
  green:   '\x1b[32m',
  yellow:  '\x1b[33m',
  blue:    '\x1b[34m',
  magenta: '\x1b[35m',
  cyan:    '\x1b[36m',
  white:   '\x1b[37m',
  // bright
  brightGreen:  '\x1b[92m',
  brightYellow: '\x1b[93m',
  brightCyan:   '\x1b[96m',
  brightWhite:  '\x1b[97m',
};

function c(color, text) {
  return `${ANSI[color]}${text}${ANSI.reset}`;
}

function bold(text) {
  return `${ANSI.bold}${text}${ANSI.reset}`;
}

// ─── Timing state ─────────────────────────────────────────────────────────────
const migrationTimings = [];   // [{ name, startMs, endMs, stats }]
let _globalStart = null;

// ─── Logger methods ───────────────────────────────────────────────────────────

/**
 * Informational message (blue)
 */
function info(msg) {
  console.log(`${c('cyan', '[INFO]')}  ${msg}`);
}

/**
 * Success message (green)
 */
function success(msg) {
  console.log(`${c('brightGreen', '[OK]')}    ${msg}`);
}

/**
 * Warning message (yellow)
 */
function warn(msg) {
  console.warn(`${c('brightYellow', '[WARN]')}  ${msg}`);
}

/**
 * Error message (red)
 */
function error(msg) {
  console.error(`${c('red', '[ERROR]')} ${msg}`);
}

/**
 * Progress line — overwrites current line in a TTY.
 * Falls back to a newline in non-TTY environments (CI, log files).
 * @param {number} current - items processed so far
 * @param {number} total   - total items
 * @param {string} msg     - label for this batch
 */
function progress(current, total, msg = '') {
  const pct    = total > 0 ? Math.round((current / total) * 100) : 100;
  const filled = Math.round(pct / 5);   // 20-char bar
  const bar    = '█'.repeat(filled) + '░'.repeat(20 - filled);
  const line   = `  ${c('cyan', `[${bar}]`)} ${c('brightWhite', `${pct}%`)}  ${current}/${total}  ${c('dim', msg)}`;

  if (process.stdout.isTTY) {
    process.stdout.clearLine(0);
    process.stdout.cursorTo(0);
    process.stdout.write(line);
    if (current >= total) process.stdout.write('\n');
  } else {
    console.log(line);
  }
}

/**
 * Prints a decorated separator / section header.
 * @param {string} title
 */
function separator(title = '') {
  const line   = '─'.repeat(60);
  const header = title
    ? `${c('magenta', '┌' + line + '┐')}\n${c('magenta', '│')} ${bold(c('brightCyan', title.padEnd(58)))} ${c('magenta', '│')}\n${c('magenta', '└' + line + '┘')}`
    : c('dim', '─'.repeat(62));
  console.log('\n' + header);
}

// ─── Timing helpers ───────────────────────────────────────────────────────────

/**
 * Records the start of a named migration stage.
 * @param {string} name
 * @returns {{ name:string, startMs:number }}
 */
function startTimer(name) {
  if (!_globalStart) _globalStart = Date.now();
  const entry = { name, startMs: Date.now(), endMs: null, stats: null };
  migrationTimings.push(entry);
  return entry;
}

/**
 * Records the end time and stats for a timer entry.
 * @param {{ name:string, startMs:number }} entry - returned by startTimer()
 * @param {{ extracted:number, migrated:number, skipped:number, failed:number }} stats
 */
function endTimer(entry, stats = {}) {
  entry.endMs = Date.now();
  entry.stats = { extracted: 0, migrated: 0, skipped: 0, failed: 0, ...stats };
  const elapsed = ((entry.endMs - entry.startMs) / 1000).toFixed(2);

  const { extracted, migrated, skipped, failed } = entry.stats;
  console.log(
    `  ${c('dim', '↳')} ${bold(entry.name)} ${c('dim', `(${elapsed}s)`)}  ` +
    `extracted=${c('cyan', extracted)}  ` +
    `migrated=${c('brightGreen', migrated)}  ` +
    `skipped=${c('brightYellow', skipped)}  ` +
    `failed=${failed > 0 ? c('red', failed) : c('dim', failed)}`
  );
}

// ─── Summary report ───────────────────────────────────────────────────────────

/**
 * Prints the final migration summary table and returns totals.
 * @returns {{ totalExtracted:number, totalMigrated:number, totalSkipped:number, totalFailed:number }}
 */
function printSummary() {
  const totalMs = _globalStart ? Date.now() - _globalStart : 0;
  separator('MIGRATION SUMMARY');

  const colW = [28, 10, 10, 8, 8, 8];
  const header = [
    'Collection'.padEnd(colW[0]),
    'Extracted'.padEnd(colW[1]),
    'Migrated'.padEnd(colW[2]),
    'Skipped'.padEnd(colW[3]),
    'Failed'.padEnd(colW[4]),
    'Time(s)'.padEnd(colW[5]),
  ].join(' │ ');

  console.log(c('dim', '─'.repeat(header.length)));
  console.log(bold(c('brightWhite', header)));
  console.log(c('dim', '─'.repeat(header.length)));

  let totals = { extracted: 0, migrated: 0, skipped: 0, failed: 0 };

  for (const t of migrationTimings) {
    if (!t.stats) continue;
    const elapsed = t.endMs ? ((t.endMs - t.startMs) / 1000).toFixed(2) : '—';
    const { extracted, migrated, skipped, failed } = t.stats;
    totals.extracted += extracted;
    totals.migrated  += migrated;
    totals.skipped   += skipped;
    totals.failed    += failed;

    const row = [
      t.name.padEnd(colW[0]),
      String(extracted).padEnd(colW[1]),
      String(migrated).padEnd(colW[2]),
      String(skipped).padEnd(colW[3]),
      (failed > 0 ? c('red', String(failed)) : String(failed)).padEnd(colW[4]),
      elapsed.padEnd(colW[5]),
    ].join(' │ ');
    console.log(row);
  }

  console.log(c('dim', '─'.repeat(header.length)));

  const footerRow = [
    bold('TOTAL'.padEnd(colW[0])),
    bold(c('cyan',        String(totals.extracted).padEnd(colW[1]))),
    bold(c('brightGreen', String(totals.migrated ).padEnd(colW[2]))),
    bold(c('brightYellow',String(totals.skipped  ).padEnd(colW[3]))),
    bold(totals.failed > 0
      ? c('red', String(totals.failed).padEnd(colW[4]))
      : String(totals.failed).padEnd(colW[4])),
    bold(((totalMs / 1000).toFixed(2)).padEnd(colW[5])),
  ].join(' │ ');
  console.log(footerRow);
  console.log(c('dim', '─'.repeat(header.length)));

  const status = totals.failed === 0
    ? c('brightGreen', '✅  ALL MIGRATIONS COMPLETED SUCCESSFULLY')
    : c('red',         `❌  COMPLETED WITH ${totals.failed} FAILURE(S) — review logs above`);
  console.log('\n' + bold(status) + '\n');

  return {
    totalExtracted: totals.extracted,
    totalMigrated:  totals.migrated,
    totalSkipped:   totals.skipped,
    totalFailed:    totals.failed,
  };
}

module.exports = {
  info,
  success,
  warn,
  error,
  progress,
  separator,
  startTimer,
  endTimer,
  printSummary,
};
