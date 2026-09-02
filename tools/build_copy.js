const copy = require('recursive-copy');
const { mkdirSync } = require('node:fs');
const path = require('node:path');

// Copy files
const options = {
    overwrite: true,
    filter: [
        // '**/*',
        'assets/**/*',
        'backend/**/*',
        'languages/**/*',
        'vendor/**/*',
        '!vendor/typisttech/**/*',
        'views/**/*',
        'file-manager.php',
        'license.txt',
        'readme.txt',
        'composer.json',
    ]
};

const buildPath = path.join('build', 'file-manager');
mkdirSync(buildPath, { recursive: true });

copy('.', buildPath, options)
    .on(copy.events.COPY_FILE_START, (copyOperation) => {
        console.info(`Copying file ${copyOperation.src}...`);
    })
    .on(copy.events.COPY_FILE_COMPLETE, (copyOperation) => {
        console.info(`Copied to ${copyOperation.dest}`);
    })
    .on(copy.events.ERROR, (error, copyOperation) => {
        console.error(`Unable to copy ${copyOperation.dest}`);
    })
    .then((results) => {
        console.info(`${results.length} file(s) copied`);
    })
    .catch((error) => {
        console.error(`Copy failed: ${error}`);
        process.exitCode = 1;
    });
