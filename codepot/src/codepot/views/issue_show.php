<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/issue.css')?>" />
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

<?php
$hex_issue_id = $this->converter->AsciiToHex ($issue->id);
$issue_file_count = count ($issue->files);

$creole_base = site_url() . "/wiki/show/{$project->id}/"; 
$creole_file_base = site_url() . "/issue/file/{$project->id}/{$issue->id}/"; 
?>

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#issue_show_alert').html(outputMsg).dialog({
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

$.widget("ui.combobox", {
	_create: function() {
		var self = this;
		var select = this.element.hide();
		var input = $("<input>").insertAfter(select);

		input.autocomplete({
			source: function(request, response) {
				var matcher = new RegExp(request.term, "i");
				response(select.children("option").map(function() {
					var text = $(this).text();
					if (!request.term || matcher.test(text))
						return {
							id: $(this).val(),
							label: text.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + request.term.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>"),
							value: text
						};
				}));
			},
			delay: 0,
			select: function(e, ui) {
				if (!ui.item) {
					// remove invalid value, as it didn't match anything
					$(this).val("");
					return false;
				}
				$(this).focus();
				select.val(ui.item.id);
				self._trigger("selected", null, {
					item: select.find("[value='" + ui.item.id + "']")
				});
				
				},
			minLength: 0
		})

		var fn = function() {
			// close if already visible
			//if (input.autocomplete("widget").is(":visible")) {
			//	input.autocomplete("close");
			//	return;
			//}
			// pass empty string as value to search for, displaying all results
			input.autocomplete("search", "");
			input.focus();
		};

		input.click (fn);
		input.focusin (fn);

		input.addClass("ui-widget ui-widget-content");
	}
});


var populated_file_obj_for_adding = [];
var populated_file_max_for_adding = 0;

function populate_selected_files_for_adding ()
{
	var file_desc = {};
	for (var n = 0; n < populated_file_max_for_adding; n++)
	{
		var f = populated_file_obj_for_adding[n];
		if (f != null)
		{
			var d = $('#issue_show_add_file_desc_' + n);
			if (d != null) file_desc[f.name] = d.val();
		}
	}

	$('#issue_show_add_file_table').empty();
	populated_file_obj_for_adding = [];

	var f = $('#issue_show_add_files').get(0);
	var f_no = 0;
	for (var n = 0; n < f.files.length; n++)
	{
		if (f.files[n] != null) 
		{
			var desc = file_desc[f.files[n].name];
			if (desc == null) desc = '';

			$('#issue_show_add_file_table').append (
				codepot_sprintf (
					'<tr id="issue_show_add_file_row_%d"><td><a href="#" id="issue_show_add_file_cancel_%d" onClick="cancel_out_add_file(%d); return false;"><i class="fa fa-trash"></i></a></td><td>%s</td><td><input type="text" id="issue_show_add_file_desc_%d" size="40" value="%s" /></td></tr>', 
					f_no, f_no, f_no, codepot_htmlspecialchars(f.files[n].name), f_no, codepot_addslashes(desc)
				)
			);

			populated_file_obj_for_adding[f_no] = f.files[n];
			f_no++;
		}
	}

	populated_file_max_for_adding = f_no;
}

function cancel_out_add_file (no)
{
	$('#issue_show_add_file_row_' + no).remove ();
	populated_file_obj_for_adding[no] = null;
}

function kill_edit_file (no)
{
	var n = $('#issue_show_edit_file_name_' + no);
	var d = $('#issue_show_edit_file_desc_' + no);
	if (n && d)
	{
		if (d.prop('disabled'))
		{
			n.css ('text-decoration', '');
			d.prop ('disabled', false);
		}
		else
		{
			n.css ('text-decoration', 'line-through');
			d.prop ('disabled', true);
		}
	}
}


function preview_edit_description (input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"issue_show_edit_description_preview", 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/",
		true // raw
	);

	prettyPrint ();
}

var work_in_progress = false;

var original_file_name = [
<?php
	for ($i = 0; $i < $issue_file_count; $i++)
	{
		$f = $issue->files[$i];
		printf ("%s\t'%s'", (($i == 0)? '': ",\n"), addslashes($f->filename));
	}
	print "\n";
?>
];

