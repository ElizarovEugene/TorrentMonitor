<?php
define('ROOT_DIR', str_replace('include', '', dirname(__FILE__)) );

include_once ROOT_DIR."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once ROOT_DIR."class/Database.class.php";
include_once ROOT_DIR."class/Errors.class.php";
include_once ROOT_DIR."class/rain.tpl.class.php";

$contents = array();
$count = Database::getWarningsCount();
if ( ! empty($count))
{
    for ($i=0; $i<count($count); $i++)
    {
        $errors = Database::getWarningsList($count[$i]['where']);
        $countErrorsByTracker = count($errors);
    
        if ($countErrorsByTracker > 5)
        {
            for ($x=0; $x<2; $x++)
            {
                if (($x % 2)==0)
                    $class = "second";
                else
                    $class = "first";
                
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('class'  => $class,
                                    'date'   => $date,
                                    'where'  => $errors[$x]['where'],
                                    'reason' => Errors::getWarning($errors[$x]['reason']),
                                    'full'   => true,
                              );
            }
            
            $contents[] = array('class'  => $second,
                                'full'   => false,
                          );
            
            $errors = array_slice($errors, $countErrorsByTracker-2, 2);
            for ($x=0; $x<2; $x++)
            {
                if (($x % 2)==0)
                    $class = "first";
                else
                    $class = "second";
                
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('class'  => $class,
                                    'date'   => $date,
                                    'where'  => $errors[$x]['where'],
                                    'reason' => Errors::getWarning($errors[$x]['reason']),
                                    'full'   => true,
                              );
            }
        }
        else
        {
            for ($x=0; $x<count($errors); $x++)
            {
                if (($x % 2)==0)
                    $class = "second";
                else
                    $class = "first";
                
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('class'  => $class,
                                    'date'   => $date,
                                    'where'  => $errors[$x]['where'],
                                    'reason' => Errors::getWarning($errors[$x]['reason']),
                                    'full'   => true,
                              );
            }
        }
    }
    // заполнение шаблона
    raintpl::configure("root_dir", ROOT_DIR );
    raintpl::configure("tpl_dir" , Sys::getTemplateDir() );
    
    $tpl = new RainTPL;
    $tpl->assign( "contents", $contents );
    
    $tpl->draw( 'show_warnings' );    
}
else
    echo 'Нет ошибок.';



?>