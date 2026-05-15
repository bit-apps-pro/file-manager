const { FlatCompat } = require('@eslint/eslintrc')
const js = require('@eslint/js')
const globals = require('globals')
const tsParser = require('@typescript-eslint/parser')
const tsPlugin = require('@typescript-eslint/eslint-plugin')
const jsonPlugin = require('eslint-plugin-json')
const cypressPlugin = require('eslint-plugin-cypress')
const reactPlugin = require('eslint-plugin-react')

// Patch eslint-plugin-react in-place before FlatCompat registers it.
// Reason: react@7 rules call context.getFilename() which doesn't exist in
// ESLint 10 flat config. This polyfill adds the missing method.
for (const rule of Object.values(reactPlugin.rules || {})) {
  const create = typeof rule === 'function' ? rule : (rule && rule.create)
  if (!create) continue
  const patched = function patchedCreate(context) {
    if (typeof context.getFilename !== 'function') {
      const filename = context.filename
      context = Object.setPrototypeOf(
        { getFilename: () => filename, getPhysicalFilename: () => context.physicalFilename || filename },
        context
      )
    }
    return create.call(this, context)
  }
  if (typeof rule === 'function') {
    // rule IS the create function — can't replace (it's read from the plugin by reference)
    // skip — flat config registered rules go through the rule object form
  } else if (rule && typeof rule.create === 'function') {
    rule.create = patched
  }
}

const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended
})

// Strip rules removed from @typescript-eslint v8 that legacy configs still reference
const existingTsRules = new Set(Object.keys(tsPlugin.rules || {}))
function stripRemovedRules(configs) {
  return configs.map(cfg => {
    if (!cfg.rules) return cfg
    const rules = {}
    for (const [key, val] of Object.entries(cfg.rules)) {
      if (key.startsWith('@typescript-eslint/')) {
        const ruleName = key.slice('@typescript-eslint/'.length)
        if (existingTsRules.has(ruleName)) rules[key] = val
      } else {
        rules[key] = val
      }
    }
    return { ...cfg, rules }
  })
}

module.exports = [
  {
    ignores: [
      'node_modules/**',
      'build/**',
      'coverage/**',
      'frontend/finder-loader.js',
      'frontend/elfinder-editors.js',
      'frontend/src/config/devHotModule.js',
      'vite.config.ts',
      'commitlint.config.js'
    ]
  },
  js.configs.recommended,
  // airbnb + TypeScript + import + a11y + prettier via FlatCompat
  // (react plugin already patched above so FlatCompat will register the patched version)
  ...stripRemovedRules(compat.extends(
    'plugin:@typescript-eslint/recommended',
    'plugin:react/recommended',
    'plugin:react/jsx-runtime',
    'plugin:react-hooks/recommended',
    'plugin:jsx-a11y/recommended',
    'airbnb',
    'airbnb-typescript',
    'plugin:import/errors',
    'plugin:import/recommended',
    'plugin:import/typescript',
    'prettier',
    'plugin:prettier/recommended'
  )),
  // JSON + Cypress native flat configs
  jsonPlugin.configs.recommended,
  cypressPlugin.configs.recommended,
  // Project-specific settings and rule overrides
  {
    settings: {
      react: { version: '19.2.6' },
      'import/parsers': {
        '@typescript-eslint/parser': ['.ts', '.tsx', '.d.ts']
      },
      'import/resolver': {
        typescript: {
          alwaysTryTypes: true,
          project: './tsconfig.json'
        },
        node: {
          extensions: ['.js', '.jsx', '.ts', '.tsx']
        }
      }
    },
    languageOptions: {
      parser: tsParser,
      parserOptions: {
        project: './tsconfig.json'
      },
      globals: {
        ...globals.browser,
        ...globals.es2021,
        ...globals.commonjs,
        Atomics: 'readonly',
        SharedArrayBuffer: 'readonly'
      }
    },
    rules: {
      indent: 'off',
      semi: ['error', 'never'],
      camelcase: 'error',
      'react/require-default-props': [0, { functions: 'ignore' }],
      'template-curly-spacing': 'off',
      'react/jsx-filename-extension': [1, { extensions: ['.js', '.jsx', '.ts', '.tsx'] }],
      'react/destructuring-assignment': 0,
      'arrow-parens': 0,
      'react/prop-types': 0,
      'max-len': ['error', { code: 350 }],
      'linebreak-style': ['error', 'unix'],
      'react-hooks/exhaustive-deps': 'warn',
      'react/jsx-uses-react': 'off',
      'react/react-in-jsx-scope': 'off',
      'object-curly-newline': [
        'error',
        {
          ImportDeclaration: { consistent: true },
          ExportDeclaration: { consistent: true },
          ObjectPattern: { consistent: true },
          ObjectExpression: { consistent: true }
        }
      ],
      'array-callback-return': 'off',
      'consistent-return': 'off',
      'newline-per-chained-call': ['error', { ignoreChainWithDepth: 4 }],
      'import/no-unresolved': ['error', { ignore: ['elfinder', 'reactflow', '@total-typescript/ts-reset'] }],
      'import/no-duplicates': 'error',
      'import/no-self-import': 'error',
      'import/no-relative-packages': 'error',
      'import/no-relative-parent-imports': 'off',
      'import/consistent-type-specifier-style': ['error', 'prefer-inline'],
      '@typescript-eslint/consistent-type-imports': 'error',
      'import/no-empty-named-blocks': 'error',
      'import/no-extraneous-dependencies': 'error',
      'import/no-import-module-exports': 'error',
      'import/newline-after-import': 'error',
      'import/no-useless-path-segments': ['error', { noUselessIndex: true }],
      '@typescript-eslint/no-non-null-assertion': 'error',
      '@typescript-eslint/no-unused-vars': 'error',
      '@typescript-eslint/indent': 'off',
      '@typescript-eslint/semi': 'off',
      'prettier/prettier': 'warn',
      '@typescript-eslint/no-use-before-define': ['error', { functions: false, classes: false }],
      'react/no-unknown-property': ['error', { ignore: ['css'] }],
      'no-param-reassign': [
        'error',
        { props: true, ignorePropertyModificationsForRegex: ['(d|D)raft', 'this', '$'] }
      ]
    }
  }
]
