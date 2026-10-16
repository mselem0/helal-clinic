import type { E2EConfig } from 'e2e';
import { web } from '@e2e-dev/web';
import { mkdtempSync, mkdirSync, copyFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
const temp = mkdtempSync(join(tmpdir(), 'helal-e2e-'));
copyFileSync('modules_statuses.json', join(temp, 'modules.json'));
for (const path of ['framework/cache/data', 'framework/sessions', 'framework/views', 'logs', 'app/public']) mkdirSync(join(temp, 'storage', path), { recursive: true });
export default {
  targets: [{ name: 'clinic', engine: web({ viewport: { width: 1440, height: 1000 }, locale: 'ar-EG', timezoneId: 'Africa/Cairo' }),
    app: { url: 'http://127.0.0.1:0', environment: 'test', readyUrl: 'http://127.0.0.1:{port}/up',
      command: { executable: 'php', args: ['tests/e2e-support/serve.php', '{port}'],
        env: { APP_ENV: 'testing', CLINIC_E2E: '1', CLINIC_MODULE_STATUSES: join(temp, 'modules.json'), APP_DEBUG: 'false', APP_KEY: 'base64:MDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA=', APP_URL: 'http://127.0.0.1:{port}', ASSET_URL: 'http://127.0.0.1:{port}', DB_CONNECTION: 'sqlite', DB_DATABASE: join(temp, 'database.sqlite'), LARAVEL_STORAGE_PATH: join(temp, 'storage'), SESSION_DRIVER: 'file', CACHE_STORE: 'array', QUEUE_CONNECTION: 'sync', MAIL_MAILER: 'array', AI_ENABLED: 'false', WHATSAPP_ENABLED: 'false', GOOGLE_ANALYTICS_ENABLED: 'false' },
        log: '.e2e/logs/app.log' } } }],
  credentials: { admin: { username: 'admin@example.test', password: 'E2E-Only-Password-123!' }, visitor: { username: 'visitor@example.test', password: 'E2E-Only-Password-123!' } },
  timeout: 60000, actionTimeout: 10000,
  workers: 1,
} satisfies E2EConfig;
