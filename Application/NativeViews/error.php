<?php $pageTitle = $title ?? 'Unavailable'; ?>
<section class="content-section"><div class="empty-state"><strong><?= rc_e($title ?? 'Unavailable') ?></strong><?= rc_e($message ?? 'This page could not be loaded.') ?><p><a class="button" href="<?= rc_url('home') ?>">Return home</a></p></div></section>
