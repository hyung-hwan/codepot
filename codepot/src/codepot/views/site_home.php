<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="codepot" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/site.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />


<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"site_home_result_wiki_text",
		"site_home_result_wiki",
		"<?php print site_url()?>/site/wiki/",
		"<?php print site_url()?>/site/image/"
	);

	prettyPrint ();
}

$(function () {
	render_wiki ();

<?php if ($issues && count($issues) > 0): ?>
	$("#site_home_result_open_issues").accordion ({
		collapsible: true 
	}); 
<?php endif; ?>

<?php if ($recently_resolved_issues && count($recently_resolved_issues) > 0): ?>
	$("#site_home_result_resolved_issues").accordion ({
		collapsible: true 
	});
<?php endif; ?>

	$("#site_home_sidebar_latest_projects_box").accordion ({
		collapsible: true 
	});

	$("#site_home_sidebar_log_box").accordion ({
		collapsible: true 
	});

	$("#site_home_sidebar_log_all_button").button ().click (function () {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", "/site/log"));
		return false;
	});
});
</script>

<title><?php
	if (!isset($login['id']) || $login['id'] == '') 
		printf ('%s', htmlspecialchars($site->name));
	else
		printf ('%s - %s', htmlspecialchars($site->name), $login['id']);
?></title>
</head>

<body>

<div class="content" id="site_home_content">

<!-- ////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('taskbar'); ?>

<!-- ////////////////////////////////////////////////////////////////////// -->

<?php

