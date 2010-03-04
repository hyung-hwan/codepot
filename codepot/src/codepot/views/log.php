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

<div class="content" id="log_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php

if (!isset($project))  $project = NULL;
if (!isset($site))  $site = NULL;

$this->load->view (
        'projectbar',
        array (
		'site' => $site,
		'project' => $project,
		'pageid' => ((isset($project) && $project != NULL)? 'project': 'sitelog'),
                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="log_mainarea">

<div class="title" id="log_mainarea_title">
<?= $this->lang->line ('Change log') ?>
</div>

<div id="log_mainarea_result">

<table id="log_mainarea_result_table">
<?php 
	$curdate = '';
	$xdot = $this->converter->AsciiToHex ('.');

	$rowclasses = array ('odd', 'even');
	$rowcount = 0;

	$numcols = 4;
	if (isset($project) && $project != NULL) $numcols--;

	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code')
		{
			$code = $log['message'];

			$date = substr($code['time'], 0, 10);
			$time = substr($code['time'], 11, 5);
		}
		else
		{
			$date = date ('Y-m-d', strtotime($log['createdon']));
			$time = date ('h:i', strtotime($log['createdon']));
		}

		if ($curdate != $date)
		{
			print "<tr class='break'><td colspan='{$numcols}' class='break'>&nbsp;</td></tr>";
			print "<tr class='head'><td colspan='{$numcols}' class='date'>$date</td></tr>";
			$curdate = $date;
			$rowcount = 0;
		}

		print "<tr class='{$rowclasses[$rowcount%2]}'>";
		$rowcount++;
		print '<td class="time">' . $time . '</td>';

		if (!isset($project) || $project == NULL)
		{
			print '<td class="projectid">';
			print anchor ("/project/home/{$log['projectid']}", $log['projectid']);
			print '</td>';
		}


		if ($log['type'] == 'code')
		{
			print '<td class="obejct">';
			print anchor (	
				"/source/revision/{$log['projectid']}/{$xdot}/{$code['rev']}", 
				"r{$code['rev']}");
			print '</td>';

			print '<td class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');
			print htmlspecialchars (sprintf($fmt, $code['author']));
			print '</span>';

			print '<pre class="message">';
			print htmlspecialchars ($code['message']);
			print '</pre>';
		}
		else
		{
			print '<td class="obejct">';

			$uri = '';
			if ($log['type'] == 'project')
			{
				$uri = "/project/home/{$log['projectid']}";
			}
			else if ($log['type'] == 'wiki')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/wiki/show_r/{$log['projectid']}/{$hex}";
			}
			else if ($log['type'] == 'file')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/file/show/{$log['projectid']}/{$hex}";
			}

			$trimmed = preg_replace("/(.{10}).+/u", "$1…", $log['message']);
			if ($uri != '')
			{
				print anchor (
					$uri,
					htmlspecialchars ($trimmed),
					'title="'.htmlspecialchars ($log['message']).'"');
			}
			else print htmlspecialchars ($trimmed);
			print '</td>';

			print '<td class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');
			print htmlspecialchars (sprintf($fmt, $log['userid']));
			print '</span>';
		}

		print '</td>';
		print '</tr>';
	}
?>
<tr class='foot'>
<td colspan='<?=$numcols?>' class='pages'><?= $page_links ?></td>
</table>

</div> <!-- log_mainarea_result -->

</div> <!-- log_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- log_content -->

</body>
</html>
