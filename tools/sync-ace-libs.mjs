#!/usr/bin/env node
// Copies snippet files from the installed `ace-builds` package into
// `frontend/libs/ace/snippets/` so Ace can resolve `<cdn>/snippets/<mode>.js`
// at runtime without 404s. The vite static-copy plugin then mirrors
// `frontend/libs` → `assets/libs` during build.
import { createRequire } from 'node:module'
import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const require = createRequire(import.meta.url)
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')

const acePkg = require.resolve('ace-builds/package.json')
const aceRoot = path.dirname(acePkg)
const snippetsSrc = path.join(aceRoot, 'src-min-noconflict', 'snippets')
const snippetsDest = path.join(root, 'frontend', 'libs', 'ace', 'snippets')

if (!fs.existsSync(snippetsSrc)) {
  console.error(`[sync-ace-libs] snippets source not found: ${snippetsSrc}`)
  process.exit(1)
}

fs.mkdirSync(snippetsDest, { recursive: true })
fs.cpSync(snippetsSrc, snippetsDest, { recursive: true, force: true })

const count = fs.readdirSync(snippetsDest).length
console.log(`[sync-ace-libs] copied ${count} files → ${path.relative(root, snippetsDest)}`)
