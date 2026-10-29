const { execFileSync } = require('node:child_process');
const { mkdirSync, readFileSync, rmSync } = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const PLUGIN_SLUG = 'file-manager';
const BUILD_DIR = path.join(ROOT, 'build');
const STAGE_DIR = path.join(BUILD_DIR, 'stage');

/** Reads the version from the plugin header, the version WordPress reports. */
function pluginVersion() {
  const header = readFileSync(path.join(ROOT, `${PLUGIN_SLUG}.php`), 'utf8');
  const match = header.match(/^\s*\*\s*Version:\s*(\S+)/m);
  if (!match) {
    throw new Error(`No "Version:" header found in ${PLUGIN_SLUG}.php`);
  }

  return match[1];
}

/** Zips build/stage/<slug> into build/<slug>-<version>/<slug>.zip with the system zip CLI, so entries nest under <slug>/. */
function zipBuild() {
  const outDir = path.join(BUILD_DIR, `${PLUGIN_SLUG}-${pluginVersion()}`);
  const outFile = path.join(outDir, `${PLUGIN_SLUG}.zip`);

  mkdirSync(outDir, { recursive: true });
  // zip updates an existing archive in place, which would keep files deleted since the last build.
  rmSync(outFile, { force: true });
  execFileSync('zip', ['-r', '-q', '-X', outFile, PLUGIN_SLUG], { cwd: STAGE_DIR, stdio: 'inherit' });
  console.log(`Zipped to ${path.relative(ROOT, outFile)}`);
}

zipBuild();
