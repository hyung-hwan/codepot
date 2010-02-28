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

<body  onLoad="render_wiki()">

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
		if ($log['type'] == 'code' && $log['action'] == 'commit')
		{
			$x = $log['code-commit'];

			print '<tr class="odd">';
			print '<td>';
			print substr($x['time'], 0, 10);
			print '</td>';
			print '<td>';
			print anchor (
				"/source/file/{$x['repo']}/{$xdot}/{$x['rev']}", 
				$x['repo']);
			print '</td>';
			print '<td>';
			print anchor (	
				"/source/revision/{$x['repo']}/{$xdot}/{$x['rev']}", 
				$x['rev']);
			print '</td>';
			print '<td>';
			print htmlspecialchars ($x['author']);
			print '</td>';
			print '</tr>';

			print '<tr class="even">';
			print '<td colspan=4>';
			$sm = strtok (trim ($x['message']), "\r\n");
			print htmlspecialchars ($sm);
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
