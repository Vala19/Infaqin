// Recreates database/e2e.sqlite and seeds it for the Playwright suite.
// Refuses to run unless .env.e2e points at exactly database/e2e.sqlite, so the
// local database.sqlite can never be deleted by mistake.
import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const ENV_FILE = resolve(ROOT, '.env.e2e');
const E2E_DATABASE = 'database/e2e.sqlite';
const DB_PATH = resolve(ROOT, E2E_DATABASE);

function fail(message) {
    console.error(`[e2e-reset] ${message}`);
    process.exit(1);
}

// Reads one key from .env.e2e without printing anything from the file.
function envValue(lines, key) {
    const line = lines.findLast((l) => l.trim().startsWith(`${key}=`));
    if (line === undefined) return undefined;
    return line.slice(line.indexOf('=') + 1).trim().replace(/^(['"])(.*)\1$/, '$2');
}

if (!existsSync(ENV_FILE)) {
    fail('.env.e2e not found. Copy .env.e2e.example to .env.e2e and fill in the Midtrans sandbox keys.');
}

const lines = readFileSync(ENV_FILE, 'utf8').split(/\r?\n/);

const connection = envValue(lines, 'DB_CONNECTION');
if (connection !== 'sqlite') {
    fail(`.env.e2e must set DB_CONNECTION=sqlite (found: ${connection ?? 'nothing'}).`);
}

const database = envValue(lines, 'DB_DATABASE');
if (database === undefined || resolve(ROOT, database) !== DB_PATH) {
    fail(`.env.e2e must set DB_DATABASE=${E2E_DATABASE} (found: ${database ?? 'nothing'}). Refusing to delete any other database.`);
}

// A DB_* variable already in the shell would win over .env.e2e inside Laravel.
for (const key of ['DB_CONNECTION', 'DB_DATABASE', 'DB_URL']) {
    if (process.env[key]) {
        fail(`${key} is set in the shell environment and would override .env.e2e. Unset it first.`);
    }
}

const artisan = (...args) => execFileSync('php', ['artisan', ...args, '--env=e2e'], { cwd: ROOT, stdio: 'inherit' });

if (!envValue(lines, 'APP_KEY')) {
    artisan('key:generate', '--force');
}

for (const suffix of ['', '-journal', '-wal', '-shm']) {
    rmSync(`${DB_PATH}${suffix}`, { force: true });
}
writeFileSync(DB_PATH, '');

artisan('migrate', '--seed', '--force');
console.log(`[e2e-reset] ${E2E_DATABASE} recreated and seeded.`);
