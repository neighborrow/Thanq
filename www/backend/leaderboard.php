<?php
	$leaders = $System->KarmaHandler->getLeaders(50);
?>
<ol>
<?php foreach($leaders as $user): ?>
	<li><a href="/user/<?= $user->id ?>"><?= $user->display ?></a> (<?= $user->getKarmaCountDisplay() ?>)</li>
<?php endforeach; ?>
</ol>