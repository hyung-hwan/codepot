<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css">
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/site.css">

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"site_home_mainarea_wiki_text",
		"site_home_mainarea_wiki",
		"<?=site_url()?>/site/wiki/",
		"<?=site_url()?>/site/image/"
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

<div class="content" id="site_home_content">

<!-- ////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('taskbar'); ?>

<!-- ////////////////////////////////////////////////////////////////////// -->

<?php

$this->load->view (
        'projectbar',
        array (
		'banner' => $site->name,

		'page' => array (
			'type' => ($login['sysadmin?']? 'site': ''),
			'id' => '',
			'site' => ($login['sysadmin?']? $site: NULL)
                ),

                'ctxmenuitems' => NULL
        )
);
?>

<!-- ////////////////////////////////////////////////////////////////////// -->

<div class="mainarea" id="site_home_mainarea">

<div class="sidebar" id="site_home_mainarea_sidebar">

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Latest projects')?></div>
<ul>
<?php
foreach ($latest_projects as $project)
{
	if (strcasecmp ($project->name, $project->id) != 0)
		$cap = "{$project->name} ($project->id)";
	else $cap = $project->name;

	//$sum = preg_replace("/(.{15}).+/u", "$1…", $project->summary);
	//$sum = htmlspecialchars ($sum);

	$anc = anchor ("project/home/{$project->id}", 
		htmlspecialchars($cap), "title='{$project->summary}'");
	print "<li>{$anc}</li>";
}
?>
</ul>
</div>

<div class="box">
<div class="boxtitle">
<?= anchor ("/site/log", $this->lang->line('Change log')) ?>
</div>
<table id="site_home_mainarea_sidebar_log_table">
<?php 
	$xdot = $this->converter->AsciiToHex ('.');
	foreach ($log_entries as $log)
	{
		if ($log['type'] == 'code')
		{
			$x = $log['message'];

			print '<tr class="odd">';
			print '<td class="date">';
			//print substr($x['time'], 5, 5);
			print date ('m-d', strtotime($log['createdon']));
			print '</td>';
			print '<td class="projectid">';
			/*
			print anchor (
				"code/file/{$x['repo']}/{$xdot}/{$x['rev']}", 
				$x['repo']);
			*/
			print anchor ("project/home/{$x['repo']}", $x['repo']);
			print '</td>';
			print '<td class="object">';
			print anchor (	
				"code/revision/{$x['repo']}/{$xdot}/{$x['rev']}", 
				"r{$x['rev']}");
			print '</td>';

			print '</tr>';

			print '<tr class="even">';

			print '<td></td>';
			print '<td colspan=2 class="details">';
			print '<span class="description">';

			if ($log['action'] == 'revpropchange')
			{
				$fmt = $this->lang->line ('MSG_LOG_REVPROP_CHANGE_BY');
				print htmlspecialchars (sprintf($fmt, $x['propname'], $x['author']));
			}
			else
			{
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');
				print htmlspecialchars (sprintf($fmt, $x['author']));
			}
			print '</span>';

			if ($log['action'] != 'revpropchange')
			{
				print '<pre class="message">';
				$sm = strtok (trim ($x['message']), "\r\n");
				print htmlspecialchars ($sm);
				print '</pre>';
			}
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
				$trimmed = preg_replace("/(.{15}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'wiki')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/wiki/show_r/{$log['projectid']}/{$hex}";
				$trimmed = preg_replace("/(.{15}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'file')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/file/show/{$log['projectid']}/{$hex}";
				$trimmed = preg_replace("/(.{15}).+/u", "$1…", $log['message']);
			}
			else if ($log['type'] == 'issue')
			{
				$hex = $this->converter->AsciiToHex ($log['message']);
				$uri = "/issue/show/{$log['projectid']}/{$hex}";
				$trimmed = $this->lang->line('Issue') . " {$log['message']}";
			}

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
</div> <!-- box -->

</div> <!-- site_home_mainarea_sidebar -->

<div id="site_home_mainarea_wiki">
<pre id="site_home_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($site->text); ?>
</pre>
</div> <!-- site_home_mainarea_text -->

<!-- ////////////////////////////////////////////////////////////////////// -->

</div> <!-- site_home_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- site_home_content -->

</body>
</html>
