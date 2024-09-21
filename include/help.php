<?php
$dir = dirname(__FILE__).'/../';
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>

<div class="top-bar mb-2">
    <div class="top-bar__title"><svg><use href="assets/img/sprite.svg#question" /></svg> Справка</div>
</div>

<div class="row help content">
    <div class="col">

<?php
$opts = stream_context_create(array(
    'http' => array(
        'timeout' => 1
        )
    ));
$xmlstr = @file_get_contents('http://xml.tormon.ru/help.xml', false, $opts);
$xml = @simplexml_load_string($xmlstr);
if (false !== $xml)
{
    ?>
    <div x-data="{
        current: 'help',

        change(item, el) {
            this.current = item
        },
    }">
        <article class="collapsar__item" :class="{'--expanded': current == 'help'}" @click="change('help', $el)">
            <div class="collapsar__title">Помощь</div>
            <div class="collapsar__content" x-show="current=='help'" x-collapse><?php echo $xml->help ?></div>
        </article>
        <article class="collapsar__item" :class="{'--expanded': current == 'about'}" @click="change('about', $el)">
            <div class="collapsar__title">О проекте</div>
            <div class="collapsar__content" x-cloak x-show="current=='about'" x-collapse><?php echo $xml->about ?></div>
        </article>
        <article class="collapsar__item" :class="{'--expanded': current == 'devs'}" @click="change('devs', $el)">
            <div class="collapsar__title">Разработчики</div>
            <div class="collapsar__content" x-cloak x-show="current=='devs'" x-collapse><?php echo $xml->developers ?></div>
        </article>
    </div>
    <?php
}
else
{
    ?>
    <div>Не удалось загрузить файл help.xml</div>
    <?php
}
?>
</div>
<!-- <div class="col --4:xxl order-first order-last:xxl">
    <div class="help-tg mb-2">
        В случае затруднений вы можете обратиться в наш
        <a class="link-icon" href="https://t.me/joinchat/DFRbKQvV_FQA8TatJjlWRw"><svg><use href="assets/img/sprite.svg#s-tg" /></svg> telegram-чат</a>.</a>
    </div>
</div> -->
</div>
