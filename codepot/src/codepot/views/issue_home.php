<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

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
$creole_base = site_url() . "/wiki/show/{$project->id}/"; 
// the issue page doesn't exist yet. so use wiki/attachment0 instead of
// issue/file/issueid. the local imange reference like {{xxx.png}}
// won't work. furthermore, the file hasn't been uploaded and isn't available.
$creole_file_base = site_url() . "/wiki/attachment0/{$project->id}/"; 
?>

<script type="text/javascript">
/* <![CDATA[ */
function show_alert (outputMsg, titleMsg) 
{
	$('#issue_home_alert').html(outputMsg).dialog({
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

function AsciiToHex (x) {
	var r="";
	for(i=0; i<x.length; i++)
	{
		var tmp = x.charCodeAt(i).toString(16);
		if (tmp.length == 1) r += "0";
		r += tmp;
	}
	return r;
}


function preview_new_description(input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"issue_home_new_description_preview", 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>",
		true
	);

	prettyPrint ();
}

var work_in_progress = false;
var populated_file_obj = [];
var populated_file_max = 0;

function populate_selected_files ()
{
	var issue_file_desc = {};
	for (var n = 0; n < populated_file_max; n++)
	{
		var f = populated_file_obj[n];
		if (f != null)
		{
			var d = $('#issue_home_new_file_desc_' + n);
			if (d != null) issue_file_desc[f.name] = d.val();
		}
	}

	$('#issue_home_new_file_table').empty();
	populated_file_obj = [];

	var f = $('#issue_home_new_files').get(0);
	var f_no = 0;
	for (var n = 0; n < f.files.length; n++)
	{
		if (f.files[n] != null) 
		{
			var desc = issue_file_desc[f.files[n].name];
			if (desc == null) desc = '';

			$('#issue_home_new_file_table').append (
				codepot_sprintf (
					'<tr id="issue_home_new_file_row_%d"><td><a href="#" id="issue_home_new_file_cancel_%d" onClick="cancel_out_new_file(%d); return false;"><i class="fa fa-trash"></i></a></td><td>%s</td><td><input type="text" id="issue_home_new_file_desc_%d" size="40" value="%s" /></td></tr>', 
					f_no, f_no, f_no, codepot_htmlspecialchars(f.files[n].name), f_no, codepot_addslashes(desc)
				)
			);

			populated_file_obj[f_no] = f.files[n];
			f_no++;
		}
	}

	populated_file_max = f_no;
}

function cancel_out_new_file (no)
{
	$('#issue_home_new_file_row_' + no).remove ();
	populated_file_obj[no] = null;
}

$(function () { 
<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$('#issue_home_new_files').change (function () {
		populate_selected_files ();
	});

	$('#issue_home_new_description_tabs').tabs ();
	$('#issue_home_new_description_tabs').bind ('tabsshow', function (event, ui) {
		if (ui.index == 1) preview_new_description ($('#issue_home_new_description').val());
	});

	$('#issue_home_new_form').dialog (
		{
			title: '<?php print $this->lang->line('New');?>',
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
						for (var i = 0; i <= populated_file_max; i++)
						{

							var f = populated_file_obj[i];
							if (f != null)
							{
								form_data.append ('issue_new_file_' + f_no, f);

								var d = $('#issue_home_new_file_desc_' + i);
								if (d != null) form_data.append('issue_new_file_desc_' + f_no, d.val());

								f_no++;
							}
						}

						form_data.append ('issue_new_file_count', f_no);
						form_data.append ('issue_new_type', $('#issue_home_new_type').val());
						form_data.append ('issue_new_summary', $('#issue_home_new_summary').val());
						form_data.append ('issue_new_description', $('#issue_home_new_description').val());

						$('#issue_home_new_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/issue/xhr_create/{$project->id}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#issue_home_new_form').dialog('enable');
								$('#issue_home_new_form').dialog('close');
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
								$('#issue_home_new_form').dialog('enable');
								$('#issue_home_new_form').dialog('close');
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
					$('#issue_home_new_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);
<?php endif; ?>

	$("#issue_home_search_form").dialog ({
		title: '<?php print $this->lang->line('Search')?>',
		autoOpen: false,
		modal: true,
		width: '80%',
		buttons: { 
			'<?php print $this->lang->line('OK')?>': function () { 
				$(this).dialog('close'); 
				var filter = AsciiToHex($('#issue_search_form').serialize());
				var url='<?php print site_url()?>/issue/home/<?php print $project->id?>/' + filter;	

				$('body').append('<form id="magic_form" method="get" action="'+url+'"></form>');
				$('#magic_form').submit();
			},
			'<?php print $this->lang->line('Cancel')?>': function () { 
				$(this).dialog('close'); 
			}
		},
		close: function() {}
	}); 


<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$("#issue_home_new_button").button().click (
		function () { 
			$('#issue_home_new_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
<?php endif; ?>

	$("#issue_home_search_button").button().click (
		function () { 
			$('#issue_home_search_form').dialog('open'); 
			return false; // prevent the default behavior
		}
	);
});
/* ]]> */
</script>

<title><?php print htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="issue_home_content">

<!-- ============================================================ -->

<?php $this->load->view ('taskbar'); ?>

<!-- ============================================================ -->

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
			// DEPRECATED
			//array ("issue/create/{$project->id}", '<i class="fa fa-plus"></i> ' . $this->lang->line('New'), 'issue_home_new')
		)
	)
); 
?>

<!-- ============================================================ -->

<div class="mainarea" id="issue_home_mainarea">

<div class="codepot-title-band" id="issue_home_title_band">
	<div class="title"><?php print $this->lang->line('Issues')?></div>

	<div class="actions">
		<?php printf ($this->lang->line('ISSUE_MSG_TOTAL_NUM_ISSUES'), $total_num_issues); ?>
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		<a id="issue_home_new_button" href='#'><?php print $this->lang->line('New')?></a>
		<?php endif; ?>
		<a id="issue_home_search_button" href='#'><?php print $this->lang->line('Search')?></a>
	</div>
	<div style='clear: both;'></div>
</div>

<div id="issue_home_result" class="codepot-relative-container-view">
<?php
if (empty($issues))
{
	print $this->lang->line('ISSUE_MSG_NO_ISSUES_AVAILABLE');
}
else
{
	print '<table id="issue_home_result_table" class="codepot-full-width-table codepot-spacious-table">';
	print '<tr class="heading">';
	print '<th class="id">' . $this->lang->line('ID') . '</th>';
	print '<th class="type">' . $this->lang->line('Type') . '</th>';
	print '<th class="status">' . $this->lang->line('Status') . '</th>';
	print '<th class="priority">' . $this->lang->line('Priority') . '</th>';
	print '<th class="owner">' . $this->lang->line('Owner') . '</th>';
	print '<th class="summary">' . $this->lang->line('Summary') . '</th>';
	print '</tr>';

	foreach ($issues as $issue)
	{
		$hexid = $this->converter->AsciiToHex ($issue->id);

		print "<tr class='{$issue->status}'>";

		print '<td class="id">';
		print anchor ("issue/show/{$project->id}/{$hexid}", htmlspecialchars($issue->id));
		print '</td>';

		print '<td class="type">';
		print (htmlspecialchars(
			array_key_exists($issue->type, $issue_type_array)?
				$issue_type_array[$issue->type]: $issue->type));
		print '</td>';

		print '<td class="status">';
		print (htmlspecialchars(
			array_key_exists($issue->status, $issue_status_array)?
				$issue_status_array[$issue->status]: $issue->status));
		print '</td>';

		print '<td class="priority">';
		print (htmlspecialchars(
			array_key_exists($issue->priority, $issue_priority_array)?
				$issue_priority_array[$issue->priority]: $issue->priority));
		print '</td>';

		print '<td class="owner">';
		print htmlspecialchars($issue->owner);
		print '</td>';

		print '<td class="summary">';
		print htmlspecialchars($issue->summary);
		print '</td>';

		print '</tr>';
	}

	print '</table>';

	print '<div id="issue_home_result_pages">';
	print $page_links;
	print '</div>';
}
?>
</div> <!-- issue_home_result -->

<?php if (isset($login['id']) && $login['id'] != ''): ?>
<div id='issue_home_new_form'>
	<div style='line-height: 2em;'>
		<?php
		print form_dropdown (
			'issue_home_new_type', 
			$issue_type_array,
			set_value('issue_home_new_type', ''),
			'id="issue_home_new_type"'
		);
		?>

		<input type='text' id='issue_home_new_summary' name='issue_home_new_summary' size='50' placeholder='<?php print $this->lang->line('Summary'); ?>'/>
	</div>

	<div id='issue_home_new_description_tabs' style='width:100%;'>
		<ul>
			<li><a href='#issue_home_new_description_input'><?php print $this->lang->line('Description'); ?></a></li>
			<li><a href='#issue_home_new_description_preview'><?php print $this->lang->line('Preview'); ?></a></li>
		</ul>

		<div id='issue_home_new_description_input'>
			<textarea type='textarea' id='issue_home_new_description' name='issue_home_new_description' rows=24 cols=100 style='width:100%;'></textarea>

			<div style='margin-top: 0.1em;'>
			<?php print $this->lang->line('Attachments'); ?>
			<input type='file' id='issue_home_new_files' name='issue_home_new_files' multiple='' autocomplete='off' style='color: transparent;' />
			<table id='issue_home_new_file_table'></table>
			</div>
		</div>
		<div id='issue_home_new_description_preview' class='codepot-styled-text-preview'>
		</div>
	</div>
</div>
<?php endif; ?>

<div id="issue_home_search_form">
	<?php
		$issue_type_array[''] = $this->lang->line('All');
		$issue_status_array[''] = $this->lang->line('All');
		$issue_priority_array[''] = $this->lang->line('All');
	?>
	<form id="issue_search_form" action="">
		<div>
			<?php print form_label ($this->lang->line('Type'), 'issue_search_type')
			?>
			<?php print form_dropdown('type',
				$issue_type_array,
				set_value('type', $search->type), 
				'id="issue_search_type"')
			?>
	
			<?php print form_label ($this->lang->line('Status'), 'issue_search_status')
			?>
			<?php print form_dropdown('status',
				$issue_status_array,
				set_value('status', $search->status), 'id="issue_search_status"')
			?>

			<?php print form_label ($this->lang->line('Priority'), 'issue_search_priority')
			?>
			<?php print form_dropdown('priority',
				$issue_priority_array,
				set_value('priority', $search->priority),
				'id="issue_search_priority"')
			?>
		</div>


		<div>
			<?php print form_label ($this->lang->line('Owner'), 'issue_search_owner')
			?>
			<?php print form_input('owner',
				set_value('owner', $search->owner),
				'id="issue_search_owner"')
			?>
		</div>

		<div>
			<?php print form_label ($this->lang->line('Summary'), 'issue_search_summary')
			?>
			<?php print form_input('summary',
				set_value('summary', $search->summary),
				'id="issue_search_summary" size="50"')
			?>
		</div>

	</form>
</div>

<div id='issue_home_alert'></div>

</div> <!-- issue_home_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- issue_home_content -->

<!-- ============================================================ -->

<?php $this->load->view ('footer'); ?>

<!-- ============================================================ -->



</body>
</html>