var original_file_desc = [
	<?php
	for ($i = 0; $i < $issue_file_count; $i++)
	{
		$f = $issue->files[$i];
		printf ("%s\t'%s'", (($i == 0)? '': ",\n"), addslashes($f->description));
	}
	print "\n";
	?>
];

$(function () { 

	$('#issue_show_state').accordion({
		collapsible: true,
		heightStyle: "content"
	});

<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$('#issue_show_edit_description_tabs').tabs ();
	$('#issue_show_edit_description_tabs').bind ('tabsshow', function (event, ui) {
		if (ui.index == 1) preview_edit_description ($('#issue_show_edit_description').val());
	});

	$('#issue_show_edit_form').dialog (
		{
			title: '<?php print $this->lang->line('Edit');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {

				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						form_data.append ('issue_edit_summary', $('#issue_show_edit_summary').val());
						form_data.append ('issue_edit_description', $('#issue_show_edit_description').val());

						$('#issue_show_edit_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/issue/xhr_update/{$project->id}/{$hex_issue_id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#issue_show_edit_form').dialog('enable');
								$('#issue_show_edit_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/issue/show/{$project->id}/{$hex_issue_id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#issue_show_edit_form').dialog('enable');
								$('#issue_show_edit_form').dialog('close');
								var errmsg = '';
								if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
								if (errmsg == '' && textStatus != null) errmsg = textStatus;
								if (errmsg == '') errmsg = 'Unknown error';
								show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (work_in_progress) return;
					$('#issue_show_edit_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#issue_show_delete_form').dialog (
		{
			title: '<?php print $this->lang->line('Delete');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f = $('#issue_show_delete_confirm');
						if (f != null && f.is(':checked')) form_data.append ('issue_delete_confirm', 'Y');

						$('#issue_show_delete_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/issue/xhr_delete/{$project->id}/{$hex_issue_id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#issue_show_delete_form').dialog('enable');
								$('#issue_show_delete_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/issue/home/{$project->id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#issue_show_delete_form').dialog('enable');
								$('#issue_show_delete_form').dialog('close');
								show_alert ('Failed - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (work_in_progress) return;
					$('#issue_show_delete_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);



	$('#issue_show_add_files').change (function () {
		populate_selected_files_for_adding ();
	});

	$('#issue_show_add_file_form').dialog (
		{
			title: '<?php print $this->lang->line('Add');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f_no = 0;
						for (var i = 0; i <= populated_file_max_for_adding; i++)
						{
							var f = populated_file_obj_for_adding[i];
							if (f != null)
							{
								form_data.append ('issue_add_file_' + f_no, f);

								var d = $('#issue_show_add_file_desc_' + i);
								if (d != null) form_data.append('issue_add_file_desc_' + f_no, d.val());
								f_no++;
							}
						}
						form_data.append ('issue_add_file_count', f_no);

						$('#issue_show_add_file_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/issue/xhr_add_file/{$project->id}/{$hex_issue_id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#issue_show_add_file_form').dialog('enable');
								$('#issue_show_add_file_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/issue/show/{$project->id}/{$hex_issue_id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#issue_show_add_file_form').dialog('enable');
								$('#issue_show_add_file_form').dialog('close');
								show_alert ('Failed - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (work_in_progress) return;
					$('#issue_show_add_file_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#issue_show_edit_file_form').dialog (
		{
			title: '<?php print $this->lang->line('Edit');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (work_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						work_in_progress = true;

						var form_data = new FormData();

						var f_no = 0;
						for (var i = 0; i <= <?php print $issue_file_count; ?>; i++)
						{
							var n = $('#issue_show_edit_file_name_' + i);
							var d = $('#issue_show_edit_file_desc_' + i);

							if (n && d)
							{
								if (d.prop('disabled'))
								{
									form_data.append ('issue_edit_file_name_' + f_no, original_file_name[i]);
									form_data.append('issue_edit_file_kill_' + f_no, 'yes');
									f_no++;
								}
								else if (d.val() != original_file_desc[i])
								{
									form_data.append ('issue_edit_file_name_' + f_no, original_file_name[i]);
									form_data.append('issue_edit_file_desc_' + f_no, d.val());
									f_no++;
								}
							}
						}
						form_data.append ('issue_edit_file_count', f_no);

						$('#issue_show_edit_file_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/issue/xhr_edit_file/{$project->id}/{$hex_issue_id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#issue_show_edit_file_form').dialog('enable');
								$('#issue_show_edit_file_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/issue/show/{$project->id}/{$hex_issue_id}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#issue_show_edit_file_form').dialog('enable');
								$('#issue_show_edit_file_form').dialog('close');
								show_alert ('Failed - ' + errorThrown, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (work_in_progress) return;
					$('#issue_show_edit_file_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

<?php endif; ?>

	/*
	$("#issue_change_type").combobox();
	$("#issue_change_status").combobox();
	$("#issue_change_priority").combobox();
	*/
	/*$("#issue_change_owner").combobox();*/

	$("#issue_show_change_form").dialog (
		{
			title: '<?php print $this->lang->line('Change')?>',
			autoOpen: false,
			modal: true,
			width: '85%',
			buttons: { 
				'<?php print $this->lang->line('OK')?>': function () { 
					var comment = $('#issue_change_comment');
					if (comment.val().trim().length <= 0)
					{
						comment.addClass ('ui-state-error');
						setTimeout (function () {
							comment.removeClass ('ui-state-error', 500);
						}, 500);
					}
					else
					{
						$(this).dialog('close'); 
						$('#issue_change').val ('change');
						$('#issue_change_form').submit ();
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () { 
					$(this).dialog('close'); 
				}
			},
			close: function() { }
		} 
	); 

<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$('#issue_show_edit_button').button().click (
		function () { 
			$('#issue_show_edit_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
	$('#issue_show_delete_button').button().click (
		function () { 
			$('#issue_show_delete_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);

	$('#issue_show_add_file_button').button().click (
		function() {
			$('#issue_show_add_file_form').dialog('open');
			return false;
		}
	);

	$('#issue_show_edit_file_button').button().click (
		function() {
			$('#issue_show_edit_file_form').dialog('open');
			return false;
		}
	);
<?php endif; ?>

	$('#issue_show_change_form_open').button().click (
		function () { 
			$('#issue_show_change_form').dialog('open'); 
			return false;
		}
	);

	$('#issue_show_undo_change_confirm').dialog (
		{
			title: '<?php print $this->lang->line('Undo')?>',
			resizable: false,
			autoOpen: false,
			modal: true,
			buttons: { 
				'<?php print $this->lang->line('OK')?>': function () { 
					$('#issue_change').val ('undo');
					$('#issue_change_form').submit ();
					$(this).dialog('close'); 
				},
				'<?php print $this->lang->line('Cancel')?>': function () { 
					$(this).dialog('close'); 
				}
			},
			close: function() { }
		} 
	);

	$('#issue_show_undo_change').button().click (
		function () { 
			$('#issue_show_undo_change_confirm').dialog('open'); 
			return false;
		}
	);

	$('#issue_change_comment_preview_button').button().click(
		function () {
			preview_issue_change_comment ($('#issue_change_comment').val());
			return false;
		}
	);

	render_wiki();
});
</script>

<title><?php print htmlspecialchars($project->name)?> - <?php print $this->lang->line('Issue')?> <?php print htmlspecialchars($issue->id)?></title>
</head>

<body>

<div class="content" id="issue_show_content">

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
			'id' => 'issue',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			//DEPRECATED
			//array ("issue/create/{$project->id}", '<i class="fa fa-plus"></i> ' . $this->lang->line('New')),
			//array ("issue/update/{$project->id}/{$hex_issue_id}", '<i class="fa fa-edit"></i> ' . $this->lang->line('Edit')),
			//array ("issue/delete/{$project->id}/{$hex_issue_id}", '<i class="fa fa-trash"></i> ' . $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="issue_show_mainarea">

<div class="codepot-title-band" id="issue_show_title_band">
	<div class="title">
		<?php print $this->lang->line('Issue')?> <?php print htmlspecialchars($issue->id)?>: 
		<?php print htmlspecialchars($issue->summary)?>
	</div>
	<div class="actions">
	<?php
		if (isset($login['id']) && $login['id'] != '')
		{
			print '<a id="issue_show_edit_button" href="#">';
			print $this->lang->line('Edit');
			print '</a>';
			print '<a id="issue_show_delete_button" href="#">';
			print $this->lang->line('Delete');
			print '</a>';
		}
	?>
	</div>
	<div style='clear: both;'></div>
</div>

<div id='issue_show_state' class='collapsible-box'>
	<div id='issue_show_state_header' class='collapsible-box-header'><?php print $this->lang->line('State')?></div>
	<div id='issue_show_state_body'>
	<ul>
	<?php

		$type = array_key_exists($issue->type, $issue_type_array)? 
			$issue_type_array[$issue->type]: $issue->type;

		$status = array_key_exists($issue->status, $issue_status_array)? 
				$issue_status_array[$issue->status]: $issue->status;

		$priority = array_key_exists($issue->priority, $issue_priority_array)? 
				$issue_priority_array[$issue->priority]: $issue->priority;

		printf ('<li class="issue-type-%s">', $issue->type);
		print $this->lang->line('Type');
		print ': '; 
		print htmlspecialchars($type);
		print '</li>';

		printf ('<li class="issue-status-%s">', $issue->status);
		print $this->lang->line('Status');
		print ': '; 
		print htmlspecialchars($status);
		print '</li>';

		printf ('<li class="issue-priority-%s">', $issue->priority);
		print $this->lang->line('Priority');
		print ': '; 
		print htmlspecialchars($priority);
		print '</li>';

		print '<li class="issue-owner">';
		if ($issue->owner != '')
		{
			print $this->lang->line('Owner');
			print ': '; 
			print htmlspecialchars($issue->owner);
			print '</li>';
		}
	?>
	</ul>
	</div>
</div>

<div id="issue_show_result" class="codepot-relative-container-view">

	<div id="issue_show_description" class="codepot-styled-text-view">
	<pre id="issue_show_description_pre" style="visibility: hidden"><?php print htmlspecialchars($issue->description); ?></pre>
	</div> <!-- issue_show_description -->

	<div id="issue_show_files">
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		<i class='fa fa-plug'></i> <?php print $this->lang->line('Attachments'); ?>
		<a id="issue_show_add_file_button" href='#'><?php print $this->lang->line('Add')?></a>
		<a id="issue_show_edit_file_button" href='#'><?php print $this->lang->line('Edit')?></a>
		<?php elseif (!empty($issue->files)): ?>
		<i class='fa fa-plug'></i> <?php print $this->lang->line('Attachments'); ?>
		<?php endif; ?>

		<?php if (!empty($issue->files)): ?>
		<ul>
		<?php
			for ($i = 0; $i < $issue_file_count; $i++)
			{
				$f = $issue->files[$i];
				$hexname = $this->converter->AsciiToHex ($f->filename);
				print '<li>';
				print anchor (
					"issue/file/{$project->id}/{$issue->id}/{$hexname}", 
					htmlspecialchars($f->filename)
				);

				if (!empty($f->description)) printf (' - %s', htmlspecialchars($f->description));
				print '</li>';
			}
		?>
		</ul>
		<?php endif; ?>
	</div>
</div>
<div id="issue_show_changes_strip" class="codepot-infostrip">
	<?php
	print '<span class="title">';
	print $this->lang->line('Change log');
	print '</span>';


	print '<a id="issue_show_change_form_open" href="#">';
	print $this->lang->line('Change');
	print '</a>';

	print ' ';

	print '<a id="issue_show_undo_change" href="#">';
	print $this->lang->line('Undo');
	print '</a>';
	?>
</div>

<div id="issue_show_changes">
	<?php
	$commentno = 0;

	$msgfmt_changed_from_to = $this->lang->line ('ISSUE_MSG_CHANGED_X_FROM_Y_TO_Z');
	$msgfmt_changed_to = $this->lang->line ('ISSUE_MSG_CHANGED_X_TO_Z');
	$count = count($issue->changes);

	print '<table id="issue_show_changes_table" class="codepot-full-width-table">';
	while ($count > 1)
	{
		$new = $issue->changes[--$count];
		$old = $issue->changes[$count-1];

		print '<tr>';

		print '<td class="date">'; 
		print codepot_dbdatetodispdate($new->updatedon);
		print '</td>';

		print '<td class="updater">'; 
		print htmlspecialchars($new->updatedby);
		print '</td>';

		print '<td class="details">';
		if ($new->comment != "")
		{
			print "<div id='issue_show_changes_comment_{$commentno}' class='codepot-styled-text-view'>";
			print "<pre id='issue_show_changes_comment_pre_{$commentno}'>";
			print htmlspecialchars($new->comment);
			print '</pre>';
			print '</div>';
			$commentno++;
		}

		print '<div class="list">';
		print '<ul>';
		if ($new->type != $old->type)
		{
			printf ("<li>{$msgfmt_changed_from_to}</li>", 
				strtolower($this->lang->line('Type')),
				htmlspecialchars(
					array_key_exists($old->type, $issue_type_array)? 
					$issue_type_array[$old->type]: $old->type),
				htmlspecialchars(
					array_key_exists($new->type, $issue_type_array)? 
					$issue_type_array[$new->type]: $new->type));
		}

		if ($new->status != $old->status)
		{
			printf ("<li>{$msgfmt_changed_from_to}</li>", 
				strtolower($this->lang->line('Status')),
				htmlspecialchars(
					array_key_exists($old->status, $issue_status_array)? 
					$issue_status_array[$old->status]: $old->status),
				htmlspecialchars(
					array_key_exists($new->status, $issue_status_array)? 
					$issue_status_array[$new->status]: $new->status));
		}

		if ($new->priority != $old->priority)
		{
			printf ("<li>{$msgfmt_changed_from_to}</li>", 
				strtolower($this->lang->line('Priority')),
				htmlspecialchars(
					array_key_exists($old->priority, $issue_priority_array)? 
					$issue_priority_array[$old->priority]: $old->priority),
				htmlspecialchars(
					array_key_exists($new->priority, $issue_priority_array)? 
					$issue_priority_array[$new->priority]: $new->priority));
		}

		if ($new->owner != $old->owner)
		{
			if ($old->owner == '')
			{
				printf ("<li>{$msgfmt_changed_to}</li>", 
					strtolower($this->lang->line('Owner')),
					htmlspecialchars($new->owner));
			}
			else
			{
				printf ("<li>{$msgfmt_changed_from_to}</li>", 
					strtolower($this->lang->line('Owner')),
					htmlspecialchars($old->owner), htmlspecialchars($new->owner));
			}
		}
		print '</ul>';
		print '</div>';

		print '</td>';
		print '</tr>';
	}

	print '<tr>';
	print '<td class="date">'; 
	print codepot_dbdatetodispdate($issue->createdon);
	print '</td>';

	print '<td class="updater">'; 
	print htmlspecialchars($issue->createdby);
	print '</td>';

	print '<td class="details">';
	print $this->lang->line('ISSUE_MSG_CREATED');
	print '</td>';

	print '</tr>';

	print '</table>';
	?>
</div> <!-- issue_show_changes -->

<?php if (isset($login['id']) && $login['id'] != ''): ?>
<div id='issue_show_edit_form'>
	<div style='line-height: 2em;'>
		<?php
		print form_dropdown (
			'issue_show_edit_type', 
			$issue_type_array,
			set_value('issue_show_edit_type', $issue->type),
			'id="issue_show_edit_type" disabled="disabled"'
		);
		?>
		<input type='text' id='issue_show_edit_summary' name='issue_show_edit_summary' size='50' placeholder='<?php print $this->lang->line('Summary'); ?>' value='<?php print addslashes($issue->summary); ?>'/>
	</div>

	<div id='issue_show_edit_description_tabs' style='width:100%;'>
		<ul>
			<li><a href='#issue_show_edit_description_input'><?php print $this->lang->line('Description'); ?></a></li>
			<li><a href='#issue_show_edit_description_preview'><?php print $this->lang->line('Preview'); ?></a></li>
		</ul>

		<div id='issue_show_edit_description_input'>
			<textarea type='textarea' id='issue_show_edit_description' name='issue_show_edit_description' rows=24 cols=100 style='width:100%;'><?php print htmlspecialchars($issue->description); ?></textarea>
		</div>
		<div id='issue_show_edit_description_preview' class='codepot-styled-text-preview'>
		</div>
	</div>
</div>

<div id='issue_show_delete_form'>
	<input type='checkbox' id='issue_show_delete_confirm' />
	<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS') . ' - ' . $issue->id . ': ' . htmlspecialchars($issue->summary); ?>
</div>

<div id='issue_show_add_file_form'>
	<div id='issue_show_add_file_input'>
		<input type='file' id='issue_show_add_files' name='issue_show_add_files' multiple='' autocomplete='off' style='color: transparent;' />
		<table id='issue_show_add_file_table'></table>
	</div>
</div>

<div id='issue_show_edit_file_form'>

	<table>
	<?php

	for ($i = 0; $i < $issue_file_count; $i++)
	{
		$f = $issue->files[$i];
		print '<tr><td>';
		printf ('<a href="#" onClick="kill_edit_file(%d); return false;"><i class="fa fa-trash"></i></a>', $i);
		print '</td><td>';
		printf ('<span id="issue_show_edit_file_name_%d">%s</span>', $i, htmlspecialchars($f->filename));
		print '</td><td>';
		printf ('<input type="text" id="issue_show_edit_file_desc_%d" value="%s" size="40" autocomplete="off" />', $i, addslashes($f->description));
		print '</td></tr>';
	}
	?>
	</table>
</div>

<?php endif; ?>

<div id="issue_show_change_form">

	<?php print form_open("issue/show/{$project->id}/{$hex_issue_id}/", 'id="issue_change_form"')?>

		<input type='hidden' name='issue_change' id='issue_change' value='change' />

		<div>
			<?php print form_label ($this->lang->line('Type'),
				'issue_change_type')
			?>
			<?php print form_dropdown('issue_change_type', 
				$issue_type_array,
				set_value('issue_change_type', $issue->type),
				'id="issue_change_type"')
			?>

			<?php print form_label ($this->lang->line('Status'),
				'issue_change_status')
			?>
			<?php print form_dropdown('issue_change_status', 
				$issue_status_array,
				set_value('issue_change_status', $issue->status),
				'id="issue_change_status"')
			?>

			<?php print form_label ($this->lang->line('Priority'),
				'issue_change_priority')
			?>

			<?php print form_dropdown (
				'issue_change_priority', 
				$issue_priority_array,
				set_value('issue_change_priority', $issue->priority),
				'id="issue_change_priority"')
			?>
		</div>

		<div>
		<?php
			print form_label ($this->lang->line('Owner'), 'issue_change_owner');

			$owner_array = array ();
			$found = FALSE;
			foreach ($project->members as $t) 
			{
				if ($issue->owner == $t) $found = TRUE;
				$owner_array[$t] = $t;
			}
			if ($found === FALSE) $owner_array[$issue->owner] = $issue->owner;

			print form_dropdown (
				'issue_change_owner', 
				$owner_array,
				set_value('issue_change_owner', $issue->owner),
				'id="issue_change_owner"');
			?>
		</div>

		<div>
			<?php print form_label ($this->lang->line('Comment'), 'issue_change_comment')?>
			<a href='#' id='issue_change_comment_preview_button'><?php print $this->lang->line('Preview')?></a>
		</div>
		<div>
			<?php
				$xdata = array (
					'name' => 'issue_change_comment',
					'value' => set_value ('issue_change_comment', ''),
					'id' => 'issue_change_comment',
					'rows' => 10,
					'cols' => 80
				);
				print form_textarea ($xdata);
			?>
		</div>

		<div id='issue_change_comment_preview' class='codepot-styled-text-preview'></div>
	<?php print form_close()?>
</div> <!-- issue_show_change_form -->


<div id="issue_show_undo_change_confirm">
	<?php print $this->lang->line ('ISSUE_MSG_CONFIRM_UNDO')?>
</div>

<div id='issue_show_alert'></div>

</div> <!-- issue_show_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  issue_show_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"issue_show_description_pre", 
		"issue_show_description", 
		"<?php print $creole_base?>",
		"<?php print $creole_file_base?>"
	);

	<?php
	if ($commentno > 0)
	{
		for ($xxx = 0; $xxx < $commentno; $xxx++)
		{
			print "creole_render_wiki (
				'issue_show_changes_comment_pre_{$xxx}', 
				'issue_show_changes_comment_{$xxx}', 
				'{$creole_base}',
				'{$creole_file_base}');";
		}
	}
	?>

	prettyPrint ();
}

function preview_issue_change_comment(input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"issue_change_comment_preview", 
		"<?php print $creole_base?>",
		"<?php print $creole_file_base?>",
		true
	);

	prettyPrint ();
}
</script>

</body>

</html>

