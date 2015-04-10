<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="<?php print $project->id?>" />
<meta name="description" content="<?php print htmlspecialchars($project->summary)?>" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/project.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<!--[if lte IE 8]><script type="text/javascript" src="<?php print base_url_make('/js/excanvas.min.js')?>"></script><![endif]-->
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.time.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.categories.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.pie.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.stack.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.tickrotor.js')?>"></script>

<script type="text/javascript">
function show_tooltip(id, x, y, contents) {
	$('<div id="' + id + '">' + contents + '</div>').css( {
		position: 'absolute',
		display: 'none',
		top: y + 5,
		left: x + 5,
		border: '1px solid #fdd',
		padding: '2px',
		'background-color': '#fee',
		'font-size': '0.8em',
		'font-family': 'inherit',
		opacity: 0.80
	}).appendTo("body").fadeIn(200);
}

function show_commits_per_month_graph(log)
{
	var min_date = '9999-99', max_date = '0000-00';
	var commits_per_month = [], commits_per_month_keys = [], commits_per_month_values = [];
	var committers_per_month = [], committers_per_month_values = []; 
	var committer_list_per_month = [], committer_table = [];
	var commits_per_month_count = 0;

	for (var i = 0; i < log.length; i++)
	{
		var date = log[i].date;
		if (date)
		{
			date = date.substring (0, 7);
			if (date in commits_per_month) commits_per_month[date]++;
			else commits_per_month[date] = 1;

			if (log[i].author)
			{
				var date_author = date + '-' + log[i].author;
				if (date_author in committer_list_per_month)
					committer_list_per_month[date_author]++;
				else
					committer_list_per_month[date_author] = 1;


				if (!(log[i].author in committer_table))
					committer_table[log[i].author] = [];

				if (date in committer_table[log[i].author])
				{
					committer_table[log[i].author][date]++;
				}
				else
				{
					committer_table[log[i].author][date] = 1;
				}
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

			for (var author in committer_table)
			{
				if (!(date in committer_table[author]))
				{
					committer_table[author][date] = 0;
				}
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

	for (var i = 0; i < commits_per_month_keys.length; i++)
	{
		var commits = commits_per_month[commits_per_month_keys[i]];
		var committers = committers_per_month[commits_per_month_keys[i]];

		var time = (new Date(commits_per_month_keys[i] + "-01")).getTime();
		commits_per_month_values.push ([time, commits]);
		committers_per_month_values.push ([time, committers]);
	}

	var dataset =
	[
		{
			label: "Total Commits Per Month",
			data: commits_per_month_values
		},
		{
			label: "Total Committers Per Month",
			data: committers_per_month_values
		}
	];

	var options =
	{
		series: {
			shadowSize: 0,
			lines: { show: true, fill: true /*, lineWidth: 2*/ },
			points: { show: false /*,lineWidth: 1*/ }
		},

		grid: { hoverable: true, clickable: true },

		xaxes: [
			{ mode: "time", minTickSize: [1, "month"] },
			{ mode: "time", minTickSize: [1, "month"] }
		],

		yaxes: { }
	}

	$.plot($("#graph_main_commits_per_month"), dataset, options);

	var graph_main_commits_per_month_previous_point = null;
	$("#graph_main_commits_per_month").bind("plothover", function (event, pos, item) {
		if (item) 
		{
			if (graph_main_commits_per_month_previous_point != item.datapoint) 
			{
				var datestr = (new Date(item.datapoint[0])).toISOString().substring(0, 7);
				graph_main_commits_per_month_previous_point = item.datapoint;
				$("#graph_main_commits_per_month_tooltip").remove();

				//show_tooltip(item.pageX, item.pageY, '(' + item.datapoint[0] + ', ' + item.datapoint[1]+')');
				show_tooltip ("graph_main_commits_per_month_tooltip",
				              item.pageX, item.pageY - 20,
				              '(' + datestr + ',' + item.datapoint[1] + ')');
			}
		} 
		else 
		{
			$("#graph_main_commits_per_month_tooltip").remove();
			graph_main_commits_per_month_previous_point = null;
		}
	});


	////////////////////////////////////////////////////////////////////////////////
	dataset = [];
	for (var author in committer_table)
	{
		var committer_data = [];
		for (var i = 0; i < commits_per_month_keys.length; i++)
		{
			var date = commits_per_month_keys[i];
			var time = (new Date(date + "-01")).getTime();
			committer_data.push ([time, committer_table[author][date]]);
		}
		dataset.push ({ label: author,  data: committer_data });
	}
	options = {
		series: {
			stack: true,
			shadowSize: 0,
			bars: { 
				show: true, 
				fill: true,
				align: "center",
				barWidth: 1000 * 60 * 60 * 24 * 10
			},
			lines: { show: false, fill: true /*, lineWidth: 2*/ },
			points: { show: false /*,lineWidth: 1*/ }
		},

		grid: { hoverable: true, clickable: true },

		xaxes: [
			{ mode: "time", minTickSize: [1, "month"] }
		],

		yaxes: { }
		
	};
	$.plot($("#graph_main_committer_dissect_per_month"), dataset, options);

	var graph_main_committer_dissect_per_month_previous_point = null;
	$("#graph_main_committer_dissect_per_month").bind("plothover", function (event, pos, item) {
		if (item) 
		{
			if (graph_main_committer_dissect_per_month_previous_point != item.datapoint) 
			{
				var datestr = (new Date(item.datapoint[0])).toISOString().substring(0, 7);
				graph_main_committer_dissect_per_month_previous_point = item.datapoint;
				$("#graph_main_committer_dissect_per_month_tooltip").remove();
				show_tooltip ("graph_main_committer_dissect_per_month_tooltip", 
				              item.pageX, item.pageY - 20,
				              '(' + datestr + ',' + item.datapoint[1] + ')');
			}
		} 
		else 
		{
			$("#graph_main_committer_dissect_per_month_tooltip").remove();
			graph_main_committer_dissect_per_month_previous_point = null;
		}
	});
}

function labelFormatter(label, series) 
{
	return "<div style='font-size:8pt; text-align:center; padding:2px; color:white;'>" + label + "<br/>" + Math.round(series.percent) + "%</div>";
}

function show_commits_per_user_graph(log)
{
	var commits_per_user = [];
	var commits_per_user_data = [];
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
		commits_per_user_data.push ([key, commits_per_user[key]]);
		commit_share_by_user.push ({ 
			label: key,
			data: commits_per_user[key],
		});
	}

	var dataset =
	[
		{
			label: "Total Commits Per User",
			data: commits_per_user_data
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

		//grid: { hoverable: true, clickable: true },

		xaxes: [
			{ mode: "categories",
			  autoscaleMargin: 0.05,
			  rotateTicks: ((commits_per_user_data.length >= 8)? 135: 0)
			}
		],

		yaxes: { }
	};

	$.plot($("#graph_main_commits_per_user"), dataset, options);


	options =
	{
		series: {
			shadowSize: 0,
			pie: { 
				show: true, 
				innerRadius: 0.1,
				label: {
					show: true,
					radius: 7/10,
					formatter: labelFormatter,
					background: { opacity: 0.8 }
				}
			}
		},
		legend: {
			show: true
		}
	};
	$.plot($("#graph_main_commit_share_by_user"), commit_share_by_user, options);
}


function show_all_graphs (response)
{
	var log = $.parseJSON(response);
	if (log == null)
	{
		alert ('Invalid data received');
	}
	else if (log.length > 0)
	{
		show_commits_per_month_graph (log);
		show_commits_per_user_graph (log);
	}
	else
	{
		//$("#graph_main_commits_per_month").text("No data available");
		alert ('No data available');
	}
}

function render_graphs() 
{
	var ajax_req = $.ajax ({
		url: '<?php print site_url()?>/graph/history_json/<?php print $project->id?>/',
		context: document.body,
		success: show_all_graphs
	});
}
</script>

<title><?php print htmlspecialchars($project->name)?></title>
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
<li><?php print $this->lang->line('Created on')?> <?php print $project->createdon?></li>
<li><?php print $this->lang->line('Created by')?> <?php print $project->createdby?></li>
<li><?php print $this->lang->line('Last updated on')?> <?php print $project->updatedon?></li>
<li><?php print $this->lang->line('Last updated by')?> <?php print $project->updatedby?></li>
</ul>
</div>
</div> --> <!-- graph_main_sidebar -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<div class="mainarea" id="graph_main_mainarea">

<div class="title">
<?php print htmlspecialchars($project->name)?>
</div>

<div>

<div id="graph_main_commits_per_month" style="width:550px; height: 400px; margin-bottom: 1em; float: left; position: relative;">
</div>

<div id="graph_main_committer_dissect_per_month" style="width:550px; height: 400px; margin-bottom: 1em; float: left; position: relative;">
</div> 

<div id="graph_main_commits_per_user" style="width:550px; height: 400px; margin-bottom: 1em; float: left; position: relative;">
</div>

<div id="graph_main_commit_share_by_user" style="width:550px; height: 400px; margin-bottom: 1em; float: left; position: relative;">
</div>

</div>

</div> <!-- graph_main_mainarea -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 
</div> <!--  graph_main_content -->

</body>

</html>

