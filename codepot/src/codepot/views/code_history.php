<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php 
	if ($fullpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($fullpath));
?></title>
</head>

<body>

<div class="content" id="code_history_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="code_history_mainarea">

<div class="title-band" id="code_history_title_band">

	<div class="title">
	<?php
		if ($revision <= 0)
		{
			$revreq = '';
			$revreqroot = '';
		}
		else
		{
			$revreq = "/{$revision}";
			$revreqroot = '/' . $this->converter->AsciiToHex('.') . $revreq;
		}

		// print the anchor for the root nolder with a project name
		print anchor (
			"code/history/{$project->id}{$revreqroot}",
			htmlspecialchars($project->name));

		// explodes part of the full path name into an array 
		$exps = explode ('/', $fullpath);
		$expsize = count($exps);
		$par = '';
		// print anchors pointing to each part
		for ($i = 1; $i < $expsize; $i++)
		{
			print '/';
			$par .= '/' . $exps[$i];
			$xpar = $this->converter->AsciiToHex ($par);
			print anchor (
				"code/history/{$project->id}/{$xpar}{$revreq}",
				htmlspecialchars($exps[$i]));
		}
	?>
	</div>

	<div class="actions"></div>
	<div style="clear: both;"></div>
</div>

<div class="graph" id="code_history_mainarea_graph">
<?php
	$xfullpath = $this->converter->AsciiToHex (($fullpath == '')? '.': $fullpath);

	$graph_url = codepot_merge_path (site_url(), "/code/graph/commits-by-users/{$project->id}/{$xfullpath}{$revreq}");
	print "<img src='{$graph_url}' />";

	$graph_url = codepot_merge_path (site_url(), "/code/graph/commit-share-by-users/{$project->id}/{$xfullpath}{$revreq}");
	print "<img src='{$graph_url}' />";
?>
</div> <!-- code_history_mainarea_graph -->

<div class="result" id="code_history_result">


<table id="code_history_result_table" class="full-width-result-table">
<tr class='full-width-result-table-header'>
	<th><?php print $this->lang->line('Revision')?></th>
	<th><?php print $this->lang->line('Committer')?></th>
	<th><?php print $this->lang->line('Date')?></th>
	<th><?php print $this->lang->line('Message')?></th>
	<?php if ($file['type'] == 'file' || $file['type'] == 'dir') print '<th></th>'; ?>
</tr>
<?php 
	$rowclasses = array ('even', 'odd');
	$history = $file['history'];
	$history_count = count($history);
	$curfullpath = $fullpath;
	for ($i = $history_count; $i > 0; )
	{
		$h = $history[--$i];

		$rowclass = $rowclasses[($history_count - $i) % 2];
		print "<tr class='{$rowclass}'>";

		print '<td class="commit-revision-td">';
		$xfullpath = $this->converter->AsciiToHex (
			($fullpath == '')? '.': $fullpath);

		print anchor ("code/file/{$project->id}/{$xfullpath}/{$h['rev']}", $h['rev']);

		if (!empty($h['tag']))
		{
			print ' ';
			print '<span class="left_arrow_indicator">';
			print htmlspecialchars($h['tag']);
			print '</span>';
		}
		print '</td>';

		print '<td class="commit-author-td">';
		// Repository migration from googlecode revealed that it did not put 
		// 'author' for initial project creation. So I've added the following check.
		if (array_key_exists('author', $h)) print htmlspecialchars($h['author']);
		print '</td>';

		print '<td class="commit-date-td"><code>';
		print strftime('%Y-%m-%d', strtotime($h['date']));
		print '</code></td>';

		print '<td class="commit-message-td">';
		print anchor ("code/revision/{$project->id}/{$xfullpath}/{$h['rev']}", htmlspecialchars($h['msg']), "class='commit-message'");
		//print '<pre class="pre-wrapped">';
		//print htmlspecialchars($h['msg']);
		//print '</pre>';
		print '</td>';

		print '<td>';
		if ($file['type'] == 'file')
		{
			print anchor ("code/blame/{$project->id}/{$xfullpath}/{$h['rev']}", 
				'<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame'));
			print ' | ';
			print anchor ("code/diff/{$project->id}/{$xfullpath}/{$h['rev']}",
				'<i class="fa fa-server"></i> ' . $this->lang->line('Difference')); 
		}
		print '</td>';

		print '</tr>';

		//
		// let's track the copy path.
		//
		$paths = $h['paths'];
		$colspan = 6;
		foreach ($paths as $p)
		{
			if (array_key_exists ('copyfrom', $p) && 
			    $p['action'] == 'A')
			{
				$d = $curfullpath;
				$f = '';

				while ($d != '/' && $d != '')
				{
					if ($d == $p['path'])
					{
						$curfullpath = $p['copyfrom'] . $f;
						print "<tr class='title'><td colspan='{$colspan}'>{$curfullpath}</td></tr>";
						break;
					}

					$d = dirname ($d);
					$f = substr ($curfullpath, strlen($d));
				}
			}
		}

	}
?>
</table>
</div> <!-- code_history_mainarea_body -->

</div> <!-- code_history_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_history_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>

