<?= '<?php' ?>

namespace PHPSTORM_META {

/**
* PhpStorm Meta file, to provide autocomplete information for PhpStorm
* Generated on <?= date("Y-m-d H:i:s") ?>.
*
* @author DuxPHP <admin@duxphp.com>
*/
<?php foreach ($methods as $method => $list): ?>
    override(<?= $method ?>, map([
    '' => '@',
    <?php foreach($list as $vo): ?>
    '<?= $vo['name'] ?>' => \<?= $vo['class'] ?>::class,
    <?php endforeach; ?>
    ]));
<?php endforeach; ?>

}