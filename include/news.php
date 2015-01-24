<?php 
$dir = dirname(__FILE__)."/../";
include_once $dir."class/Database.class.php";
include_once $dir."class/System.class.php";
?>
<h2 class="settings-title">Новости</h2>
<?php
$news = Database::getNews();
if ( ! empty($news))
{
    for ($i=0; $i<count($news); $i++)
    {
?>
<div><?php echo $news[$i]['text']?></div>
<?php
    }
}
?>
<div class="clear-both"></div>
