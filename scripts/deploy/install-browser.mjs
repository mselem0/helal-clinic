import {createRequire} from 'node:module';
import {dirname,join} from 'node:path';
import {spawnSync} from 'node:child_process';
const require=createRequire(import.meta.url);
const fromWeb=createRequire(require.resolve('@e2e-dev/web'));
const cli=join(dirname(fromWeb.resolve('playwright-core/package.json')),'cli.js');
const result=spawnSync(process.execPath,[cli,'install','--with-deps','chromium'],{stdio:'inherit'});
process.exit(result.status ?? 1);
