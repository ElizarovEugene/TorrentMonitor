<?php 
$dir = dirname(__FILE__)."/../";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));
?>
<h2 class="settings-title">Новости</h2>
<?php
$news = Database::getNews();
if ($news != NULL)
{
    for ($i=0; $i<count($news); $i++)
    {
?>
<div class="<?php if ($news[$i]['new']) echo 'new' ?>" id="<?php echo $news[$i]['id']?>" onmouseover="newsRead(<?php echo $news[$i]['id']?>)">
<?php echo $news[$i]['text']?><br /></div>
<?php
    }
}
?>
<br /><br />