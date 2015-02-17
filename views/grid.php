<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2015 Sangoma Technologies.
//
foreach (languages_list() as $row) {
	$lrows .= '<tr>';
	$lrows .= '<td>';
	$lrows .= $row['description'];
	$lrows .= '</td>';
	$lrows .= '<td>';
	$lrows .= '<a href="?display=languages&view=form&extdisplay='.$row['language_id'].'"><i class="fa fa-edit"></i></a>&nbsp;';
	$lrows .= '<a href="?display=languages&action=delete&language_id='.$row['language_id'].'"><i class="fa fa-trash"></i></a>';
	$lrows .= '</td>';
	$lrows .= '</tr>';
}

?>
<table class="table table-striped">
	<thead>
		<th><?php echo _("Language")?></th>
		<th><?php echo _("Actions")?></th>
	</thead>
	<tbody>
		<?php echo $lrows ?>
	</tbody>
</table>