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

<!--[if lte IE 8]><script type="text/javascript" src="<?php print base_url_make('/js/excanvas.min.js')?>"></script><![endif]-->
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.time.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.categories.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.pie.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.tickrotor.js')?>"></script>


<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"site_home_result_wiki_text",
		"site_home_result_wiki",
		"<?php print site_url()?>/site/wiki/",
		"<?php print site_url()?>/site/image/",
		false
	);

	prettyPrint ();
}

function labelFormatter(label, series) 
{
	return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + series.data[0][1] + "(" + Math.round(series.percent) + "%)</div>";
}

<?php if (count($open_issue_counts_per_project) > 0): ?>
function show_open_issues_per_project()
{
	var open_issues_per_project_data = [
	<?php
		$first = TRUE;
		foreach ($open_issue_counts_per_project as $issue)
		{
			if ($issue->issue_count > 0)
			{
				if ($first) $first = FALSE;
				else print "\n,";
				printf ("['%s', %d]", $issue->projectid, $issue->issue_count);
			}
		}
	?>
	];

	var your_open_issues_per_project_data = [
	<?php
		$first = TRUE;
		foreach ($your_open_issue_counts_per_project as $issue)
		{
			if ($issue->issue_count > 0)
			{
				if ($first) $first = FALSE;
				else print "\n,";
				printf ("['%s', %d]", $issue->projectid, $issue->issue_count);
			}
		}
	?>
	];

	var dataset =
	[
		{
			label: "Total Open Issues",
			data: open_issues_per_project_data
		},

		{
			label: "My Open Issues",
			data: your_open_issues_per_project_data
		}
	];

	var options =
	{
		series: {
			shadowSize: 0,
			bars: { 
				show: true, 
				fill: true,
				align: "center",
				barWidth: 0.8
			}
		},

		grid: { hoverable: true, clickable: true },

		xaxes: [
			{ mode: "categories",
			  autoscaleMargin: 0.05,
			  rotateTicks: ((open_issues_per_project_data.length >= 8)? 135: 0)
			}
		],

		yaxes: { }
	};

	var issue_graph_view = $("#site_home_open_issues_per_project");
	var issue_graph_plot = $.plot(issue_graph_view, dataset, options);

	issue_graph_view.bind("plotclick", function (event, pos, item) {
		if (item) {
				$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '/issue/home/' + item.series.data[item.dataIndex][0]));
		}
	});
}
<?php endif; ?>

<?php if (count($commit_counts_per_project) > 0): ?>
function show_commits_per_project()
{
	var commits_per_project_data = [
	<?php
		$first = TRUE;
		foreach ($commit_counts_per_project as $commit)
		{
			if ($commit->commit_count > 0)
			{
				if ($first) $first = FALSE;
				else print "\n,";
				printf ("{ label: '%s', data: %d}", $commit->projectid, $commit->commit_count);
			}
		}
	?>
	];

	var options =
	{
		series: {
			shadowSize: 0,
			pie: { 
				show: true, 
				innerRadius: 0.1,
				label: {
					show: true,
					radius: 0.9,
					formatter: labelFormatter,
					background: { opacity: 0.8 }
				}
			}
		},
		legend: {
			show: false
		}
	};

	var commit_graph_plot = $.plot($("#site_home_commits_per_project"), commits_per_project_data, options);
}
<?php endif; ?>

<?php if (count($commit_counts_per_user) > 0): ?>
function show_commits_per_user()
{
	var commits_per_user_data = [
	<?php
		$first = TRUE;
		foreach ($commit_counts_per_user as $commit)
		{
			if ($commit->commit_count > 0)
			{
				if ($first) $first = FALSE;
				else print "\n,";
				printf ("{ label: '%s', data: %d}", $commit->userid, $commit->commit_count);
			}
		}
	?>
	];

	var options =
	{
		series: {
			shadowSize: 0,
			pie: { 
				show: true, 
				innerRadius: 0.1,
				label: {
					show: true,
					radius: 0.9,
					formatter: labelFormatter,
					background: { opacity: 0.8 }
				}
			}
		},
		legend: {
			show: false
		}
	};

	var commit_graph_plot = $.plot($("#site_home_commits_per_user"), commits_per_user_data, options);
}
<?php endif; ?>

