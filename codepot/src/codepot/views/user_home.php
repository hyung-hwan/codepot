<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>
<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"user_home_mainarea_textpre",
		"user_home_mainarea_text",
		"<?=dirname(dirname(current_url()))?>/user/home/"
	);
}
</script>

<?php
	$caption = $this->lang->line('Home');
	if ($login['id'] != '') $caption .= "({$login['id']})";
?>
<title><?=htmlspecialchars($caption)?></title>
</head>

<body onLoad="render_wiki()">

<div class="content" id="user_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php

if ($login['sysadmin?'])
{
	$ctxmenuitems = array (
		array ("site/create", $this->lang->line('Create')),
		array ("site/update/{$site->id}", $this->lang->line('Edit')),
		array ("site/delete/{$site->id}", $this->lang->line('Delete'))
	);
}
else $ctxmenuitems = array ();

$this->load->view (
        'projectbar',
        array (
		'project' => NULL,
		'site' => $site,
		'pageid' => 'site',
                'ctxmenuitems' => $ctxmenuitems
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_home_mainarea">

<div class="sidebar" id="user_home_mainarea_sidebar">

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Latest projects')?></div>
<ul>
<?php
foreach ($latest_projects as $project)
{
	//$cap = "{$project->name} ({$project->id})";
	$cap = "{$project->name}";
	$anc = anchor ("project/home/{$project->id}", htmlspecialchars($cap));
	//$date = date ('Y/m/d', strtotime($project->createdon));
	//print "<tr><td>{$anc}</td><td>{$date}</td></tr>";
	print "<li>{$anc}</li>";
}
?>
</ul>
</div>

<div class="box">
<div class="boxtitle">
<?= anchor ("/user/sitelog", $this->lang->line('Change log')) ?>
</div>
<table id="user_home_mainarea_sidebar_log_table">
<?php 
	$xdot = $this->converter->AsciiToHex ('.');
	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code')
		{
			$x = $log['message'];

			print '<tr class="odd">';
			print '<td class="date">';
			print substr($x['time'], 5, 5);
			print '</td>';
			print '<td class="projectid">';
			/*
			print anchor (
				"/source/file/{$x['repo']}/{$xdot}/{$x['rev']}", 
				$x['repo']);
			*/
			print anchor ("/project/home/{$x['repo']}", $x['repo']);
			print '</td>';
			print '<td class="object">';
			print anchor (	
				"/source/revision/{$x['repo']}/{$xdot}/{$x['rev']}", 
				"r{$x['rev']}");
			print '</td>';

			print '</tr>';

			print '<tr class="even">';

			print '<td></td>';
			print '<td colspan=2 class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');
			print htmlspecialchars (sprintf($fmt, $x['author']));
			print '</span>';

			print '<pre class="message">';
			$sm = strtok (trim ($x['message']), "\r\n");
			print htmlspecialchars ($sm);
			print '</pre>';
			print '</td>';
			print '</tr>';
		}
		else
		{
			print '<tr class="odd">';
			print '<td class="date">';
			print date ('m-d', strtotime($log['createdon']));
			print '</td>';

			print '<td class="project">';
			print anchor ("/project/home/{$log['projectid']}", $log['projectid']);
			print '</td>';

			print '<td class="object">';
			$uri = '';
			if ($log['type'] == 'project')
			{
				$uri = "/project/home/{$log['projectid']}";
			}
			else if ($log['type'] == 'wiki' ||
			         $log['type'] == 'file')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/{$log['type']}/show/{$log['projectid']}/{$hex}";
			}

			$trimmed = preg_replace("/(.{15}).+/u", "$1…", $log['message']);
			if ($uri != '')
				print anchor ($uri, htmlspecialchars($trimmed));
			else
				print htmlspecialchars($trimmed);
			print '</td>';

			print '</tr>';

			print '<tr class="even">';
			print '<td></td>';
			print '<td colspan=2 class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');
			print htmlspecialchars (sprintf($fmt, $log['userid']));
			print '</span>';
			print '</td>';

			print '</tr>';
		}
	}
?>
</table>
</div>

</div> <!-- user_home_mainarea_sidebar -->

<div id="user_home_mainarea_text">
<pre id="user_home_mainarea_textpre" style="visibility: hidden">
<?php print htmlspecialchars($site->text); ?>
</pre>
</div> <!-- user_home_mainarea_text -->

<!----------------------------------------------------------->

</div> <!-- user_home_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- user_home_content -->

</body>
</html>
