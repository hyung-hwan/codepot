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

<script type="text/javascript" src="<?=base_url_make('/js/Chart.js')?>"></script>

<script type="text/javascript">
function show_commits_per_month_graph(log)
{
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
	if (min_year == max_year && min_month == max_month)
	{
		// only 1 data point if this condition is met 
		// insert a fake data point to make at least 2 data points
		if (min_month <= 1)
		{
			min_year--;
			min_month = 12;
		}
		else
		{
			min_month--;
		}
	}
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
	orig_commits_per_month_keys = commits_per_month_keys.slice (0); // clone the array

	var max_value = 0;
	var x_scale = Math.ceil(commits_per_month_keys.length / 12);
	if (x_scale <= 0) x_scale = 1;
	for (i = 0; i < commits_per_month_keys.length; i++)
	{
		var commits = commits_per_month[commits_per_month_keys[i]];
		var committers = committers_per_month[commits_per_month_keys[i]];

		if (commits > max_value) max_value = commits;
		if (committers > max_value) max_value = committers;

		commits_per_month_values.push (commits);
		committers_per_month_values.push (committers);

		// to work around the problem of too many data points.
		// chart.js doesn't provide a means to skip x-labels.
		// empty some points despite the side effect to tooltip titles.
		if (i % x_scale) commits_per_month_keys[i] = '';
	}

	var commits_per_month_data = {
		labels : commits_per_month_keys,

		//////////////////////////////////////////////////
		// this requires HYUNG-HWAN's change to Chart.js
		tooltipLabels : orig_commits_per_month_keys,
		//////////////////////////////////////////////////

		datasets : [
			{
				label: 'Commits per month',
				fillColor : 'rgba(151,187,245,0.2)',
				strokeColor: "rgba(151,187,245,1.0)",
				pointColor: "rgba(151,187,245,1.0)",
				data : commits_per_month_values
			},
			
			{
				label: 'Committers per month',
				fillColor: "rgba(245,187,151,0.2)",
				strokeColor: "rgba(245,187,151,1.0)",
				pointColor: "rgba(245,187,151,1.0)",
				data : committers_per_month_values
			}
		]

	}

	var scale_steps = 5;
	$('#graph_main_commits_per_month_canvas').each (function() {
		var canvas = $(this)[0];
		var ctx = canvas.getContext('2d');
		var commits_per_month_chart = new Chart(ctx).Line(commits_per_month_data, {
			responsive : true,
			maintainAspectRatio: true,
			animation: false,
			pointDot: false,
			scaleShowGridLines: true,
			scaleShowHorizontalLines: true,
			scaleShowVerticalLines: false,
			scaleFontSize: 10,
			scaleFontStyle: 'normal',
			scaleFontColor: 'black',

			scaleOverride: true,
			scaleSteps: scale_steps,
			scaleStepWidth: Math.ceil(max_value / scale_steps),
			scaleStartValue: 0,

			showTooltips: true,
			tooltipFontSize: 10,
			tooltipTitleFontSize: 10,
			datasetFill: true,
			datasetStroke: true,
			datasetStrokeWidth: 1,
			bezierCurve: true,
			legendTemplate : '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"><%if(datasets[i].label){%><%=datasets[i].label%><%}%></span></li><%}%></ul>'
		});

		var legend = commits_per_month_chart.generateLegend();
		$('#graph_main_commits_per_month_legend').html(legend);
	});

}

