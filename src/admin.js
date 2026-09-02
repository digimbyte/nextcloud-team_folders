import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
const button = document.getElementById('team-folders-rebuild')
button?.addEventListener('click', async () => {
  button.disabled = true
  try { await axios.post(generateUrl('/apps/team_folders/api/v1/admin/rebuild')); document.querySelector('[data-field="status"]').textContent = 'queued' }
  finally { button.disabled = false }
})
