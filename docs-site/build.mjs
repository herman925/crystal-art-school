import { spawnSync } from 'node:child_process'
import { copyFileSync, cpSync, existsSync, lstatSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, realpathSync, rmSync, symlinkSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const here = dirname(fileURLToPath(import.meta.url))
const site = join(here, 'site')
const output = join(site, '.vitepress', 'dist')

if (process.env.DOCS_BUILD_WORKER === '1') {
  const audience = process.env.DOCS_AUDIENCE
  if (!['crystal', 'admin'].includes(audience)) throw new Error('Unknown guide audience')
  const { build } = await import('vitepress')
  let temporary
  try {
    temporary = mkdtempSync(join(tmpdir(), 'cas-guide-'))
    const source = join(temporary, 'site')
    cpSync(site, source, { recursive: true, filter: path => !path.startsWith(output) })
    const pictures = join(source, 'public', 'guide-screenshots')
    mkdirSync(pictures, { recursive: true })
    const referenced = new Set(readdirSync(site).filter(name => name.endsWith('.md')).flatMap(name =>
      [...readFileSync(join(site, name), 'utf8').matchAll(/\/guide-screenshots\/([a-z0-9-]+\.png)/g)].map(match => match[1])))
    for (const name of referenced) {
      const picture = join(process.env.DOCS_SCREENSHOT_SOURCE, name)
      if (!lstatSync(picture).isFile() || lstatSync(picture).isSymbolicLink()) throw new Error('Screenshots must be regular PNG files')
      copyFileSync(picture, join(pictures, name))
    }
    if (audience === 'admin') {
      cpSync(process.env.DOCS_ADMIN_SOURCE, join(source, 'admin'), { recursive: true })
    }
    symlinkSync(realpathSync(join(here, 'node_modules')), join(temporary, 'node_modules'), 'dir')
    await build(source, { outDir: join(output, audience) })
  } finally {
    if (temporary) rmSync(temporary, { recursive: true, force: true })
  }
} else {
  if (existsSync(join(site, 'admin'))) throw new Error('Keep administrator Markdown outside the public repository')
  if (existsSync(join(site, 'public', 'guide-screenshots'))) throw new Error('Keep backend screenshots outside the public repository')
  if (!process.env.DOCS_ADMIN_SOURCE || !existsSync(join(process.env.DOCS_ADMIN_SOURCE, 'index.md'))) {
    throw new Error('Set DOCS_ADMIN_SOURCE to the private administrator Markdown directory')
  }
  if (!process.env.DOCS_SCREENSHOT_SOURCE || !existsSync(process.env.DOCS_SCREENSHOT_SOURCE)) {
    throw new Error('Set DOCS_SCREENSHOT_SOURCE to the private backend screenshot directory')
  }
  rmSync(output, { recursive: true, force: true })
  // Separate processes keep page/search/module caches from crossing audience builds.
  for (const audience of ['crystal', 'admin']) {
    const result = spawnSync(process.execPath, [fileURLToPath(import.meta.url)], {
      cwd: here,
      env: { ...process.env, DOCS_BUILD_WORKER: '1', DOCS_AUDIENCE: audience },
      stdio: 'inherit'
    })
    if (result.status !== 0) process.exit(result.status || 1)
  }
}
