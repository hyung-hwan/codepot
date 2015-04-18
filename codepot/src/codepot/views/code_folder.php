<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />

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
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.stack.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.tickrotor.js')?>"></script>


<?php
	$file_count = count($file['content']);

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
?>

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

function show_loc_graph (response)
{
	var loc = $.parseJSON(response);
	if (loc == null)
	{
		alert ('Invalid data received');
	}
	else
	{
		var blank = [];
		for (var key in loc) blank.push ([ key, loc[key][1]] );

		var comment = [];
		for (var key in loc) comment.push ([ key, loc[key][2]] );

		var code = [];
		for (var key in loc) code.push ([ key, loc[key][3]] );

		var dataset = 
		[
			{ label: "<?php print $this->lang->line('Code')?>",  data: code },
			{ label: "<?php print $this->lang->line('Comment')?>",  data: comment },
			{ label: "<?php print $this->lang->line('Blank')?>",  data: blank }
		];

		var options = {

			series: {
				stack: true,
				shadowSize: 0,
				bars: { 
					show: true, 
					fill: true,
					align: "center",
					barWidth: 0.8
				},
				lines: { show: false, fill: true },
				points: { show: false }
			},

			grid: { hoverable: true, clickable: true },

			xaxes: [
				{ mode: "categories",
				  autoscaleMargin: 0.05,
				  rotateTicks: ((code.length >= 8)? 135: 0)
				},
			],

			yaxes: { }
		};

		$("#code_folder_mainarea_result_info_loc_graph").width(550).height(400);
		$.plot($("#code_folder_mainarea_result_info_loc_graph"), dataset, options);


		var code_folder_mainarea_result_info_loc_graph_previous_point = null;

		$("#code_folder_mainarea_result_info_loc_graph").bind("plothover", function (event, pos, item) {
			if (item) 
			{
				if (code_folder_mainarea_result_info_loc_graph_previous_point != item.datapoint) 
				{
					code_folder_mainarea_result_info_loc_graph_previous_point = item.datapoint;
					$("#code_folder_mainarea_result_info_loc_graph_tooltip").remove();
					show_tooltip("code_folder_mainarea_result_info_loc_graph_tooltip", item.pageX, item.pageY - 20, item.datapoint[1]);
				}
			} 
			else 
			{
				$("#code_folder_mainarea_result_info_loc_graph_tooltip").remove();
				code_folder_mainarea_result_info_loc_graph_previous_point = null;
			}
		});
	}

	$("#code_folder_mainarea_result_info_loc_button").button("enable");
	//$("#code_folder_mainarea_result_info_loc_progress" ).progressbar().hide();
}

function render_readme()
{
	<?php
	// if the readme file name ends with '.wiki', perform pretty printing
	if (strlen($readme_text) > 0 && substr_compare($readme_file, '.wiki', -5) === 0):
	?>
	creole_render_wiki (
		"code_folder_mainarea_result_readme_text",
		"code_folder_mainarea_result_readme",
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/show/<?php print $project->id?>/"),
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/attachment0/<?php print $project->id?>/")
	);
	prettyPrint();
	<?php endif; ?>
}


$(function () {

<?php if ($file_count > 0): ?>
	<?php
	if ($login['settings'] != NULL && $login['settings']->code_hide_metadata == 'Y')
		print '$("#code_folder_mainarea_result_info").hide();';
	?>

	if ($("#code_folder_mainarea_result_info").is(":visible"))
		btn_label = "<?php print $this->lang->line('Hide metadata')?>";
	else
		btn_label = "<?php print $this->lang->line('Show metadata')?>";

	btn = $("#code_folder_mainarea_metadata_button").button({"label": btn_label}).click (function () {
		
		if ($("#code_folder_mainarea_result_info").is(":visible"))
		{
			$("#code_folder_mainarea_result_info").hide("blind",{},200);
			$("#code_folder_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Show metadata')?>");
		}
		else
		{
			$("#code_folder_mainarea_result_info").show("blind",{},200);
			$("#code_folder_mainarea_metadata_button").button(
				"option", "label", "<?php print $this->lang->line('Hide metadata')?>");
		}
	});

	btn = $("#code_folder_mainarea_result_info_loc_button").button().click (function () {
		$("#code_folder_mainarea_result_info_loc_button").button("disable");
		//$("#code_folder_mainarea_result_info_loc_progress" ).progressbar("value", 0).show();

		//function show_progress ()
		//{
		//	var progress = $("#code_folder_mainarea_result_info_loc_progress");
		//	progress.progressbar ("value", progress.progressbar("value") + 1);
		//	setTimeout (show_progress, 1000);
		//}
		//setTimeout (show_progress, 1000);

		var ajax_req = $.ajax ({
			url: codepot_merge_path (
				"<?php print site_url(); ?>", 
				"/graph/folder_loc_json/<?php print $project->id; ?>/<?php print $this->converter->AsciiToHex($headpath)?><?php print $revreq?>"),
			context: document.body,
			success: show_loc_graph
		});
	});

	//$("#code_folder_mainarea_result_info_loc_progress" ).progressbar().hide();
<?php endif; ?>

	$('#code_folder_search_submit').button().click (function (e) {
		if ($.trim($("#code_folder_search_string").val()) != "")
		{
			$('#code_folder_search_form').submit ();
		}
	});
	/* 
	$('#code_folder_search_form').submit (function(e) {
		if ($.trim($("#code_folder_search_string").val()) === "")
		{
			// prevent submission when the search string is empty.
			e.preventDefault();
		}
	});*/

	$('#code_search_invertedly').button();
	$('#code_search_case_insensitively').button();
	$('#code_search_recursively').button();
	$('#code_search_in_name').button();
	$('#code_search_is_regex').button();
	$('.code_search_option').tooltip();

	render_readme ();
});

