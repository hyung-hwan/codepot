<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
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

<script type="text/javascript" src="<?php print base_url_make('/js/jqueryui-editable.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jqueryui-editable.css')?>" />

<!--[if lte IE 8]><script type="text/javascript" src="<?php print base_url_make('/js/excanvas.min.js')?>"></script><![endif]-->
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.time.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.categories.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.stack.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.flot.tickrotor.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/d3.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/CodeFlower.js')?>"></script>

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

	$hex_headpath = $this->converter->AsciiToHex($headpath);
?>

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#code_folder_mainarea_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			"OK": function () {
				$(this).dialog("close");
			}
		}
	});
}

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

function show_loc_by_lang_graph (response)
{
	var loc = $.parseJSON(response);
	if (loc == null)
	{
		show_alert ('Invalid data received', "<?php print $this->lang->line('Error')?>");
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

		$("#code_folder_mainarea_result_loc_by_lang_graph").width(550).height(400);
		$.plot($("#code_folder_mainarea_result_loc_by_lang_graph"), dataset, options);

		var code_folder_mainarea_result_loc_by_lang_graph_previous_point = null;

		$("#code_folder_mainarea_result_loc_by_lang_graph").bind("plothover", function (event, pos, item) {
			if (item) 
			{
				if (code_folder_mainarea_result_loc_by_lang_graph_previous_point != item.datapoint) 
				{
					code_folder_mainarea_result_loc_by_lang_graph_previous_point = item.datapoint;
					$("#code_folder_mainarea_result_loc_by_lang_graph_tooltip").remove();
					show_tooltip("code_folder_mainarea_result_loc_by_lang_graph_tooltip", item.pageX, item.pageY - 20, item.datapoint[1]);
				}
			} 
			else 
			{
				$("#code_folder_mainarea_result_loc_by_lang_graph_tooltip").remove();
				code_folder_mainarea_result_loc_by_lang_graph_previous_point = null;
			}
		});
	}

	$("#code_folder_mainarea_result_info_loc_by_lang_button").button("enable");
	$("#code_folder_mainarea_result_info_loc_by_lang_spin" ).removeClass ("fa-cog fa-spin");
}

function show_loc_by_file_graph (response)
{
	var loc = $.parseJSON(response);
	if (loc == null)
	{
		show_alert ('Invalid data received', "<?php print $this->lang->line('Error')?>");
	}
	else
	{
		var f = new CodeFlower("#code_folder_mainarea_result_loc_by_file_graph", 550, 400);
		f.update (loc);
	}

	$("#code_folder_mainarea_result_info_loc_by_file_button").button("enable");
	$("#code_folder_mainarea_result_info_loc_by_file_spin" ).removeClass ("fa-cog fa-spin");
}

