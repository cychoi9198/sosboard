<?php
declare(strict_types=1);

use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;
?>
<h2><?= View::e(I18n::t('not_found_title')) ?></h2>
<p><?= View::e(I18n::t('not_found_body')) ?></p>
<p><a href="<?= View::e(Url::to('board')) ?>"><?= View::e(I18n::t('view_back_to_list')) ?></a></p>
