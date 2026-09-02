#!/usr/bin/env sh
set -eu
NC_CONTAINER=${NC_CONTAINER:-inhouse-nextcloud-nextcloud-1}
NC_URL=${NC_URL:-http://127.0.0.1:8080}
OWNER=${TF_DEMO_OWNER:-tf_demo_owner}
PASSWORD=$(openssl rand -hex 24)
docker exec -u www-data -e OC_PASS="$PASSWORD" "$NC_CONTAINER" php occ user:resetpassword --password-from-env "$OWNER" >/dev/null
response=$(curl -fsS -u "$OWNER:$PASSWORD" -H 'OCS-APIRequest: true' --get "$NC_URL/index.php/apps/team_folders/api/v1/indicators" --data-urlencode 'dir=/Team Folders Demo')
printf '%s\n' "$response"
printf '%s' "$response" | grep -q '"00 Private":{"solid":\[\],"ghost":\[\]}'
printf '%s' "$response" | grep -q '"10 Direct People":{"solid":\["people"\]'
printf '%s' "$response" | grep -q '"20 Protected Link":{"solid":\["link"\]'
printf '%s' "$response" | grep -q '"30 Public Link":{"solid":\["link","public"\]'
printf '%s' "$response" | grep -q '"40 Mixed":{"solid":\[\],"ghost":\["people","link","public"\]}'
printf '%s' "$response" | grep -q '"50 Deep":{"solid":\[\],"ghost":\["link","public"\]}'
printf '%s' "$response" | grep -q '"60 Public Parent":{"solid":\["link","public"\]'
echo 'Demo exposure assertions passed.'