function render_readme()
{
	<?php
	// if the readme file name ends with '.wiki', perform wiki formatting and pretty printing
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

var new_item_no = 0;
var import_in_progress = false;

function get_new_item_html(no, type, name)
{
	return codepot_sprintf ('<li><input type="%s" id="code_folder_mainarea_new_item_%s_%d" name="code_folder_new_item_%s_%d" /></li>',  type, name, no, name, no);
}

$(function () {

<?php if (isset($login['id']) && $login['id'] != ''): ?>

	new_item_no = 0;
	$('#code_folder_mainarea_new_file_form_item_list').append (get_new_item_html(new_item_no, 'file', 'file'));
	$('#code_folder_mainarea_new_dir_form_item_list').append (get_new_item_html(new_item_no, 'text', 'dir'));

	$("#code_folder_mainarea_new_form_tabs").tabs ();

	$('#code_folder_mainarea_new_form_div').dialog (
		{
			title: '<?php print $this->lang->line('New');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'More': function () {
					if (import_in_progress) return;

					++new_item_no;
					$('#code_folder_mainarea_new_file_form_item_list').append (get_new_item_html(new_item_no, 'file', 'file'));
					$('#code_folder_mainarea_new_dir_form_item_list').append (get_new_item_html(new_item_no, 'text', 'dir'));
				},
				'<?php print $this->lang->line('OK')?>': function () {
					if (import_in_progress) return;

					$('#code_folder_mainarea_new_item_count').val (new_item_no + 1);

					if (!!window.FormData)
					{
						// FormData is supported
						import_in_progress = true;

						var form_data = new FormData();

						form_data.append ('code_folder_new_message', $('#code_folder_mainarea_new_message').val());
						form_data.append ('code_folder_new_item_count', $('#code_folder_mainarea_new_item_count').val());
						form_data.append ('code_folder_new_item_unzip', $('#code_folder_mainarea_new_item_unzip').val());
						for (var i = 0; i <= new_item_no; i++)
						{
							var f = $('#code_folder_mainarea_new_item_file_' + i).get(0).files[0];
							if (f != null) form_data.append ('code_folder_new_item_file_' + i, f);

							var d = $('#code_folder_mainarea_new_item_dir_' + i).val();
							if (d != null && d != '') form_data.append ('code_folder_new_item_dir_' + i, d);
						}

						$('#code_folder_mainarea_new_form_div').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_import/{$project->id}/{$hex_headpath}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								import_in_progress = false;
								$('#code_folder_mainarea_new_form_div').dialog('enable');
								$('#code_folder_mainarea_new_form_div').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/file/{$project->id}/{$hex_headpath}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								import_in_progress = false;
								$('#code_folder_mainarea_new_form_div').dialog('enable');
								$('#code_folder_mainarea_new_form_div').dialog('close');
								show_alert ('Failed - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						$('#code_folder_mainarea_new_form_div').dialog('close');
						$('#code_folder_mainarea_new_form').submit();
					}

				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (import_in_progress) return;
					$('#code_folder_mainarea_new_form_div').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !import_in_progress;
			}
		}
	);

	$('#code_folder_mainarea_new_button').button().click (function() {
		$('#code_folder_mainarea_new_form_div').dialog('open');
	});

<?php endif; ?>

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

	btn = $("#code_folder_mainarea_result_info_loc_by_lang_button").button().click (function () {
		$("#code_folder_mainarea_result_info_loc_by_lang_button").button("disable");
		$("#code_folder_mainarea_result_info_loc_by_lang_spin").addClass ("fa-cog fa-spin");

		var ajax_req = $.ajax ({
			url: codepot_merge_path (
				"<?php print site_url(); ?>", 
				"/graph/enjson_loc_by_lang/<?php print $project->id; ?>/<?php print $this->converter->AsciiToHex($headpath)?><?php print $revreq?>"),
			context: document.body,
			success: show_loc_by_lang_graph
		});
	});

	btn = $("#code_folder_mainarea_result_info_loc_by_file_button").button().click (function () {
		$("#code_folder_mainarea_result_info_loc_by_file_button").button("disable");
		$("#code_folder_mainarea_result_info_loc_by_file_spin").addClass ("fa-cog fa-spin");
		var ajax_req = $.ajax ({
			url: codepot_merge_path (
				"<?php print site_url(); ?>", 
				"/graph/enjson_loc_by_file/<?php print $project->id; ?>/<?php print $this->converter->AsciiToHex($headpath)?><?php print $revreq?>"),
			context: document.body,
			success: show_loc_by_file_graph
		});
	});
<?php endif; ?>

	$('#code_search_submit').button().click (function () {
		if ($.trim($("#code_search_string").val()) != "")
		{
			$('#code_search_submit').button ('disable');
			$('#code_search_string_icon').addClass("fa-cog fa-spin");
			$('#code_search_form').submit ();
		}
	});
	/* 
	$('#code_search_form').submit (function(e) {
		if ($.trim($("#code_search_string").val()) === "")
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

	$('#code_search_wildcard').text($('input[name=search_wildcard_pattern]').val());

	$('#code_search_wildcard').editable({
		type: 'text', 
		title: '<?php print $this->lang->line('CODE_SEARCH_WILDCARD') ?>',
		placement: 'bottom',
		success: function(response, newValue) {
			$('input[name=search_wildcard_pattern]').val(newValue);
		}
	});

	render_readme ();


<?php if (strlen($popup_error_message) > 0): ?>
	show_alert (<?php print codepot_json_encode('<pre>' . htmlspecialchars($popup_error_message) . '</pre>'); ?>, "<?php print $this->lang->line('Error')?>");
<?php endif; ?>
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
	print form_open("code/search/{$project->id}/", 'id="code_search_form"');

	if (CODEPOT_SIGNIN_FOR_CODE_SEARCH === FALSE || (isset($login['id']) && $login['id'] != ''))
	{
		print form_hidden('search_folder', set_value('search_folder', $file['fullpath']));
		print form_hidden('search_revision', set_value('search_revision', $revision));
		
		print '<i id="code_search_string_icon" class="fa"></i> ';
		print form_input(array(
			'name' => 'search_string', 
			'value' => set_value('search_string', ''), 
			'id' =>'code_search_string',
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

		print '<a id="code_search_wildcard" href="#"></a>';
		print form_hidden('search_wildcard_pattern', set_value('search_wildcard_pattern', $wildcard_pattern));

		print ' ';
		//print form_submit('search_submit', $this->lang->line('Search'), 'id="code_search_submit"');
		printf ('<a id="code_search_submit" href="#">%s</a>', $this->lang->line('Search'));
		print ' | ';
	} 

	print anchor ("code/file/{$project->id}/${hex_headpath}/{$prev_revision}", '<i class="fa fa-arrow-circle-left"></i>');
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
	print anchor ("code/file/{$project->id}/${hex_headpath}/{$next_revision}", '<i class="fa fa-arrow-circle-right"></i>');


	if ((isset($login['id']) && $login['id'] != '') || $file_count > 0)
	{
		print ' | ';

		if (isset($login['id']) && $login['id'] != '')
		{
			printf ('<a id="code_folder_mainarea_new_button" href="#">%s</a>', $this->lang->line('New'));
		}

		if ($file_count > 0)
		{
			printf ('<a id="code_folder_mainarea_metadata_button" href="#">%s</a>', $this->lang->line('Metadata'));
		}
	}

	print form_close();
	?>
</div>

<div class="result" id="code_folder_mainarea_result">

<div id="code_folder_mainarea_result_loc_by_lang_graph"></div>
<div id="code_folder_mainarea_result_loc_by_file_graph"></div>

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
		if ($revision > 0 && $revision < $next_revision)
		{
			$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head revision');
			print anchor ("code/file/{$project->id}/{$hex_headpath}", $head_revision_text);
			print ' | ';
		}

		$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
		$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
		$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
		$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
		$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame'); 

		if ($revision > 0)
		{
			if ($hex_headpath == '') $revtrailer = $revreqroot;
			else $revtrailer = "/{$hex_headpath}{$revreq}";
			print anchor ("code/history/{$project->id}{$revtrailer}", $history_anchor_text);
		}
		else
		{
			print anchor ("code/history/{$project->id}/{$hex_headpath}", $history_anchor_text);
		}

		print ' | ';
		print anchor ("code/fetch/{$project->id}/${hex_headpath}{$revreq}", $download_anchor_text);
		
		print '</div>';

		usort ($file['content'], 'comp_files');

		print '<table id="code_folder_mainarea_result_table" class="fit-width-result-table">';
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
				print '<i class="fa fa-folder-o"></i> ';
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
				print '<td></td>';
				print '</tr>';
			}
			else
			{
				// file
				$hexpath = $this->converter->AsciiToHex($fullpath);
				print "<tr class='{$rowclass}'>";
				print '<td>';
				$fa_type = codepot_get_fa_file_type ($f['name']);
				print "<i class='fa fa-{$fa_type}-o'></i> ";
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
				print anchor ("code/blame/{$project->id}/{$hexpath}{$revreq}", $blame_anchor_text);
				print '</td>';
				print '<td>';
				print anchor ("code/diff/{$project->id}/{$hexpath}{$revreq}", $diff_anchor_text);
				print '</td>';
				print '<td>';
				print anchor ("code/fulldiff/{$project->id}/{$hexpath}{$revreq}", $fulldiff_anchor_text);
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';

		if (strlen($readme_text) > 0)
		{
			print '<div id="code_folder_mainarea_result_readme">';
			// the pre division is gone when rendered as a wiki text.
			// so is the pre-wrapped class. so let me put the class 
			// regardless of the text type.
			print '<pre id="code_folder_mainarea_result_readme_text" class="pre-wrapped">';
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
		print '<pre id="code_folder_mainarea_result_info_logmsg" class="pre-wrapped">';
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
		print '<a id="code_folder_mainarea_result_info_loc_by_lang_button" href="#">';
		print '<i id="code_folder_mainarea_result_info_loc_by_lang_spin" class="fa"></i>';
		print $this->lang->line('Language');
		print '</a>';

		print ' ';

		print '<a id="code_folder_mainarea_result_info_loc_by_file_button" href="#">';
		print '<i id="code_folder_mainarea_result_info_loc_by_file_spin" class="fa"></i>';
		print $this->lang->line('File'); 
		print '</a>';

		print '</div>';

	}
?>

</div> <!-- code_folder_mainarea_result -->



<?php if (isset($login['id']) && $login['id'] != ''): ?>

<div id="code_folder_mainarea_new_form_div">
	<?php 
	$attrs = array ('id' => 'code_folder_mainarea_new_form');
	// this form posts to the head revision regardless of the revision being shown currently
	print form_open_multipart("code/file/{$project->id}/".$this->converter->AsciiToHex($headpath), $attrs);
	?>
	<input type='hidden' name='code_folder_new_item_count' id='code_folder_mainarea_new_item_count' />

	<div><?php print $this->lang->line('Message'); ?>:</div>
	<div><textarea type='textarea' id='code_folder_mainarea_new_message' name='code_folder_new_message' style='width:100%;'></textarea></div>
	
	<div id="code_folder_mainarea_new_form_tabs" style="width:100%;">
		<ul>
			<li><a href="#code_folder_mainarea_new_file_div">File</a></li>
			<li><a href="#code_folder_mainarea_new_dir_div">Directory</a></li>
		</ul>
		<div id="code_folder_mainarea_new_file_div">
			<div><input type='checkbox' id='code_folder_mainarea_new_item_unzip' name='code_folder_new_item_unzip' value='yes'/><?php print $this->lang->line('Unzip a zip file'); ?></div>
			<div><ul id='code_folder_mainarea_new_file_form_item_list'></ul></div>
		</div>
		<div id="code_folder_mainarea_new_dir_div">
			<div><ul id='code_folder_mainarea_new_dir_form_item_list'></ul></div>
		</div>
	</div>

	<?php print form_close();?>
</div>

<?php endif; ?>

<div id='code_folder_mainarea_alert'></div>

</div> <!-- code_folder_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_folder_content -->

<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->

</body>

</html>

