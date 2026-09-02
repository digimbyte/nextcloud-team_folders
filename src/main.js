import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const ICONS = {
  people: ['👤', 'Shared with people'],
  link: ['🔗', 'Link shared'],
  public: ['🌐', 'Publicly accessible'],
  federated: ['☁', 'Federated share'],
  other: ['◆', 'Other share type'],
}

let generation = 0

function currentDirectory() {
  const url = new URL(window.location.href)
  return url.searchParams.get('dir') || '/'
}

function badge(kind, nested) {
  const [glyph, text] = ICONS[kind] || ICONS.other
  const node = document.createElement('span')
  node.className = `team-folders__badge team-folders__badge--${kind}${nested ? ' team-folders__badge--nested' : ''}`
  node.textContent = glyph
  node.title = nested ? `Contains nested item: ${text}` : text
  node.setAttribute('aria-label', node.title)
  return node
}

function findRows() {
  return document.querySelectorAll('tr[data-cy-files-list-row], tr[data-file], .files-list__row')
}

function rowName(row) {
  return row.dataset.file || row.querySelector('[data-cy-files-list-row-name], .files-list__row-name')?.textContent?.trim()
}

function decorate(items) {
  // Decorating mutates the file list; pause observation to avoid a request loop.
  observer.disconnect()
  try {
    for (const row of findRows()) {
      const state = items[rowName(row)]
      row.querySelector('.team-folders')?.remove()
      if (!state || (!state.direct.length && !state.nested.length)) continue
      const host = row.querySelector('[data-cy-files-list-row-name], .files-list__row-name, .filename')
      if (!host) continue
      const wrap = document.createElement('span')
      wrap.className = `team-folders${state.stale ? ' team-folders--stale' : ''}`
      for (const kind of state.direct) wrap.append(badge(kind, false))
      for (const kind of state.nested.filter((kind) => !state.direct.includes(kind))) wrap.append(badge(kind, true))
      host.append(wrap)
    }
  } finally {
    observeFiles()
  }
}

async function refresh() {
  const mine = ++generation
  try {
    const { data } = await axios.get(generateUrl('/apps/team_folders/api/v1/indicators'), { params: { dir: currentDirectory() } })
    if (mine === generation) decorate(data.items || {})
  } catch (error) {
    console.warn('Team Folders could not load indicators', error)
  }
}

let timer
const observer = new MutationObserver(() => {
  clearTimeout(timer)
  timer = setTimeout(refresh, 80)
})

function observeFiles() {
  observer.observe(document.body, { subtree: true, childList: true })
}

observeFiles()
window.addEventListener('popstate', refresh)
document.addEventListener('DOMContentLoaded', refresh, { once: true })