$(function () {
	render_wiki ();

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

<?php if (count($open_issue_counts_per_project) > 0): ?>
	show_open_issues_per_project();
<?php endif; ?>

<?php if (count($commit_counts_per_project) > 0): ?>
	$("#site_home_sidebar_top_projects_box").accordion ({
		collapsible: true 
	});
	show_commits_per_project();
<?php endif; ?>

<?php if (count($commit_counts_per_user) > 0): ?>
	$("#site_home_sidebar_top_committers_box").accordion ({
		collapsible: true 
	});
	show_commits_per_user();
<?php endif; ?>

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



<div class="codepot-sidebar" id="site_home_sidebar">

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

<?php if (count($commit_counts_per_project) > 0): ?>
<div id="site_home_sidebar_top_projects_box" class="collapsible-box">
	<div id="site_home_sidebar_top_projects_header" class="collapsible-box-header">
		<?php printf ($this->lang->line('FMT_TOP_X_PROJECTS'), CODEPOT_MAX_TOP_PROJECTS); ?>
	</div>

	<div id="site_home_result_commits_per_project_graph" style="overflow:auto;">
		<div id="site_home_commits_per_project" style="width:100%;height:250px;"></div>
	</div>
</div>
<?php endif; ?>

<?php if (count($commit_counts_per_user) > 0): ?>
<div id="site_home_sidebar_top_committers_box" class="collapsible-box">
	<div id="site_home_sidebar_top_committers_header" class="collapsible-box-header">
		<?php printf ($this->lang->line('FMT_TOP_X_COMMITTERS'), CODEPOT_MAX_TOP_COMMITTERS); ?>
	</div>

	<div id="site_home_result_commits_per_user_graph" style="overflow:auto;">
		<div id="site_home_commits_per_user" style="width:100%;height:250px;"></div>
	</div>
</div>
<?php endif; ?>

<div id="site_home_sidebar_log_box" class="collapsible-box">
	<div id="site_home_sidebar_log_header" class="collapsible-box-header">
	<span><?php print $this->lang->line('Change log'); ?></span>
	<span id="site_home_sidebar_log_all_span"><a href='#' id="site_home_sidebar_log_all_button"><?php print $this->lang->line('All'); ?></a></span>
	</div>

	<div id="site_home_sidebar_log_table_container" class="collapsible-box-panel">
	<table id="site_home_sidebar_log_table" class="collapsible-box-table codepot-full-width-table">
	<?php 
		$xdot = $this->converter->AsciiToHex ('.');
		foreach ($log_entries as $log)
		{
			if ($log['type'] == 'code')
			{
				$x = $log['message'];

				print '<tr class="odd">';
				print '<td class="date">';
				print codepot_dbdatetodispdate ($log['createdon'], 'Y-m-d');
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
					"#R{$x['rev']}");
				print '</td>';

				print '</tr>';

				print '<tr class="even">';

				print '<td colspan="3" class="details">';
				print '<span class="description">';

				$xauthor = $this->converter->AsciiToHex($x['author']);

				if ($log['action'] == 'revpropchange')
				{
					$fmt = $this->lang->line ('MSG_LOG_REVPROP_CHANGE_BY');
					//print htmlspecialchars (sprintf($fmt, $x['propname'], $x['author']));

					printf (
						htmlspecialchars ($fmt),
						htmlspecialchars ($x['propname']),
						anchor ("/user/log/{$xauthor}", htmlspecialchars ($x['author'])));
				}
				else
				{
					$fmt = $this->lang->line (
						'MSG_LOG_'.strtoupper($log['action']).'_BY');
					printf (
						htmlspecialchars ($fmt),
						anchor ("/user/log/{$xauthor}", htmlspecialchars ($x['author'])));
				}
				print '</span>';

				if ($log['action'] != 'revpropchange')
				{
					print '<div class="codepot-plain-text-view"><pre>';
					$sm = strtok (trim ($x['message']), "\r\n");
					print htmlspecialchars ($sm);
					print '</pre></div>';
				}
				print '</td>';
				print '</tr>';
			}
			else
			{
				print '<tr class="odd">';
				print '<td class="date">';
				print codepot_dbdatetodispdate ($log['createdon'], 'Y-m-d');
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
					//$trimmed = $this->lang->line('Issue') . " {$log['message']}";
					$trimmed = "#I{$log['message']}";
				}

				if ($uri != '' && $trimmed != '')
					print anchor ($uri, htmlspecialchars($trimmed));
				else
					print htmlspecialchars($trimmed);
				print '</td>';

				print '</tr>';

				print '<tr class="even">';
				print '<td colspan="3" class="details">';
				print '<span class="description">';
				$fmt = $this->lang->line (
					'MSG_LOG_'.strtoupper($log['action']).'_BY');
				$xuserid = $this->converter->AsciiToHex ($log['userid']);
				printf (
					htmlspecialchars ($fmt),
					anchor ("/user/log/{$xuserid}", htmlspecialchars ($log['userid'])));
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

<div id="site_home_result" class="codepot-static-container-view">

	<?php if (count($open_issue_counts_per_project) > 0): ?>
	<div id="site_home_result_open_issues_graph" style="overflow:auto; padding-left:10px;">
		<div id="site_home_open_issues_per_project" style="width:100%;height:400px;margin-bottom:1em;"></div>
	</div>
	<?php endif; ?>

	<div id="site_home_result_wiki" class="codepot-styled-text-view">
	<pre id="site_home_result_wiki_text" style="visibility: hidden"><?php print htmlspecialchars($site->text); ?></pre>
	</div> <!-- site_home_text -->

</div> <!-- site_home_result -->


</div> <!-- site_home_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- site_home_content -->

<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 

</body>

</html>
