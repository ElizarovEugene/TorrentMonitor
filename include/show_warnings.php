<?php
$dir = dirname(__FILE__)."/../";
include_once $dir."config.php";
include_once $dir."class/System.class.php";
include_once $dir."class/Database.class.php";
include_once $dir."class/Errors.class.php";

$count = Database::getWarningsCount();
if ( ! empty($count))
{
?>
<table class="warning_table" border="0" cellpadding="0" cellspacing="1">
	<thead> 
	<tr>
		<th width="20%">Время</th>
		<th width="15%">Трекер</th>
		<th width="65%">Причина</th>
 	</tr>
	</thead>
	<?php

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
				?>
			<tr class="<?php echo $class ?>">
				<td align="center"><?php echo $date ?></td>
				<td>&nbsp;&nbsp;<?php echo $errors[$x]['where'] ?></td>
				<td>&nbsp;&nbsp;<?php echo Errors::getWarning($errors[$x]['reason']) ?></td>
			</tr>			
			<?php	
			}
			?>
			<tr class="second">
				<td colspan="3" align="center">...</td>
			</tr>
			<?php
			$errors = array_slice($errors, $countErrorsByTracker-2, 2);
			for ($x=0; $x<2; $x++)
			{
				if (($x % 2)==0)
					$class = "first";
				else
					$class = "second";
				
				$date = $errors[$x]['day']." ".Sys::dateNumToString($errors[$x]['month'])." ".$errors[$x]['year']." ".$errors[$x]['time'];
				?>
			<tr class="<?php echo $class ?>">
				<td align="center"><?php echo $date ?></td>
				<td>&nbsp;&nbsp;<?php echo $errors[$x]['where'] ?></td>
				<td>&nbsp;&nbsp;<?php echo Errors::getWarning($errors[$x]['reason']) ?></td>
			</tr>			
			<?php	
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
				?>
			<tr class="<?php echo $class ?>">
				<td align="center"><?php echo $date ?></td>
				<td>&nbsp;&nbsp;<?php echo $errors[$x]['where'] ?></td>
				<td>&nbsp;&nbsp;<?php echo Errors::getWarning($errors[$x]['reason']) ?></td>
			</tr>
		 	<?php
			}
		}
	}
}
?>
</table>