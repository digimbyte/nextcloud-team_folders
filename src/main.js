import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { evaluateHealth } from './health.js'

const ICONS = {
  people: '👤',
  link: '🔗',
  public: '🌐',
  federated: '☁️',
  other: '◆',
}
const LABELS = {
  people: 'Shared with people',
  link: 'Link shared',
  public: 'Public',
  federated: 'Federated',
  other: 'Other',
}

let requestGeneration = 0
let timer
let healthRetry = 0

function currentDirectory() { return new URL(window.location.href).searchParams.get('dir') || '/' }

function overlay(kind, ghost) {
  const node = document.createElement('span')
  node.className = `team-folders__overlay team-folders__overlay--${kind}${ghost ? ' team-folders__overlay--ghost' : ''}`
  node.setAttribute('role', 'img')
  node.title = LABELS[kind] || LABELS.other
  node.setAttribute('aria-label', node.title)
  node.textContent = ICONS[kind] || ICONS.other
  return node
}

function rows() { return document.querySelectorAll('tr[data-cy-files-list-row], tr[data-file], .files-list__row') }
function rowName(row) { return row.dataset.file || row.querySelector('[data-cy-files-list-row-name], .files-list__row-name')?.textContent?.trim() }
function iconHost(row) { return row.querySelector('[data-cy-files-list-row-icon], .files-list__row-icon, .thumbnail, .files-list__row-icon-container') }
function hasNativeCollaborationIcon(host) {
  return /folder-(shared|public|group)|icon-(shared|group)|groupfolder|team-folder/i.test(host.outerHTML)
}

function plainFolder() {
  const node = document.createElement('span')
  node.className = 'team-folders__plain-folder'
  node.setAttribute('aria-hidden', 'true')
  node.innerHTML = '<svg viewBox="0 0 32 32"><path d="M2 8a4 4 0 0 1 4-4h7l3 4h10a4 4 0 0 1 4 4v12a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4Z"/></svg>'
  return node
}

function checkRenderedHealth(items, apiStatus) {
  const visibleRows = [...rows()]
  let matchedRows = 0
  let expectedBases = 0
  let expectedOverlays = 0
  for (const row of visibleRows) {
    const state = items[rowName(row)]
    if (!state) continue
    matchedRows++
    const host = iconHost(row)
    const hasExposure = (state.solid?.length || 0) + (state.ghost?.length || 0) > 0
    if (hasExposure || (host && hasNativeCollaborationIcon(host))) expectedBases++
    expectedOverlays += (state.solid?.length || 0) + (state.ghost?.length || 0)
  }
  const detail = {
    ...evaluateHealth({
      apiStatus,
      itemCount: Object.keys(items).length,
      matchedRows,
      expectedBases,
      actualBases: document.querySelectorAll('.team-folders__plain-folder').length,
      expectedOverlays,
      actualOverlays: document.querySelectorAll('.team-folders__overlay').length,
    }),
    apiStatus,
    itemCount: Object.keys(items).length,
    matchedRows,
    checkedAt: new Date().toISOString(),
  }
  window.__teamFoldersHealth = detail
  document.documentElement.dataset.teamFoldersHealth = detail.ok ? 'healthy' : 'degraded'
  window.dispatchEvent(new CustomEvent('team-folders:health', { detail }))
  if (!detail.ok) console.error('Team Folders post-render health check failed', detail)
  return detail.ok
}

function showHealthFailure(show) {
  document.querySelector('.team-folders__health-error')?.remove()
  if (!show) return
  const warning = document.createElement('div')
  warning.className = 'team-folders__health-error'
  warning.setAttribute('role', 'alert')
  warning.textContent = 'Team Folders indicators failed to render'
  document.body.append(warning)
}

function decorate(items, apiStatus) {
  observer.disconnect()
  try {
    for (const row of rows()) {
      row.querySelector('.team-folders')?.remove()
      row.querySelector('.team-folders__plain-folder')?.remove()
      const state = items[rowName(row)]
      if (!state) continue
      const host = iconHost(row)
      if (!host) continue
      // The index response contains every visible folder. Cover Nextcloud's
      // native shared/Groupfolder glyph with the neutral base icon even when
      // this folder has no exposure flags.
      const hasExposure = (state.solid?.length || 0) > 0 || (state.ghost?.length || 0) > 0
      if (hasExposure || hasNativeCollaborationIcon(host)) host.append(plainFolder())
      if (!hasExposure) continue
      const wrap = document.createElement('span')
      wrap.className = 'team-folders'
      for (const kind of state.solid || []) wrap.append(overlay(kind, false))
      for (const kind of state.ghost || []) wrap.append(overlay(kind, true))
      host.append(wrap)
    }
  } finally {
    observe()
    requestAnimationFrame(() => {
      const healthy = checkRenderedHealth(items, apiStatus)
      if (!healthy && healthRetry++ < 1) setTimeout(refresh, 500)
      else if (healthy) { healthRetry = 0; showHealthFailure(false) }
      else showHealthFailure(true)
    })
  }
}

async function refresh() {
  const mine = ++requestGeneration
  try {
    const { data } = await axios.get(generateUrl('/apps/team_folders/api/v1/indicators'), { params: { dir: currentDirectory() } })
    if (mine === requestGeneration) decorate(data.items || {}, data.health?.status || 'unknown')
  } catch (error) {
    window.__teamFoldersHealth = { ok: false, checks: { api: false }, error: String(error), checkedAt: new Date().toISOString() }
    document.documentElement.dataset.teamFoldersHealth = 'degraded'
    console.error('Team Folders API health check failed', error)
    showHealthFailure(true)
  }
}

const observer = new MutationObserver(() => { clearTimeout(timer); timer = setTimeout(refresh, 150) })
function observe() { observer.observe(document.body, { subtree: true, childList: true }) }
function scheduleRefresh() { clearTimeout(timer); timer = setTimeout(refresh, 20) }

observe()
window.addEventListener('popstate', scheduleRefresh)
window.addEventListener('hashchange', scheduleRefresh)
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', refresh, { once: true })
else refresh()
document.addEventListener('files:list:updated', scheduleRefresh)
document.addEventListener('nextcloud:files:navigation', scheduleRefresh)
