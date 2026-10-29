const { mkdirSync, readFileSync } = require('node:fs');
const path = require('node:path');
const make_zip = require('zip-local');

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

/** Zips build/stage (holding <slug>/) into build/<slug>-<version>/<slug>.zip, so entries nest under <slug>/. */
function zipBuild() {
  const outDir = path.join(BUILD_DIR, `${PLUGIN_SLUG}-${pluginVersion()}`);
  const outFile = path.join(outDir, `${PLUGIN_SLUG}.zip`);

  mkdirSync(outDir, { recursive: true });
  make_zip.sync.zip(STAGE_DIR).compress().save(outFile);
  console.log(`Zipped to ${path.relative(ROOT, outFile)}`);
}

zipBuild();