$this->load->view (
	'projectbar',
	array (
		'banner' => array($site->name, $site->summary),

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



<div class="sidebar" id="site_home_sidebar">

<div id="site_home_sidebar_latest_projects_box" class="collapsible-box">
<div id="site_home_sidebar_latest_projects_header" class="collapsible-box-header">
	<?php print $this->lang->line('Latest projects'); ?>
</div>

<ul id="site_home_sidebar_latest_projects_list" class="collapsible-box-list">
<?php
foreach ($latest_projects as $project)
{
	if (strcasecmp ($project->name, $project->id) != 0)
		$cap = "{$project->name} ($project->id)";
	else $cap = $project->name;

	$sum = $project->summary;
	//$sum = preg_replace("/(.{15}).+/u", "$1…", $project->summary);
	$sum = htmlspecialchars ($sum);

	$anc = anchor ("project/home/{$project->id}", 
		htmlspecialchars($cap), "title='$sum'");
	print "<li>{$anc}</li>";
}
?>
</ul>

</div>

<div id="site_home_sidebar_log_box" class="collapsible-box">
<div id="site_home_sidebar_log_header" class="collapsible-box-header">
<span><?php print $this->lang->line('Change log'); ?></span>
<span id="site_home_sidebar_log_all_span"><a href='#' id="site_home_sidebar_log_all_button"><?php print $this->lang->line('All'); ?></a></span>
</div>

<div id="site_home_sidebar_log_table_container" class="collapsible-box-panel">
<table id="site_home_sidebar_log_table" class="collapsible-box-table">
<?php 
	$xdot = $this->converter->AsciiToHex ('.');
	foreach ($log_entries as $log)
	{
		if (CODEPOT_DATABASE_STORE_GMT)
			$createdon = $log['createdon'] . ' +0000';
		else
			$createdon = $log['createdon'];

		if ($log['type'] == 'code')
		{
			$x = $log['message'];

			print '<tr class="odd">';
			print '<td class="date">';
			print strftime ('%m-%d', strtotime($createdon));
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
			print '<td colspan="2" class="details">';
			print '<span class="description">';

			if ($log['action'] == 'revpropchange')
			{
				$fmt = $this->lang->line ('MSG_LOG_REVPROP_CHANGE_BY');
				//print htmlspecialchars (sprintf($fmt, $x['propname'], $x['author']));
				printf (
					htmlspecialchars ($fmt),
					htmlspecialchars ($x['propname']),
					anchor ("/site/userlog/{$x['author']}", htmlspecialchars ($x['author'])));
			}
			else
			{
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');
				//print htmlspecialchars (sprintf($fmt, $x['author']));
				printf (
					htmlspecialchars ($fmt),
					anchor ("/site/userlog/{$x['author']}", htmlspecialchars ($x['author'])));
			}
			print '</span>';

			if ($log['action'] != 'revpropchange')
			{
				print '<pre class="pre-wrapped message">';
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
			print strftime ('%m-%d', strtotime($createdon));
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

			if ($uri != '' && $trimmed != '')
				print anchor ($uri, htmlspecialchars($trimmed));
			else
				print htmlspecialchars($trimmed);
			print '</td>';

			print '</tr>';

			print '<tr class="even">';
			print '<td></td>';
			print '<td colspan="2" class="details">';
			print '<span class="description">';
			$fmt = $this->lang->line (
				'MSG_LOG_'.strtoupper($log['action']).'_BY');
			//print htmlspecialchars (sprintf($fmt, $log['userid']));
			printf (
				htmlspecialchars ($fmt),
				anchor ("/site/userlog/{$log['userid']}", htmlspecialchars ($log['userid'])));
			print '</span>';
			print '</td>';

			print '</tr>';
		}
	}
?>
</table>
</div>
</div> <!-- box -->

</div> <!-- site_home_sidebar -->

<div class="mainarea" id="site_home_mainarea">

<div id="site_home_result" class="result">

	<?php if ($issues && count($issues) > 0): ?>
	<div id="site_home_result_open_issues" class="collapsible-box">
	<div id="site_home_result_open_issues_header" class="collapsible-box-header">
		<?php print $this->lang->line('Open issues')?>
	</div>
	<ul id="site_home_result_open_issues_list" class="collapsible-box-list">
		<?php 
		foreach ($issues as $issue) 
		{
			$pro = $issue->projectid;
			$xid = $this->converter->AsciiToHex ((string)$issue->id);
			$owner = $issue->owner;
		
			$proissueanc = anchor ("issue/home/{$issue->projectid}", $pro);
			$anc = anchor ("issue/show/{$issue->projectid}/{$xid}", '#' . htmlspecialchars($issue->id));
		
			$status = htmlspecialchars(
				array_key_exists($issue->status, $issue_status_array)?
				$issue_status_array[$issue->status]: $issue->status);
			$type = htmlspecialchars(
				array_key_exists($issue->type, $issue_type_array)?
				$issue_type_array[$issue->type]: $issue->type);
		
			$sum = htmlspecialchars ($issue->summary);
			print "<li><font color='blue'>{$owner}</font> | {$proissueanc} | {$anc} | {$type} {$status} - {$sum}</li>";
		}
		?>
	</ul>
	</div>
	<?php endif; ?>

	<?php if ($recently_resolved_issues && count($recently_resolved_issues) > 0): ?>
	<div id="site_home_result_resolved_issues" class="collapsible-box">
	<div id="site_home_result_resolved_issues_header" class="collapsible-box-header">
		<?php print $this->lang->line('Recently resolved issues')?>
	</div>
	<ul id="site_home_result_resolved_issues_list" class="collapsible-box-list">
		<?php 
		foreach ($recently_resolved_issues as $issue) 
		{
			$pro = $issue->projectid;
			$xid = $this->converter->AsciiToHex ((string)$issue->id);
			$owner = $issue->owner;
		
			$proissueanc = anchor ("issue/home/{$issue->projectid}", $pro);
			$anc = anchor ("issue/show/{$issue->projectid}/{$xid}", '#' . htmlspecialchars($issue->id));
		
			$status = htmlspecialchars(
				array_key_exists($issue->status, $issue_status_array)?
				$issue_status_array[$issue->status]: $issue->status);
			$type = htmlspecialchars(
				array_key_exists($issue->type, $issue_type_array)?
				$issue_type_array[$issue->type]: $issue->type);
		
			$sum = htmlspecialchars ($issue->summary);
			print "<li><font color='blue'>{$owner}</font> | {$proissueanc} | {$anc} | {$type} {$status} - {$sum}</li>";
		}
		?>
	</ul>
	</div>
	<?php endif; ?>

	<div id="site_home_result_wiki">
	<pre id="site_home_result_wiki_text" style="visibility: hidden"><?php print htmlspecialchars($site->text); ?></pre>
	</div> <!-- site_home_text -->

</div> <! -- site_home_result -->


</div> <!-- site_home_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- site_home_content -->

<?php $this->load->view ('footer'); ?>


</body>
</html>
