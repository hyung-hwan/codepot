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

	foreach ($sitelogs as $sitelog)
	{
		// TODO: use time... and inspect type then may use svn_time.
		$date = substr($sitelog['svn_time'], 0, 10);
		$time = substr($sitelog['svn_time'], 11, 5);
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
			"/source/file/{$sitelog['projectid']}/{$xdot}/{$sitelog['svn_rev']}", 
			$sitelog['projectid']);
		print '</td>';

		print '<td class="details">';

		if ($sitelog['type'] == 'svn-commit')
		{
			print '<span class="description">';
			print anchor (	
				"/source/revision/{$sitelog['projectid']}/{$xdot}/{$sitelog['svn_rev']}", 
				"r{$sitelog['svn_rev']}");
			print ' committed by ';
			print htmlspecialchars ($sitelog['svn_author']);
			print '</span>';

			print '<pre class="message">';
			print htmlspecialchars ($sitelog['svn_message']);
			print '</pre>';
			/*
			print '<br />';
			print '<span class="message">';
			$sm = htmlspecialchars ($sitelog['svn_message']);
			print str_replace (array ("\r\n", "\n", "\r"), '<br />', $sm);
			print '</span>';
			*/
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
