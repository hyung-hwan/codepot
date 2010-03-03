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
		"project_home_textpre", 
		"project_home_textarea", 
		"<?=dirname(dirname(dirname(current_url())))?>/wiki/show/<?=$project->id?>/"
	);
}
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onLoad="render_wiki()">

<div class="content" id="project_home_content">


<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php 
$this->load->view (
	'projectbar', 
	array (
		'site' => NULL,
		'pageid' => 'project',
		'ctxmenuitems' => array (
			array ("project/update/{$project->id}", $this->lang->line('Edit')),
			array ("project/delete/{$project->id}", $this->lang->line('Delete'))
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

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
<?= anchor ($this->converter->expand(CODEPOT_SVN_BASE_URL, $_SERVER) . '/' . $project->id) ?>
</div>

<div class="box">
<div class="boxtitle">
<?= $this->lang->line('Change log') ?>
</div>
<table id="project_home_mainarea_sidebar_log_table">
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
			print '<td class="object">';
			print anchor (	
				"/source/revision/{$x['repo']}/{$xdot}/{$x['rev']}", 
				"r{$x['rev']}");
			print '</td>';

			print '</tr>';

			print '<tr class="even">';

			print '<td></td>';
			print '<td colspan=1 class="details">';
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

			$trimmed = preg_replace("/(.{20}).+/u", "$1…", $log['message']);
			if ($uri != '')
				print anchor ($uri, htmlspecialchars($trimmed));
			else
				print htmlspecialchars($trimmed);
			print '</td>';

			print '</tr>';

			print '<tr class="even">';
			print '<td></td>';
			print '<td colspan=1 class="details">';
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

</div> <!-- project_home_sidebar -->

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_home_mainarea">

<div class="title">
<?=htmlspecialchars($project->name)?>
</div>

<div id="project_home_textarea">
<pre id="project_home_textpre" style="visibility: hidden">
<?php print htmlspecialchars($project->description); ?>
</pre>
</div> <!-- project_home_textarea -->

</div> <!-- project_home_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->
 
</div> <!--  project_home_content -->

</body>

</html>

