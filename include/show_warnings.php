<?php
$dir = str_replace('include', '', dirname(__FILE__));

include_once $dir."class/System.class.php";

if ( ! Sys::checkAuth())
    die(header('Location: ../'));

include_once $dir."class/Database.class.php";
include_once $dir."class/Errors.class.php";
include_once $dir."class/rain.tpl.class.php";

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
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('date'   => $date,
                                    'where'  => $errors[$x]['where'],
                                    'reason' => Errors::getWarning($errors[$x]['reason']),
                                    'full'   => true,
                              );
            }
            
            $contents[] = array('full'   => false,
                          );
            
            $errors = array_slice($errors, $countErrorsByTracker-2, 2);
            for ($x=0; $x<2; $x++)
            {
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('date'   => $date,
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
                $date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
                
                $contents[] = array('date'   => $date,
                                    'where'  => $errors[$x]['where'],
                                    'reason' => Errors::getWarning($errors[$x]['reason']),
                                    'full'   => true,
                              );
            }
        }
    }
}

// заполнение шаблона
raintpl::configure("root_dir", $dir );
raintpl::configure("tpl_dir" , Sys::getTemplateDir() );

$tpl = new RainTPL;
$tpl->assign( "contents", $contents );
$tpl->assign( "show_table", (! empty($count)) );

$tpl->draw( 'show_warnings' );

?>