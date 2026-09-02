#!/usr/bin/env sh
set -eu

# Run on the Docker host. Credentials are generated for this run unless supplied.
NC_CONTAINER=${NC_CONTAINER:-inhouse-nextcloud-nextcloud-1}
NC_URL=${NC_URL:-http://127.0.0.1:8080}
OWNER=${TF_DEMO_OWNER:-tf_demo_owner}
RECIPIENT=${TF_DEMO_RECIPIENT:-tf_demo_recipient}
GROUP=${TF_DEMO_GROUP:-team_folders_demo}
OWNER_PASSWORD=${TF_DEMO_OWNER_PASSWORD:-$(openssl rand -hex 24)}
RECIPIENT_PASSWORD=${TF_DEMO_RECIPIENT_PASSWORD:-$(openssl rand -hex 24)}

occ() { docker exec -u www-data "$NC_CONTAINER" php occ "$@"; }
if ! occ user:info "$OWNER" >/dev/null 2>&1; then
  docker exec -u www-data -e OC_PASS="$OWNER_PASSWORD" "$NC_CONTAINER" php occ user:add --password-from-env --display-name="Team Folders Demo Owner" "$OWNER"
else
  docker exec -u www-data -e OC_PASS="$OWNER_PASSWORD" "$NC_CONTAINER" php occ user:resetpassword --password-from-env "$OWNER"
fi
if ! occ user:info "$RECIPIENT" >/dev/null 2>&1; then
  docker exec -u www-data -e OC_PASS="$RECIPIENT_PASSWORD" "$NC_CONTAINER" php occ user:add --password-from-env --display-name="Team Folders Demo Recipient" "$RECIPIENT"
else
  docker exec -u www-data -e OC_PASS="$RECIPIENT_PASSWORD" "$NC_CONTAINER" php occ user:resetpassword --password-from-env "$RECIPIENT"
fi
occ group:add "$GROUP" >/dev/null 2>&1 || true
occ group:adduser "$GROUP" "$OWNER"
occ user:setting "$OWNER" files quota '1 GB'

FOLDER_ID=${TF_DEMO_FOLDER_ID:-}
if [ -z "$FOLDER_ID" ]; then
  FOLDER_ID=$(occ groupfolders:create --output=json "Team Folders Demo" | tr -cd '0-9')
fi
occ groupfolders:group "$FOLDER_ID" "$GROUP" read write share delete

dav="$NC_URL/remote.php/dav/files/$OWNER/Team%20Folders%20Demo"
mkcol() { curl -sS -u "$OWNER:$OWNER_PASSWORD" -X MKCOL "$dav/$1" >/dev/null; }
put() { curl -fsS -u "$OWNER:$OWNER_PASSWORD" -T /dev/null "$dav/$1" >/dev/null; }
echo 'Creating demo hierarchy'
mkcol '00%20Private'
mkcol '10%20Direct%20People'
mkcol '20%20Protected%20Link'
mkcol '30%20Public%20Link'
mkcol '40%20Mixed'; mkcol '40%20Mixed/Child%20People'; mkcol '40%20Mixed/Child%20Public'
mkcol '50%20Deep'; mkcol '50%20Deep/Level%201'; mkcol '50%20Deep/Level%201/Level%202'
echo 'Creating public-file fixture'
put '50%20Deep/Level%201/Level%202/Public%20File.txt'
mkcol '60%20Public%20Parent'; mkcol '60%20Public%20Parent/Child'

ocs="$NC_URL/ocs/v2.php/apps/files_sharing/api/v1/shares"
share() { curl -fsS -u "$OWNER:$OWNER_PASSWORD" -H 'OCS-APIRequest: true' -H 'Accept: application/json' -X POST "$ocs" "$@" >/dev/null; }
echo 'Creating demo shares'
share --data-urlencode 'path=/Team Folders Demo/10 Direct People' --data 'shareType=0' --data-urlencode "shareWith=$RECIPIENT" --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/20 Protected Link' --data 'shareType=3' --data-urlencode "password=$RECIPIENT_PASSWORD" --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/30 Public Link' --data 'shareType=3' --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/40 Mixed/Child People' --data 'shareType=0' --data-urlencode "shareWith=$RECIPIENT" --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/40 Mixed/Child Public' --data 'shareType=3' --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/50 Deep/Level 1/Level 2/Public File.txt' --data 'shareType=3' --data 'permissions=1'
share --data-urlencode 'path=/Team Folders Demo/60 Public Parent' --data 'shareType=3' --data 'permissions=1'

occ files:scan "$OWNER" >/dev/null
occ team-folders:rebuild
echo "Demo folder $FOLDER_ID provisioned. Credentials were not persisted."
