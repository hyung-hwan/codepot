<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

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

<div class="codepot-title-band" id="code_history_title_band">

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

<div id="code_history_result" class="codepot-relative-container-view">
	<table id="code_history_result_table" class="codepot-full-width-table codepot-spacious-table">
	<?php 

		$history = $file['history'];
		$history_count = count($history);
		$curfullpath = $fullpath;
		for ($i = $history_count; $i > 0; )
		{
			$h = $history[--$i];
			$xfullpath = $this->converter->AsciiToHex (($fullpath == '')? '.': $fullpath);

			print '<tr class="commit-info-top">';
			print '<td class="commit-author-td" rowspan=2>';
			// Repository migration from googlecode revealed that it did not put 
			// 'author' for initial project creation. So I've added the following check.
			if (array_key_exists('author', $h)) 
			{
				$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($h['author']));
				$user_home_url = codepot_merge_path (site_url(), '/user/home/' . $this->converter->AsciiToHex($h['author']));
				//print "<img src='{$user_icon_url}' class='codepot-committer-icon-24x24' /> ";
				print "<div class='commit-author-icon'>";
				print "<a href='{$user_home_url}'><img src='{$user_icon_url}' class='commit-author-icon' /></a>";
				print "</div>";
			}
			print '</td>';


			print '<td class="commit-basic-info-td">';

			print '<table class="commit-basic-info-subtable"><tr>';
			print '<td class="commit-revision-td">';
			print '<div class="commit-revision">';
			print anchor ("code/file/{$project->id}/{$xfullpath}/{$h['rev']}", $h['rev']);
			print '</div>';
			print '</td>';

			print '<td class="commit-datetime-td">';
			print '<div class="commit-datetime">';
			print codepot_unixtimetodispdate(strtotime($h['date']));
			print '</div>';
			print '</td>';

			print '<td class="commit-author-text-td">';
			print '<div class="commit-author-text">';
			print "<a href='{$user_home_url}'>";
			print htmlspecialchars($h['author']);
			print '</a>';
			print '</div>';
			print '</td>';

			print '<td class="commit-tag-td">';
			if (!empty($h['tag']))
			{
				print '<span class="left_arrow_indicator">';
				print htmlspecialchars($h['tag']);
				print '</span>';
			}
			print '</td>';
			print '</tr></table>';

			print '</td>';

			print '<td rowspan="2" class="commit-actions-td">';
			print '<div class="commit-actions">';
			if ($file['type'] == 'file')
			{
				print anchor ("code/blame/{$project->id}/{$xfullpath}/{$h['rev']}", 
					sprintf('<img src="%s" class="codepot-buttoned-img-30" alt="%s" title="%s"/>', base_url_make('/css/images/iconmonstr-clipboard-6-240.png'), $this->lang->line('Blame'), 'Show code annotated with revision and author information'));
				print '&nbsp;';

				print anchor ("code/diff/{$project->id}/{$xfullpath}/{$h['rev']}",
					sprintf('<img src="%s" class="codepot-buttoned-img-30" alt="%s" title="%s"/>', base_url_make('/css/images/iconmonstr-ethernet-1-240.png'), $this->lang->line('Difference'), 'Show differences'));
				print '&nbsp;';
			}

			print anchor ("code/revision/{$project->id}/{$xfullpath}/{$h['rev']}",
				sprintf('<img src="%s" class="codepot-buttoned-img-30" alt="%s" title="%s"/>', base_url_make('/css/images/iconmonstr-script-4-240.png'), $this->lang->line('Changes'), 'Show revision changes'));
			print '&nbsp;';
			print anchor ("code/file/{$project->id}/{$xfullpath}/{$h['rev']}", 
				sprintf('<img src="%s" class="codepot-buttoned-img-30" alt="%s" title="%s"/>', base_url_make('/css/images/iconmonstr-sitemap-7-240.png'), $this->lang->line('Changes'), 'Show the repository'));
			print '</div>';
			print '</td>';
			print '</tr>';

			print '<tr class="commit-info-bottom">';

			print '<td class="commit-message-td">';
			if ($h['review_count'] > 0)
			{
				$tmp = sprintf ('<span class="codepot-history-review-count">%d</span>', $h['review_count']);
				print  anchor("code/revision/{$project->id}/{$xfullpath}/{$h['rev']}#code_revision_result_comments", $tmp);
				print ' ';
			}
			print anchor ("code/revision/{$project->id}/{$xfullpath}/{$h['rev']}", htmlspecialchars($h['msg']), "class='commit-message'");
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
</div> <!-- code_history_result -->

</div> <!-- code_history_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_history_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>