</script>

<title><?php 
	if ($headpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($headpath));
?></title>
</head>

<body>

<div class="content" id="code_folder_content">

<!-- ================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- ================================================================== -->

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

<!-- ================================================================== -->

<div class="mainarea" id="code_folder_mainarea">

<div class="title">
<?php
	// print the main anchor for the root folder. 
	// let the anchor text be the project name.
	print anchor (
		"code/file/{$project->id}{$revreqroot}", 
		htmlspecialchars($project->name));

	// explode non-root folder parts to anchors
	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"code/file/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars ($file['fullpath']);
	}
?>
</div>

<div class="infostrip" id="code_folder_mainarea_infostrip">

	<?php 
	print form_open("code/search/{$project->id}/", 'id="code_folder_search_form"');

	if (CODEPOT_SIGNIN_FOR_CODE_SEARCH === FALSE || (isset($login['id']) && $login['id'] != ''))
	{
		print form_hidden('search_folder', set_value('search_folder', $file['fullpath']), 'id="code_folder_search_folder"');
		print form_hidden('search_revision', set_value('search_revision', $revision), 'id="code_folder_search_revision"');
		print form_input(array(
			'name' => 'search_string', 
			'value' => set_value('search_string', ''), 
			'id' =>'code_folder_search_string',
			'placeholder' => $this->lang->line('CODE_SEARCH_STRING')
		));

		print ' ';

		print form_checkbox(array(
			'name'    => 'search_invertedly', 
			'id'      => 'code_search_invertedly',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE,
			'title'   => $this->lang->line('CODE_SEARCH_INVERTEDLY')
		));
		print form_label('v', 'code_search_invertedly', 
			array('class'=>'code_search_option', 'id'=>'code_search_invertedly_label')
		);

		print form_checkbox(array(
			'name'    => 'search_case_insensitively', 
			'id'      => 'code_search_case_insensitively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE,
			'title'   => $this->lang->line('CODE_SEARCH_CASE_INSENSITIVELY')
		));
		print form_label('i', 'code_search_case_insensitively', 
			array('class'=>'code_search_option', 'id'=>'code_search_case_insensitively_label')
		);

		print form_checkbox(array(
			'name'    => 'search_recursively', 
			'id'      => 'code_search_recursively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => TRUE,
			'title'   => $this->lang->line('CODE_SEARCH_RECURSIVELY')
		));
		print form_label('r', 'code_search_recursively', 
			array('class'=>'code_search_option', 'id'=>'code_search_recursively_label')
		);

		print form_checkbox(array(
			'name'    => 'search_in_name', 
			'id'      => 'code_search_in_name',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE,
			'title'   => $this->lang->line('CODE_SEARCH_IN_NAME')
		));
		print form_label('n', 'code_search_in_name',
			array('class'=>'code_search_option', 'id'=>'code_search_in_name_label')
		);

		print form_checkbox(array(
			'name'    => 'search_is_regex', 
			'id'      => 'code_search_is_regex',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE,
			'title'   => $this->lang->line('CODE_SEARCH_IS_REGEX')
		));
		print form_label('x', 'code_search_is_regex',
			array('class'=>'code_search_option', 'id'=>'code_search_is_regex_label')
		);

		print ' ';
		//print form_submit('search_submit', $this->lang->line('Search'), 'id="code_folder_search_submit"');
		printf ('<a id="code_folder_search_submit" href="#">%s</a>', $this->lang->line('Search'));
		print ' | ';
	} 

	$xpar = $this->converter->AsciiTohex ($headpath);
	print anchor ("code/file/{$project->id}/${xpar}/{$prev_revision}", '<<');
	print ' ';

	// anchor to the revision history at the root directory
	print anchor (
		"code/revision/{$project->id}/!/{$file['created_rev']}", 
		sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
	);

	if (!empty($file['created_tag']))
	{
		print ' ';
		printf ('<span class="left_arrow_indicator">%s</span>', htmlspecialchars($file['created_tag']));
	}

	print ' ';
	print anchor ("code/file/{$project->id}/${xpar}/{$next_revision}", '>>');

	if ($file_count > 0)
	{
		print ' | ';
		printf ('<a id="code_folder_mainarea_metadata_button" href="#">%s</a>', $this->lang->line('Metadata'));
	}

	print form_close();
	?>
