<?php include_once "header.php"; ?>
<script language="javascript" type="text/javascript">
window.onload = function(){
    show('show_table');
}
</script>

<div id="wrapper">
    <header id="header">
    <?php
    $dir = __DIR__."/../";
    include_once $dir."class/System.class.php";
    ?>
        <h1 class="h-title">TorrentMonitor v.
        <?php
            echo Sys::version();
        ?>
        </h1>
        
        <menu class="h-menu">
            <li id="show_table" class="active"><a href="#" onclick="show('show_table')" class="h-menu-item1">Торренты</a></li>
            <li id="show_watching"><a href="#" onclick="show('show_watching')" class="h-menu-item2">Пользователи</a></li>
            <li id="add"><a href="#" onclick="show('add')" class="h-menu-item3">Добавить</a></li>
            <li id="credentials"><a href="#" onclick="show('credentials')" class="h-menu-item4">Учётные данные</a></li>
            <li id="settings"><a href="#" onclick="show('settings')" class="h-menu-item5">Настройки</a></li>
            <li id="show_warnings"><a href="#" onclick="show('show_warnings')" class="h-menu-item6">Ошибки
            <?php
            $errors = Database::getWarningsCount();
            
            if ( ! empty($errors))
            {
                $count = 0;
                for ($i=0; $i<count($errors); $i++)
                    $count += $errors[$i]['count'];

                if ($count > 0)
                    echo ' ('.$count.')';
            }
            ?>
            </a></li>
            <li id="check"><a href="#" onclick="show('check')" class="h-menu-item7">Тест</a></li>
        </menu>
    </header>
    <div id="content">

    </div>
</div>
<?php include_once "footer.php"; ?>