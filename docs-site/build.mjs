import { spawnSync } from 'node:child_process'
import { cpSync, existsSync, mkdtempSync, realpathSync, rmSync, symlinkSync } from 'node:fs'
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
    let source = site
    if (audience === 'admin') {
      temporary = mkdtempSync(join(tmpdir(), 'cas-admin-guide-'))
      source = join(temporary, 'site')
      cpSync(site, source, { recursive: true, filter: path => !path.startsWith(output) })
      cpSync(process.env.DOCS_ADMIN_SOURCE, join(source, 'admin'), { recursive: true })
      symlinkSync(realpathSync(join(here, 'node_modules')), join(temporary, 'node_modules'), 'dir')
    }
    await build(source, { outDir: join(output, audience) })
  } finally {
    if (temporary) rmSync(temporary, { recursive: true, force: true })
  }
} else {
  if (existsSync(join(site, 'admin'))) throw new Error('Keep administrator Markdown outside the public repository')
  if (!process.env.DOCS_ADMIN_SOURCE || !existsSync(join(process.env.DOCS_ADMIN_SOURCE, 'index.md'))) {
    throw new Error('Set DOCS_ADMIN_SOURCE to the private administrator Markdown directory')
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
