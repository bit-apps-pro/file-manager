/// <reference types="vite/client" />
import react from '@vitejs/plugin-react'
import detectPort from 'detect-port'
import fs from 'node:fs'
import { type AddressInfo } from 'node:net'
import path from 'node:path'
import csso from 'postcss-csso'
import { type Alias, type Plugin } from 'vite'
import { defineConfig, normalizePath } from 'vite'
import { viteStaticCopy } from 'vite-plugin-static-copy'

import * as tsconfig from './tsconfig.json'

// const nextId = incstr.idGenerator()
let chunkCount = 0
function hash() {
  return Math.round(Math.random() * (999 - 1) + 1)
}

function readAliasFromTsConfig(): Alias[] {
  // eslint-disable-next-line prefer-regex-literals
  const pathReplaceRegex = new RegExp(/\/\*$/, '')
  return Object.entries(tsconfig.compilerOptions.paths).reduce((aliases, [fromPaths, toPaths]) => {
    const find = fromPaths.replace(pathReplaceRegex, '')
    // @ts-ignore
    const toPath = toPaths[0].replace(pathReplaceRegex, '')
    const replacement = path.resolve(__dirname, toPath)
    aliases.push({ find, replacement })
    return aliases
  }, [] as Alias[])
}

const getVersion = () => {
  let version = '1.0.0'
  if (fs.existsSync('readme.txt')) {
    const readme = fs.readFileSync('readme.txt').toString()
    const regex = /Stable\s+tag:\s+(\d+\.\d+(\.?\d+)*)/
    const match = readme.match(regex)
    version = match ? match[1] : '1.0.0'
  }
  return version
}

// @ts-ignore
export default defineConfig(({ mode }) => {
  // const isProd = mode === 'production'
  const folderName = path.basename(process.cwd())
  return {
    root: 'frontend/src',
    base: mode === 'development' ? `/wp-content/plugins/${folderName}/frontend/src/` : '',
    plugins: [
      react({
        jsxImportSource: '@emotion/react',
        jsxRuntime: 'automatic'
        // @ts-ignore
        // fastRefresh: true,
      }),
      setDevServerConfig(),
      viteStaticCopy({
        targets: [
          {
            src: normalizePath(path.resolve(__dirname, 'frontend/finder-loader.js')),
            dest: 'js'
          },
          {
            src: normalizePath(path.resolve(__dirname, 'frontend/style')),
            dest: '../../assets'
          }
        ]
      }),
      {
        name: 'generate-elfinder-editors',
        writeBundle() {
          const src = path.resolve(__dirname, 'vendor/studio-42/elfinder/js/extras/editors.default.min.js')
          const dest = path.resolve(__dirname, 'assets/js/elfinder-editors.js')
          const content = fs.readFileSync(src, 'utf8')
          fs.writeFileSync(dest, `(function($){${content}})(jQuery);`)
        }
      }
    ],
    oxc: {
      drop: mode === 'development' ? [] : ['console', 'debugger']
    },
    css: {
      postcss: {
        plugins: [csso()]
      }
    },
    resolve: { alias: readAliasFromTsConfig() },

    build: {
      outDir: '../../assets/js',
      emptyOutDir: true,
      // assetsDir: './',
      rollupOptions: {
        input: path.resolve(__dirname, 'frontend/src/main.tsx'),
        output: {
          entryFileNames: `main.${getVersion()}.js`,
          manualChunks(id: string) {
            if (id.includes('/node_modules/react/') || id.includes('/node_modules/react-dom/')) {
              return 'react-vendor'
            }
            if (id.includes('/node_modules/@emotion/react/')) {
              return '@emotion/react'
            }
            if (id.includes('/node_modules/@tanstack/react-query-devtools/')) {
              return '@tanstack/react-query-devtools'
            }
            if (id.includes('/node_modules/@tanstack/react-query/')) {
              return '@tanstack/react-query'
            }
            if (id.includes('/node_modules/react-router-dom/') || id.includes('/node_modules/react-router/')) {
              return 'react-router-dom'
            }
            if (id.includes('/node_modules/antd/') || id.includes('/node_modules/rc-')) {
              return 'antd'
            }
            return undefined
          },
          chunkFileNames: () => '[name]-[hash].js',

          assetFileNames: fInfo => {
            const pathArr = fInfo?.name?.split('/')
            const fileName = pathArr?.[pathArr.length - 1]

            // console.log(fInfo.name, fileName)

            if (fileName === 'main.css') {
              return `main.${getVersion()}.css`
            }
            if (fileName === 'logo.svg') {
              return 'logo.svg'
            }

            return `bf-${hash()}-${chunkCount++}.[ext]`
          }
        }
      }
    },
    test: {
      // globals: true,
      environment: 'jsdom',
      setupFiles: './config/test.setup.ts'
      // since parsing CSS is slow
      // css: true,
    },
    server: {
      cors: true, // required to load scripts from custom host
      port: 3000,
      hmr: { host: 'localhost' }
      // commonjsOptions: { transformMixedEsModules: true },
    }
  }
})

function setDevServerConfig(): Plugin {
  return {
    name: 'vite-plugin-set-dev-server-config',
    async config(_, env) {
      if (env?.mode === 'development') {
        let port = getStoredPort()
        if (!port) {
          port = await detectPort(3000).then((detectedPort: number) => detectedPort)
          updateStoredPort(port)
        }
        return { server: { port, origin: `http://localhost:${port}` } }
      }
      removeStoredPort()
    },
    configureServer(server) {
      if (server.httpServer) {
        server.httpServer.once('listening', () => {
          const { port } = server.httpServer?.address() as AddressInfo
          const storedPort = getStoredPort()
          if (port !== storedPort) {
            updateStoredPort(port)
          }
        })

        server.watcher.add(['port'])

        server.watcher.on('change', (file: string) => {
          if (file === 'port') {
            server.config.logger.warnOnce('Server restarting for origin mismatch', { timestamp: true })
            server.restart()
          }
        })

        server.httpServer.close(() => {
          server.watcher.close()
          removeStoredPort()
        })
      }
    }
  }
}

const portFile = path.resolve(__dirname, './port')

function getStoredPort() {
  let port = 0
  if (fs.existsSync(portFile)) {
    port = Number(fs.readFileSync(portFile))
  }

  return port
}

function updateStoredPort(port: number) {
  fs.writeFileSync(portFile, String(port))
}

function removeStoredPort() {
  if (fs.existsSync(portFile)) {
    fs.rmSync(portFile)
  }
}