</div>

<div class="result" id="code_folder_mainarea_result">

<div id="code_folder_mainarea_result_info_loc_progress"></div> 
<div id="code_folder_mainarea_result_info_loc_graph">
</div>

<?php
	function comp_files ($a, $b)
	{
		if ($a['type'] == $b['type'])
		{
			return strcasecmp ($a['name'], $b['name']);
		}

		return ($a['type'] == 'dir')? -1: 1;
	}

	if ($file_count <= 0)
	{
		 print $this->lang->line('MSG_NO_CODE_AVAIL');
	}
	else 
	{
		print '<div class="menu" id="code_folder_mainarea_menu">';
		$xpar = $this->converter->AsciiTohex ($headpath);
		if ($revision > 0 && $revision < $next_revision)
		{
			print anchor ("code/file/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
			print ' | ';
		}

		if ($revision > 0)
		{
			if ($xpar == '') $revtrailer = $revreqroot;
			else $revtrailer = "/{$xpar}{$revreq}";
			print anchor ("code/history/{$project->id}{$revtrailer}", $this->lang->line('History'));
		}
		else
			print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));

		print ' | ';
		print anchor (
			"code/fetch/{$project->id}/${xpar}{$revreq}",
			$this->lang->line('Download'));
		
		print '</div>';

		usort ($file['content'], 'comp_files');

		print '<table id="code_folder_mainarea_result_table">';
		print '<tr class="heading">';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Committer') . '</th>';
		print '<th>' . $this->lang->line('Date') . '</th>';
		print '<th>' . $this->lang->line('Blame') . '</th>';
		print '<th>' . $this->lang->line('Difference') . '</th>';
		print '<th>' . $this->lang->line('Full Difference') . '</th>';
		print '</tr>';

		$rowclasses = array ('even', 'odd');
		$rownum = 0;
		foreach ($file['content'] as $f)
		{
			//$fullpath = $headpath . '/' . $f['name'];
			$fullpath = $file['fullpath'] . '/' . $f['name'];

			$rowclass = $rowclasses[++$rownum % 2];
			if ($f['type'] === 'dir')
			{
				// directory 
				$hexpath = $this->converter->AsciiToHex($fullpath);
				print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td></td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><code>';
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
				print '</code></td>';
				print '<td></td>';
				print '<td></td>';
				print '</tr>';
			}
			else
			{
				// file
				$hexpath = $this->converter->AsciiToHex($fullpath);
				print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td>';
				print $f['size'];
				print '</td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><code>';
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
				print '</code></td>';

				print '<td>';
				print anchor (
					"code/blame/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Blame'));
				print '</td>';
				print '<td>';
				print anchor (
					"code/diff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Difference'));
				print '</td>';
				print '<td>';
				print anchor (
					"code/fulldiff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Full Difference'));
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';

		if (strlen($readme_text) > 0)
		{
			print '<div id="code_folder_mainarea_result_readme">';
			print '<pre id="code_folder_mainarea_result_readme_text">';
			print "\n";
			print htmlspecialchars($readme_text);
			print "\n";
			print '</pre>';
			print '</div>';
		}

		print '<div id="code_folder_mainarea_result_info" class="infobox">';

		print '<div class="title">';
		print $this->lang->line('CODE_COMMIT');
		print '</div>';
		print '<ul>';
		print '<li>';
		printf ($this->lang->line('CODE_MSG_COMMITTED_BY'), $file['last_author']);
		print '</li>';
		print '</ul>';

		print '<div class="title">';
		print $this->lang->line('Message');
		print '</div>';
		print '<pre id="code_folder_mainarea_result_info_logmsg">';
		print htmlspecialchars ($file['logmsg']);
		print '</pre>';

		if (array_key_exists('properties', $file) && count($file['properties']) > 0)
		{
			print '<div class="title">';
			print $this->lang->line('CODE_PROPERTIES');
			print '</div>';

			print '<ul id="code_folder_mainarea_result_info_property_list">';
			foreach ($file['properties'] as $pn => $pv)
			{
				print '<li>';
				print htmlspecialchars($pn);
				if ($pv != '')
				{
					print ' - ';
					print htmlspecialchars($pv);
				}
				print '</li>';
			}
			print '</ul>';
		}


		print '<div class="title">LOC</div>';
		print '<a id="code_folder_mainarea_result_info_loc_button" href="#">';
		print $this->lang->line('Graph');
		print '</a>';

		print '</div>';

	}
?>

</div> <!-- code_folder_mainarea_result -->

</div> <!-- code_folder_mainarea -->

<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->

</div> <!--  code_folder_content -->

</body>

</html>

