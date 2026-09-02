<?php
$status = $_['status'];
script('team_folders', 'team-folders-admin');
style('team_folders', 'admin');
?>
<div id="team-folders-admin" class="section">
 <h2><?= p($l->t('Team Folders')) ?></h2>
 <p><?= p($l->t('Recursive share exposure index health. Cached indicators remain available while an index repair is running.')) ?></p>
 <dl class="team-folders-admin__status">
  <dt><?= p($l->t('State')) ?></dt><dd data-field="status"><?= p($status['status']) ?></dd>
  <dt><?= p($l->t('Last successful reconciliation')) ?></dt><dd><?= $status['lastSuccess'] ? p(date('c', $status['lastSuccess'])) : p($l->t('Never')) ?></dd>
  <dt><?= p($l->t('Indexed shares')) ?></dt><dd><?= p((string)$status['shares']) ?></dd>
  <dt><?= p($l->t('Indexed nodes')) ?></dt><dd><?= p((string)$status['nodes']) ?></dd>
  <dt><?= p($l->t('Generation')) ?></dt><dd><?= p((string)$status['generation']) ?></dd>
  <?php if ($status['lastError'] !== null): ?><dt><?= p($l->t('Last error')) ?></dt><dd><?= p($status['lastError']) ?></dd><?php endif; ?>
 </dl>
 <button type="button" id="team-folders-rebuild" class="primary"><?= p($l->t('Rebuild exposure index')) ?></button>
</div>
