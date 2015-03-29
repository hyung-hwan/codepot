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

<!--[if lte IE 8]><script type="text/javascript" src="<?=base_url_make('/js/excanvas.min.js')?>"></script><![endif]-->
<script type="text/javascript" src="<?=base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery-ui.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery.flot.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery.flot.time.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery.flot.categories.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url_make('/js/jquery.flot.pie.min.js')?>"></script>

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

	for (i = 0; i < commits_per_month_keys.length; i++)
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
			label: "Commits Per Month",
			data: commits_per_month_values,
			//color: "#FF0000"
		},
		{
			label: "Commiters Per Month",
			data: committers_per_month_values,
			//color: "#00FF00"
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
			{ mode: "time" },
			{ mode: "time" }
		],

		yaxes: { }
	}

	$.plot($("#graph_main_commits_per_month"), dataset, options);

	var previousPoint = null;
	$("#graph_main_commits_per_month").bind("plothover", function (event, pos, item) {
		function show_tooltip(x, y, contents) {
		    $('<div id="graph_main_commits_per_month_tooltip">' + contents + '</div>').css( {
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

		if (item) 
		{
			if (previousPoint != item.datapoint) 
			{
				previousPoint = item.datapoint;
				$("#graph_main_commits_per_month_tooltip").remove();
				//show_tooltip(item.pageX, item.pageY, '(' + item.datapoint[0] + ', ' + item.datapoint[1]+')');
				//show_tooltip(item.pageX, item.pageY - 20, item.datapoint[1]);
				show_tooltip(item.pageX, item.pageY - 20, '(' + (new Date(item.datapoint[0])).toISOString().substring(0, 7) + ', ' + item.datapoint[1]+')');
			}
		} 
		else 
		{
			$("#graph_main_commits_per_month_tooltip").remove();
			previousPoint = null;
		}
	});
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
			label: "Commits Per User",
			data: commits_per_user_data
			//color: "#FF0000"
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
				barWidth: 0.1
			}
		},

		//grid: { hoverable: true, clickable: true },

		xaxes: [
			{ mode: "categories" },
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
				fill: true
			}
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

<div id="graph_main_commits_per_month" style="width:50%; height: 400px; margin-bottom: 1em;">
</div> <!-- graph_main_commits_per_month-->

<div id="graph_main_commits_per_user" style="width:50%; height: 400px; margin-bottom: 1em;">
</div> <!-- graph_main_commits_per_user-->

<div id="graph_main_commit_share_by_user" style="width:50%; height: 400px; margin-bottom: 1em;">
</div> <!-- graph_main_commits_per_user-->

</div> <!-- graph_main_mainarea -->


<!-- /////////////////////////////////////////////////////////////////////// -->

<?php $this->load->view ('footer'); ?>

<!-- /////////////////////////////////////////////////////////////////////// -->
 
</div> <!--  graph_main_content -->

</body>

</html>

