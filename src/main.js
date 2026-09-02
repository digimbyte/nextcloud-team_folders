import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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

function decorate(items) {
  observer.disconnect()
  try {
    for (const row of rows()) {
      row.querySelector('.team-folders')?.remove()
      const state = items[rowName(row)]
      if (!state || (!(state.solid?.length) && !(state.ghost?.length))) continue
      const host = iconHost(row)
      if (!host) continue
      const wrap = document.createElement('span')
      wrap.className = 'team-folders'
      for (const kind of state.solid || []) wrap.append(overlay(kind, false))
      for (const kind of state.ghost || []) wrap.append(overlay(kind, true))
      host.append(wrap)
    }
  } finally { observe() }
}

async function refresh() {
  const mine = ++requestGeneration
  try {
    const { data } = await axios.get(generateUrl('/apps/team_folders/api/v1/indicators'), { params: { dir: currentDirectory() } })
    if (mine === requestGeneration) decorate(data.items || {})
  } catch (error) { console.warn('Team Folders could not load indicators', error) }
}

const observer = new MutationObserver(() => { clearTimeout(timer); timer = setTimeout(refresh, 150) })
function observe() { observer.observe(document.body, { subtree: true, childList: true }) }
function scheduleRefresh() { clearTimeout(timer); timer = setTimeout(refresh, 20) }

observe()
window.addEventListener('popstate', scheduleRefresh)
window.addEventListener('hashchange', scheduleRefresh)
document.addEventListener('DOMContentLoaded', refresh, { once: true })
document.addEventListener('files:list:updated', scheduleRefresh)
document.addEventListener('nextcloud:files:navigation', scheduleRefresh)
