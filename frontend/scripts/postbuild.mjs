import fs from 'node:fs';
import path from 'node:path';

const outputRoot = path.resolve(process.cwd(), '..', 'public', 'spa');
const browserDir = path.join(outputRoot, 'browser');

if (!fs.existsSync(browserDir)) {
  process.exit(0);
}

for (const entry of fs.readdirSync(browserDir)) {
  fs.cpSync(path.join(browserDir, entry), path.join(outputRoot, entry), {
    force: true,
    recursive: true,
  });
}
