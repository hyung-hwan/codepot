<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
</script>

<?php
	$caption = $this->lang->line('Home');
	if ($login['id'] != '') $caption .= "({$login['id']})";
?>
<title><?=htmlspecialchars($caption)?></title>
</head>

<body>

<div class="content" id="user_sitelog_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php

$this->load->view (
        'projectbar',
        array (
		'project' => NULL,
		'site' => NULL,
		'pageid' => 'sitelog',
                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_sitelog_mainarea">

<div class="title" id="user_sitelog_mainarea_title">
<?= $this->lang->line ('Change log') ?>
</div>

<div id="user_sitelog_mainarea_result">

<table id="user_sitelog_mainarea_result_table">
<?php 
	$curdate = '';
	$xdot = $this->converter->AsciiToHex ('.');

	$rowclasses = array ('odd', 'even');
	$rowcount = 0;

	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code' && $log['action'] == 'commit')
		{
			$code_commit = $log['code-commit'];

			$date = substr($code_commit['time'], 0, 10);
			$time = substr($code_commit['time'], 11, 5);
		}
		else
		{
			$date = date ('Y-m-d', strtotime($log['createdon']));
			$time = date ('h:i', strtotime($log['createdon']));
		}

		if ($curdate != $date)
		{
			print "<tr class='break'><td colspan=3 class='break'>&nbsp;</td></tr>";
			print "<tr class='head'><td colspan=3 class='date'>$date</td></tr>";
			$curdate = $date;
			$rowcount = 0;
		}

		print "<tr class='{$rowclasses[$rowcount%2]}'>";
		$rowcount++;
		print '<td class="time">' . $time . '</td>';

		print '<td class="projectid">';
		print anchor (
			"/project/home/{$log['projectid']}",
			$log['projectid']);
		print '</td>';

		print '<td class="details">';

		if ($log['type'] == 'code' && $log['action'] == 'commit')
		{
			print '<span class="description">';
			print anchor (	
				"/source/revision/{$log['projectid']}/{$xdot}/{$code_commit['rev']}", 
				"r{$code_commit['rev']}");
			print ' committed by ';
			print htmlspecialchars ($code_commit['author']);
			print '</span>';

			print '<pre class="message">';
			print htmlspecialchars ($code_commit['message']);
			print '</pre>';
		}

		print '</td>';
		print '</tr>';
	}
?>
<tr class='foot'>
<td colspan=3 class='pages'><?= $page_links ?></td>
</table>

</div> <!-- user_sitelog_mainarea_result -->

</div> <!-- user_sitelog_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- user_sitelog_content -->

</body>
</html>