function show_commits_per_user_graph(log)
{
	var commits_per_user = [], commits_per_user_keys = [], commits_per_user_values = [];
	var commit_share_by_user = [];

	for (var i = 0; i < log.length; i++)
	{
		var author = log[i].author;
		if (!author) author = '';
		
		if (author in commits_per_user) commits_per_user[author]++;
		else commits_per_user[author] = 1;
	}

	for (var key in commits_per_user)
	{
		commits_per_user_keys.push (key);
		commits_per_user_values.push (commits_per_user[key]);

		commit_share_by_user.push (
			{ 
				value: commits_per_user[key],
				label: key,

				//////////////////////////////////////////////////
				// this requires HYUNG-HWAN's change to Chart.js
				//tooltipLabel: key,
				//////////////////////////////////////////////////

				color: ('#' + Math.random().toString(16).substring(2, 8)) // generate random color
			}
		);
	}

	var commits_per_user_data = {
		labels : commits_per_user_keys,

		//////////////////////////////////////////////////
		// this requires HYUNG-HWAN's change to Chart.js
		tooltipLabels : commits_per_user_keys,
		//////////////////////////////////////////////////

		datasets : [
			{
				label: 'Commits per user',
				fillColor : 'rgba(151,187,245,0.2)',
				strokeColor: "rgba(151,187,245,1.0)",
				pointColor: "rgba(151,187,245,1.0)",
				data : commits_per_user_values
			}
		]
	}

	$('#graph_main_commits_per_user_canvas').each (function() {
		var canvas = $(this)[0];
		var ctx = canvas.getContext('2d');
		var commits_per_user_chart = new Chart(ctx).Bar(commits_per_user_data, {
			responsive : true,
			maintainAspectRatio: true,
			animation: false,

			showTooltips: true,
			tooltipFontSize: 10,
			tooltipTitleFontSize: 10
		});
	});

	$('#graph_main_commit_share_by_user_canvas').each (function() {
		var canvas = $(this)[0];
		var ctx = canvas.getContext('2d');
		var commit_share_by_user_chart = new Chart(ctx).Pie(commit_share_by_user, {
			responsive : true,
			maintainAspectRatio: true,
			animation: false
		});

		var legend = commit_share_by_user_chart.generateLegend();
		$('#graph_main_commit_share_by_user_legend').html(legend);
	});
}

function show_all_graphs (response)
{
	var log = $.parseJSON(response);
	if (log == null)
	{
		alert ('Invalid data received');
		return;
	}

	show_commits_per_month_graph (log);
	show_commits_per_user_graph (log);
}

function render_graphs() 
{
	var ajax_req = $.ajax ({
		url: '<?=site_url()?>/graph/history_json/<?=$project->id?>/',
		context: document.body,
		success: show_all_graphs
	});
}
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body onload="render_graphs()">

<div class="content" id="graph_main_content">


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
			'id' => 'graph',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			/*
			array ("project/update/{$project->id}", $this->lang->line('Edit')),
			array ("project/delete/{$project->id}", $this->lang->line('Delete'))
			*/
		)
	)
); 
?>

<!-- /////////////////////////////////////////////////////////////////////// -->

<!--
<div class="sidebar" id="graph_main_sidebar">
<div class="box">
<ul>
<li><?=$this->lang->line('Created on')?> <?=$project->createdon?></li>
<li><?=$this->lang->line('Created by')?> <?=$project->createdby?></li>
<li><?=$this->lang->line('Last updated on')?> <?=$project->updatedon?></li>
<li><?=$this->lang->line('Last updated by')?> <?=$project->updatedby?></li>
</ul>
</div>
</div> --> <!-- graph_main_sidebar -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<div class="mainarea" id="graph_main_mainarea">

<div class="title">
<?=htmlspecialchars($project->name)?>
</div>

<div id="graph_main_commits_per_month">
<div id='graph_main_commits_per_month_legend'></div>
<canvas id='graph_main_commits_per_month_canvas'></canvas>
</div> <!-- graph_main_commits_per_month-->

<div id="graph_main_commits_per_user">
<canvas id='graph_main_commits_per_user_canvas'></canvas>
</div> <!-- graph_main_commits_per_user-->

<div id="graph_main_commit_share_by_user">
<div id='graph_main_commit_share_by_user_legend'></div>
<canvas id='graph_main_commit_share_by_user_canvas'></canvas>
</div> <!-- graph_main_commits_per_user-->

</div> <!-- graph_main_mainarea -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 
</div> <!--  graph_main_content -->

</body>

</html>

