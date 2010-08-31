<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="<?=$project->id?>" />
<meta name="description" content="<?=htmlspecialchars($project->summary)?>" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/project.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript">
function render_wiki() 
{
	creole_render_wiki (
		"project_home_mainarea_wiki_text", 
		"project_home_mainarea_wiki", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment0/<?=$project->id?>/"
	);
}
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="render_wiki()">

<div class="content" id="project_home_content">


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('taskbar'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->

<?php 
$this->load->view (
	'projectbar', 
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'project',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("project/update/{$project->id}", $this->lang->line('Edit')),
			array ("project/delete/{$project->id}", $this->lang->line('Delete'))
		)
	)
); 
?>

<!-- /////////////////////////////////////////////////////////////////////// -->
<div class="sidebar" id="project_home_sidebar">

<div class="box">
<ul>
<li><?=$this->lang->line('Created on')?> <?=$project->createdon?></li>
<li><?=$this->lang->line('Last updated on')?> <?=$project->updatedon?></li>
<li><?=$this->lang->line('Last updated by')?> <?=$project->updatedby?></li>
</ul>
</div>

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Members')?></div>
<ul>
<?php
	$members = preg_split ('/[[:space:],]+/', $project->members);
	$member_count = count ($members);
	$members = array_unique ($members);
	$priority = 0;
	for ($i = 0; $i < $member_count; $i++)
	{
		if (!array_key_exists($i, $members)) continue;

		$m = $members[$i];
		if ($m == '') continue;
		print "<li>{$m}</li>";
	}
?>
</ul>
</div>

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Repository')?></div>
<ul>
<?php
$urls = explode (',', CODEPOT_SVN_BASE_URL);
foreach ($urls as $url)
{
	$url = trim($url);
	if ($url == '') continue;
	print '<li>';
	print anchor ($this->converter->expand($url,$_SERVER) . "/{$project->id}");
	print '</li>';
}
?>
</ul>
</div>

<div class="box">
<div class="boxtitle">
<?= anchor ("/project/log/{$project->id}", $this->lang->line('Change log')) ?>
</div>
<?php 
	if (count($log_entries) > 0)
	{
		print '<table id="project_home_sidebar_log_table">';

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
				print '<td class="object">';
				print anchor (	
					"code/revision/{$x['repo']}/{$xdot}/{$x['rev']}", 
					"r{$x['rev']}");
				print '</td>';
	
				print '</tr>';
	
				print '<tr class="even">';
	
				print '<td></td>';
				print '<td colspan="1" class="details">';
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
	
				print '<td class="object">';
				$uri = '';
				if ($log['type'] == 'project')
				{
					$uri = "/project/home/{$log['projectid']}";
					$trimmed = preg_replace("/(.{20}).+/u", "$1…", $log['message']);
				}
				else if ($log['type'] == 'wiki')
				{
					$hex = $this->converter->AsciiToHex ($log['message']);
					$uri = "/wiki/show_r/{$log['projectid']}/{$hex}";
					$trimmed = preg_replace("/(.{20}).+/u", "$1…", $log['message']);
				}
				else if ($log['type'] == 'file')
				{
					$hex = $this->converter->AsciiToHex ($log['message']);
					$uri = "/file/show/{$log['projectid']}/{$hex}";
					$trimmed = preg_replace("/(.{20}).+/u", "$1…", $log['message']);
				}
				else if ($log['type'] == 'issue')
				{
					$hex = $this->converter->AsciiToHex ($log['message']);
					$uri = "/issue/show/{$log['projectid']}/{$hex}";
					$trimmed =  $this->lang->line('Issue') . " {$log['message']}";
				}
	
				if ($uri != '')
					print anchor ($uri, htmlspecialchars($trimmed));
				else
					print htmlspecialchars($trimmed);
				print '</td>';
	
				print '</tr>';
	
				print '<tr class="even">';
				print '<td></td>';
				print '<td colspan="1" class="details">';
				print '<span class="description">';
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');
				print htmlspecialchars (sprintf($fmt, $log['userid']));
				print '</span>';
				print '</td>';
	
				print '</tr>';
			}
		}

		print "</table>";
	}
?>
</div>

</div> <!-- project_home_sidebar -->

<!-- /////////////////////////////////////////////////////////////////////// -->

<div class="mainarea" id="project_home_mainarea">

<div class="title">
<?=htmlspecialchars($project->name)?>
</div>

<div id="project_home_mainarea_wiki">
<pre id="project_home_mainarea_wiki_text" style="visibility: hidden">
<?php print htmlspecialchars($project->description); ?>
</pre>
</div> <!-- project_home_mainarea_wiki -->

</div> <!-- project_home_mainarea -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 
</div> <!--  project_home_content -->

</body>

</html>

