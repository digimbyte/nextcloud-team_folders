#!/usr/bin/env sh
set -eu
NC_CONTAINER=${NC_CONTAINER:-inhouse-nextcloud-nextcloud-1}
NC_URL=${NC_URL:-http://127.0.0.1:8080}
OWNER=${TF_DEMO_OWNER:-tf_demo_owner}
PASSWORD=$(openssl rand -hex 24)
docker exec -u www-data -e OC_PASS="$PASSWORD" "$NC_CONTAINER" php occ user:resetpassword --password-from-env "$OWNER" >/dev/null
dav="$NC_URL/remote.php/dav/files/$OWNER/Team%20Folders%20Demo/70%20Event%20Test"
curl -sS -u "$OWNER:$PASSWORD" -X DELETE "$dav" >/dev/null
curl -sS -u "$OWNER:$PASSWORD" -X MKCOL "$dav" >/dev/null
ocs="$NC_URL/ocs/v2.php/apps/files_sharing/api/v1/shares"
created=$(curl -fsS -u "$OWNER:$PASSWORD" -H 'OCS-APIRequest: true' -H 'Accept: application/json' -X POST "$ocs" --data-urlencode 'path=/Team Folders Demo/70 Event Test' --data 'shareType=3' --data 'permissions=1')
share_id=$(printf '%s' "$created" | sed -n 's/.*"id":"*\([0-9][0-9]*\)"*.*/\1/p')
test -n "$share_id"
echo 'Temporary public share created'
indicators() { curl -fsS -u "$OWNER:$PASSWORD" -H 'OCS-APIRequest: true' --get "$NC_URL/index.php/apps/team_folders/api/v1/indicators" --data-urlencode 'dir=/Team Folders Demo'; }
current=$(indicators); printf '%s\n' "$current"; printf '%s' "$current" | grep -q '"70 Event Test":{"solid":\["link","public"\]'
curl -fsS -u "$OWNER:$PASSWORD" -H 'OCS-APIRequest: true' -X DELETE "$ocs/$share_id" >/dev/null
echo 'Temporary public share deleted'
current=$(indicators); printf '%s\n' "$current"; printf '%s' "$current" | grep -q '"70 Event Test":{"solid":\[\],"ghost":\[\]}'
curl -fsS -u "$OWNER:$PASSWORD" -X DELETE "$dav" >/dev/null
echo 'Immediate share create/delete assertions passed.'
