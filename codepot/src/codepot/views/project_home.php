<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="<?=$project->id?>" />
<meta name="description" content="<?=htmlspecialchars($project->summary)?>" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/project.css')?>" />

<script type="text/javascript" src="<?=base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>

<script type="text/javascript" src="<?=base_url_make('/js/Chart.min.js')?>"></script>

<script type="text/javascript">
function show_commits_per_month_graph(response)
{
	var log = $.parseJSON(response);
	if (log == null)
	{
		alert ('Invalid data received');
		return;
	}

	var min_date = '9999-99', max_date = '0000-00';
	var commits_per_month = [], commits_per_month_keys = [], commits_per_month_values = [];
	var committers_per_month = [], committers_per_month_values = [], committer_list_per_month = [];
	var commits_per_month_count = 0;
	for (var i = 0; i < log.length; i++)
	{
		var date = log[i].date;
		if (date)
		{
			date = date.substring (0, 7);
			if (date in commits_per_month) commits_per_month[date]++;
			else commits_per_month[date] = 1;

			// TODO: calculate committers...
			if (log[i].author)
			{
				committer_list_per_month[date + '-' + log[i].author] = 1;
			}

			if (date < min_date) min_date = date;
			if (date > max_date) max_date = date;

			commits_per_month_count++;
		}
	}

	//if (Object.keys(commits_per_month).length <= 0)
	if (commits_per_month_count <= 0)
	{
		// no data to show
		return;
	}

	for (var key in committer_list_per_month)
	{
		date = key.substring (0, 7);
		if (date in committers_per_month) committers_per_month[date]++;
		else committers_per_month[date] = 1;
	}

	var year, month, min_year, max_year, min_month, max_month;

	min_year = parseInt(min_date.substring (0, 4), 10);
	min_month = parseInt(min_date.substring (5), 10);
	max_year = parseInt(max_date.substring (0, 4), 10);
	max_month = parseInt(max_date.substring (5), 10);

	// fill empty data points
	for (year = min_year; year <= max_year; year++)
	{
		month = (year == min_year)? min_month: 1;
		month_end = (year == max_year)? max_month: 12;
	
		while (month <= month_end)
		{
			var m = month.toString();
			while (m.length < 2) m = '0' + m;

			var y = year.toString();
			while (y.length < 4) y = '0' + y;

			date = y + '-' + m;

			if (!(date in commits_per_month))
			{
				// fill the holes
				commits_per_month[date] = 0;
			}

			if (!(date in committers_per_month))
			{
				// fill the holes
				committers_per_month[date] = 0;
			}

			month++;
		}
	}

	// for sorting
	for (var key in commits_per_month)
	{
		commits_per_month_keys.push (key);
	}

	commits_per_month_keys = commits_per_month_keys.sort();
	for (i = 0; i < commits_per_month_keys.length; i++)
	{
		commits_per_month_values.push (commits_per_month[commits_per_month_keys[i]]);
		committers_per_month_values.push (committers_per_month[commits_per_month_keys[i]]);

		if (commits_per_month_keys.length > 12 && (i % 3)) commits_per_month_keys[i] = '';
	}

	var commits_per_month_data = {
		labels : commits_per_month_keys,

		datasets : [
			{
				label: 'Commits per month',
				fillColor : 'rgba(151,187,205,0.2)',
				strokeColor: "rgba(151,187,205,0.8)",
				pointColor: "rgba(151,187,205,0.8)",
				data : commits_per_month_values
			},
			
			{
				label: 'Committers per month',
				fillColor: "rgba(205,187,151,0.2)",
				strokeColor: "rgba(205,187,151,0.8)",
				pointColor: "rgba(205,187,151,0.8)",
				data : committers_per_month_values
			}
		]

	}

	$('#commits-per-month-canvas').each (function() {
		var canvas = $(this)[0];
		var ctx = canvas.getContext('2d');
		var commits_per_month_chart = new Chart(ctx).Line(commits_per_month_data, {
			responsive : true,
			pointDot: false,
			scaleShowGridLines: true,
			scaleShowHorizontalLines: true,
			scaleShowVerticalLines: false,
			datasetFill: true,
			datasetStroke: true,
			bezierCurve: true,
			legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<datasets.length; i++){%><li><span style=\"background-color:<%=datasets[i].strokeColor%>\"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span></li><%}%></ul>"
		});

		var legend = commits_per_month_chart.generateLegend();
		$('#commits-per-month-legend').html(legend);
	});

}

function render_wiki() 
{
	creole_render_wiki (
		"project_home_mainarea_wiki_text", 
		"project_home_mainarea_wiki", 
		"<?=site_url()?>/wiki/show/<?=$project->id?>/",
		"<?=site_url()?>/wiki/attachment0/<?=$project->id?>/"
	);

	prettyPrint ();

	var ajax_req = $.ajax ({
		url: '<?=site_url()?>/code/history_json/<?=$project->id?>/',
		context: document.body,
		success: show_commits_per_month_graph
	});
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
<li><?=$this->lang->line('Created by')?> <?=$project->createdby?></li>
<li><?=$this->lang->line('Last updated on')?> <?=$project->updatedon?></li>
<li><?=$this->lang->line('Last updated by')?> <?=$project->updatedby?></li>
</ul>
</div>

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Members')?></div>
<ul>
<?php
	$members = $project->members;
	$member_count = count($members);
	$members = array_unique ($members);
	$priority = 0;

	$icons = $this->projects->getUserIcons($members);
	if ($icons === FALSE) $icons = array(); // can't get the icon array for members.

	for ($i = 0; $i < $member_count; $i++)
	{
		if (!array_key_exists($i, $members)) continue;

		$m = $members[$i];
		if ($m == '') continue;

		/*
		$icon_src = '';
		if (array_key_exists($m, $icons))
		{
			// old browsers don't support image data URI.
			$icon_path = CODEPOT_USERICON_DIR . '/' . $icons[$m];
			$icon_image = @file_get_contents($icon_path);
			if ($icon_image)
			{
				$icon_src = sprintf (
					'<img class="user_icon_img" src="data:%s;base64,%s" alt="" /> ',
					mime_content_type($icon_path),
					base64_encode($icon_image)
				);
			}
		}

		print "<li>{$icon_src}{$m}</li>";
		*/
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($m));
		print "<li><img src='{$user_icon_url}' class='user_icon_img' />{$m}</li>";
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
	print anchor ($this->converter->expand($url,$_SERVER) . "/{$project->id}/");
	print '</li>';
}
?>
</ul>
<pre>
<?php //print_r ($urls); ?>
<?php //print_r ($_SERVER); ?>
</pre>
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

<div id="project_home_mainarea_stat">
<?php
//$graph_url = codepot_merge_path (site_url(), "/code/graph/commits-per-month/{$project->id}");
//print "<img src='{$graph_url}' id='project_home_commits_per_month_graph' />";
?>

<div id='commits-per-month-legend'></div>
<canvas id='commits-per-month-canvas'></canvas>

</div> <!-- project_home_mainarea_stat -->

</div> <!-- project_home_mainarea -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 
</div> <!--  project_home_content -->

</body>

</html>

