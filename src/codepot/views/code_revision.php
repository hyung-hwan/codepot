<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

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

<?php
	$hex_headpath = $this->converter->AsciiToHex(($headpath == '')? '.': $headpath);

	if ($revision <= 0)
	{
		$revreq = '';
		$revreqroot = '';

		$history_path = "/code/history/{$project->id}/{$hex_headpath}";
		$code_path = "/code/file/{$project->id}/{$hex_headpath}";
	}
	else
	{
		$revreq = "/{$file['created_rev']}";
		$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;

		if ($hex_headpath == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$hex_headpath}{$revreq}";
		$history_path = "/code/history/{$project->id}{$revtrailer}";
		$code_path = "/code/file/{$project->id}{$revtrailer}";
	}

	$creole_base = site_url() . "/wiki/show/{$project->id}/"; 
	$creole_file_base = site_url() . "/wiki/attachment0/{$project->id}/"; 
?>

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#code_revision_mainarea_alert').html(outputMsg).dialog({
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

function preview_new_review_comment (input_text)
{
	creole_render_wiki_with_input_text (
		input_text,
		"code_revision_new_review_comment_preview", 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/",
		true
	);

	prettyPrint ();
}

function preview_edit_review_comment (input_text, no)
{
	creole_render_wiki_with_input_text (
		input_text,
		"code_revision_edit_review_comment_preview_" + no, 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/",
		true
	);

	prettyPrint ();
}

var work_in_progress = false;

<?php $review_count = count($reviews); ?>
<?php $is_loggedin = ($login['id'] != ''); ?>
<?php $can_edit = ($is_loggedin && $login['id'] == $file['history']['author']); ?>

$(function() {

	$("#code_revision_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});

	$("#code_revision_code_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $code_path; ?>'));
		return false;
	});

<?php if ($can_edit): ?>
	$('#code_revision_edit_revision_message_form').dialog (
		{
			title: '<?php print $this->lang->line('Message');?>',
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

						form_data.append ('code_edit_revision_message', $('#code_revision_edit_revision_message').val());

						$('#code_revision_edit_revision_message_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_edit_revision_message/{$project->id}/{$revreq}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#code_revision_edit_revision_message_form').dialog('enable');
								$('#code_revision_edit_revision_message_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/revision/{$project->id}/{$hex_headpath}{$revreq}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#code_revision_edit_revision_message_form').dialog('enable');
								$('#code_revision_edit_revision_message_form').dialog('close');
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
					$('#code_revision_edit_revision_message_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#code_revision_edit_revision_message_button').button().click (
		function () {
			$('#code_revision_edit_revision_message_form').dialog('open');
			return false;
		}
	);
<?php endif; ?>

<?php if ($is_loggedin): ?>
	$('#code_revision_edit_revision_tag_form').dialog (
		{
			title: '<?php print $this->lang->line('Tag');?>',
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

						form_data.append ('code_edit_revision_tag', $('#code_revision_edit_revision_tag').val());

						$('#code_revision_edit_revision_tag_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_edit_revision_tag/{$project->id}/{$revreq}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#code_revision_edit_revision_tag_form').dialog('enable');
								$('#code_revision_edit_revision_tag_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/revision/{$project->id}/{$hex_headpath}{$revreq}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#code_revision_edit_revision_tag_form').dialog('enable');
								$('#code_revision_edit_revision_tag_form').dialog('close');
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
					$('#code_revision_edit_revision_tag_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$('#code_revision_edit_revision_tag_button').button().click (
		function () {
			$('#code_revision_edit_revision_tag_form').dialog('open');
			return false;
		}
	);

	$('#code_revision_new_review_comment_tabs').tabs ();
	$('#code_revision_new_review_comment_tabs').bind ('tabsshow', function (event, ui) {
		if (ui.index == 1) preview_new_review_comment ($('#code_revision_new_review_comment').val());
	});

	$('#code_revision_new_review_comment_form').dialog (
		{
			title: '<?php print $this->lang->line('Comment');?>',
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

						form_data.append ('code_new_review_comment', $('#code_revision_new_review_comment').val());
						form_data.append ('code_new_review_url', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/revision/{$project->id}/{$hex_headpath}{$revreq}"; ?>'));

						$('#code_revision_new_review_comment_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_new_review_comment/{$project->id}/{$revreq}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								work_in_progress = false;
								$('#code_revision_new_review_comment_form').dialog('enable');
								$('#code_revision_new_review_comment_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/revision/{$project->id}/{$hex_headpath}{$revreq}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								work_in_progress = false;
								$('#code_revision_new_review_comment_form').dialog('enable');
								$('#code_revision_new_review_comment_form').dialog('close');
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
					$('#code_revision_new_review_comment_form').dialog('close');
				}
			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !work_in_progress;
			}
		}
	);

	$("#code_revision_new_review_comment_button").button().click (
		function () {
			$('#code_revision_new_review_comment_form').dialog('open');
			return false;
		}
	);

	function make_edit_review_comment_ok_function (no)
	{
		var form_name = '#code_revision_edit_review_comment_form_' + no;

		return function () {
			if (work_in_progress) return;

			if (!!window.FormData)
			{
				// FormData is supported
				work_in_progress = true;

				var form_data = new FormData();

				form_data.append ('code_edit_review_no', no);
				form_data.append ('code_edit_review_comment', $('#code_revision_edit_review_comment_' + no).val());

				$(form_name).dialog('disable');
				$.ajax({
					url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_edit_review_comment/{$project->id}/{$revreq}"; ?>'),
					type: 'POST',
					data: form_data,
					mimeType: 'multipart/form-data',
					contentType: false,
					processData: false,
					cache: false,

					success: function (data, textStatus, jqXHR) { 
						work_in_progress = false;
						$(form_name).dialog('enable');
						$(form_name).dialog('close');
						if (data == 'ok') 
						{
							// refresh the page
							$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/revision/{$project->id}/{$hex_headpath}{$revreq}"; ?>'));
						}
						else
						{
							show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
						}
					},

					error: function (jqXHR, textStatus, errorThrown) { 
						work_in_progress = false;
						$(form_name).dialog('enable');
						$(form_name).dialog('close');
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
		};
	}

	<?php
	for ($i = 0; $i < $review_count; )
	{
		$rc = $reviews[$i];
		$i++;
		if ($login['id'] == $rc->updatedby)
		{
			$edit_title = $this->lang->line('Comment') . " {$i}";
			$label_ok = $this->lang->line('OK');
			$label_cancel = $this->lang->line('Cancel');
			print ("
				$('#code_revision_edit_review_comment_tabs_{$i}').tabs ();
				$('#code_revision_edit_review_comment_tabs_{$i}').bind ('tabsshow', function (event, ui) {
					if (ui.index == 1) preview_edit_review_comment ($('#code_revision_edit_review_comment_{$i}').val(), {$i});
				});

				$('#code_revision_edit_review_comment_form_{$i}').dialog (
					{
						title: '{$edit_title}',
						width: 'auto',
						height: 'auto',
						resizable: false,
						autoOpen: false,
						modal: true,
						buttons: {
							'{$label_ok}': make_edit_review_comment_ok_function ({$i}),
							'{$label_cancel}': function () {
								if (work_in_progress) return;
								$('#code_revision_edit_review_comment_form_{$i}').dialog('close');
							}
						},
						beforeClose: function() { 
							// if importing is in progress, prevent dialog closing
							return !work_in_progress;
						}
					}
				);

				$('#code_revision_edit_review_comment_button_{$i}').button().click (
					function () {
						$('#code_revision_edit_review_comment_form_{$i}').dialog('open');
						return false;
					}
				)
			");
		}
	}
	?>
<?php endif; ?>
});


function render_wiki()
{
	<?php 
	print "for (i = 0; i < $review_count; i++) {\n";
	?>

	creole_render_wiki (
		"code_revision_mainarea_review_comment_text_" + (i + 1) , 
		"code_revision_mainarea_review_comment_" + (i + 1), 
		"<?php print $creole_base; ?>",
		"<?php print $creole_file_base; ?>/",
		false
	);

	<?php
	print "}\n";
	?>

	prettyPrint ();
}

function hide_unneeded_divs()
{
	// hide the properties division if its table contains no rows
	var nrows = $('#code_revision_result_properties_table tr').length;
	if (nrows <= 0) $('#code_revision_result_properties').hide();
}

$(function() {
	$("#code_revision_metadata").accordion ({
		collapsible: true,
		heightStyle: "content"
	});

	$("#code_revision_result_files").accordion ({
		collapsible: true,
		heightStyle: "content"
	});

	$("#code_revision_result_properties").accordion ({
		collapsible: true,
		heightStyle: "content"
	});

	$("#code_revision_result_comments").accordion ({
		collapsible: true,
		heightStyle: "content"
	});

	hide_unneeded_divs ();
	render_wiki ();
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

<div class="content" id="code_revision_content">

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
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="code_revision_mainarea">

<?php
$history = $file['history'];
?>

<div class="codepot-title-band" id="code_revision_title_band">
	<div class="title">
	<?php
		print anchor (
			"code/revision/{$project->id}{$revreqroot}",
			htmlspecialchars($project->id));

		$exps = explode ('/', $headpath);
		$expsize = count($exps);
		$par = '';
		for ($i = 1; $i < $expsize; $i++)
		{
			$par .= '/' . $exps[$i];
			$xpar = $this->converter->AsciiToHex ($par);

			print '/';
			print anchor (
				"code/revision/{$project->id}/{$xpar}{$revreq}",
				htmlspecialchars($exps[$i]));
		}

		if ($headpath != $file['fullpath'])
		{
			// this comparsion doesn't work well for a file.
			// $file['fullpath'] for a file doesn't include the directory.
			// is this a bug of peclsvn? never mind. it's ok to show the file name again.
			//
			// [created_rev] => 322
			// [name] => subversionmodel.php
			// [type] => file
			// [fullpath] => subversionmodel.php

			print ' - ';
			print htmlspecialchars($file['fullpath']);
		}
	?>
	</div>

	<div class="actions">
		<?php
		print anchor ("code/revision/{$project->id}/${hex_headpath}/{$prev_revision}", '<i class="fa fa-arrow-circle-left"></i>');
		print ' ';

		printf ('%s %s',  $this->lang->line('Revision'), $history['rev']);
		if (!empty($history['tag']))
		{
			print ' ';
			print ('<span class="left_arrow_indicator">');
			print htmlspecialchars($history['tag']);
			print ('</span>');
		}

		print ' ';
		print anchor ("code/revision/{$project->id}/${hex_headpath}/{$next_revision}", '<i class="fa fa-arrow-circle-right"></i>');

		if ($is_loggedin)
		{
			print ' ';
			print '<span class="anchor">';
			print anchor ("#", $this->lang->line('Tag'), array ('id' => 'code_revision_edit_revision_tag_button'));
			print '</span>';
		}
		?>
	</div>

	<div style="clear: both;"></div>
</div>

<div id="code_revision_metadata" class="collapsible-box">
	<div id="code_revision_metadata_header" class="collapsible-box-header" >
		<?php
		print '<div class="metadata-committer">';
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($history['author']));
		print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
		print htmlspecialchars ($history['author']);
		print '</div>';

		print '<div class="metadata-menu">';
		if ($can_edit) 
		{
			print '<span class="anchor">';
			print anchor ("#", $this->lang->line('Edit'),
					 array ('id' => 'code_revision_edit_revision_message_button'));
			print '</span>';
		}

		$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
		print anchor ("#", $history_anchor_text, 'id="code_revision_history_button"');

		$history_anchor_text = '<i class="fa fa-code"></i> ' . $this->lang->line('Code');
		print anchor ("#", $history_anchor_text, 'id="code_revision_code_button"');
		print '</div>';

		print '<div class="metadata-commit-date">';
		printf ('[%s] ', $history['rev']);
		print codepot_unixtimetodispdate (strtotime($history['date']));
		print '</div>';
		?>
		
		<div style='clear: both'></div>
	</div>


	<div id="code_revision_metadata_body" class='codepot-metadata-collapsible-body'>
		<div class="codepot-plain-text-view">
			<?php
				$transformed_message = htmlspecialchars($history['msg']);
				foreach ($related_issues as $ri)
				{
					$hex_issueid = $this->converter->AsciiToHex ($ri->issueid);

					// handle [[#IXXX]]
					$transformed_message = preg_replace (
						"/\[\[(#I{$ri->issueid})\]\]/", 
						'[[' . anchor ("/issue/show/{$ri->projectid}/{$hex_issueid}", "\${1}", "class='codepot-issue-type-{$ri->type}'") . ']]',
						$transformed_message
					);

					// handle #XXXX. ##XXXX or some weird variants are not allowed.
					$transformed_message = preg_replace (
						"/(^|[^#])#{$ri->issueid}([^[:digit:]]|$)/", 
						"\${1}" . anchor ("/issue/show/{$ri->projectid}/{$hex_issueid}", "#{$ri->issueid}", "class='codepot-issue-type-{$ri->type}'") . "\${2}",
						$transformed_message
					);
				}

				// handle [[#RXXX]]
				$transformed_message = preg_replace (
					"/\[\[(#R([[:digit:]]+))\]\]/", 
					'[[' . anchor ("/code/revision/{$project->id}/!./\${2}", "\${1}", "class='codepot-hashed-revision-number'") . ']]',
					$transformed_message
				);
			?>
			<pre id="code_revision_metadata_text"><?php print $transformed_message; ?></pre>
		</div>
		<?php 
			if (!empty($related_issues))
			{
				print '<div><ul id="code_revision_related_issue_list" class="codepot-horizontal-list">';
				print '<li><i class="fa fa-hand-o-right"></i> </li>';
				foreach ($related_issues as $ri)
				{
					$hex_issueid = $this->converter->AsciiToHex ($ri->issueid);
					print '<li>';
					print anchor ("/issue/show/{$ri->projectid}/{$hex_issueid}", $ri->issueid . ':' . htmlspecialchars($ri->summary), "class='codepot-issue-type-{$ri->type}'");
					print '</li>';
				}
				print '</ul></div>';
			}
		?>

		<div style='clear: both'></div>
	</div>
</div>

<div id="code_revision_result" class="codepot-relative-container-view">
	<div id="code_revision_result_files" class="collapsible-box">
		<div class="collapsible-box-header"><?php print $this->lang->line('Files')?></div>
		<div id="code_revision_result_files_table_container" class="collapsible-box-panel">
			<table id="code_revision_result_files_table" class="codepot-fit-width-table">
				<?php 
				/*
				print '<tr class="heading">';
				print '<th>' .  $this->lang->line('Path') . '</th>';
				print '<th></th>';
				print '</tr>';
				*/
				$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
				$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
				$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');

				$rowclasses = array ('odd', 'even');
				$rowcount = 0;
				foreach ($history['paths'] as &$p)
				{
					$rowclass = $rowclasses[++$rowcount % 2];
					print "<tr class='{$rowclass}'>";

					$xpar = $this->converter->AsciiToHex ($p['path']);

					print "<td class='{$p['action']}' >";
					print anchor ("code/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
					print '</td>';

					print '<td>';
					print '<ul id="code_revision_action_list" class="codepot-horizontal-list">';
					print '<li class="codepot-buttoned-anchor">';
					print anchor ("code/diff/{$project->id}/{$xpar}/{$history['rev']}", $diff_anchor_text);
					print '</li>';
					print '<li class="codepot-buttoned-anchor">';
					print anchor ("code/fulldiff/{$project->id}/{$xpar}/{$history['rev']}", $fulldiff_anchor_text);
					print '</li>';
					print '<li class="codepot-buttoned-anchor">';
					print anchor ("code/blame/{$project->id}/{$xpar}/{$history['rev']}", $blame_anchor_text);
					print '</li>';
					print '</ul>';
					print '</td>';

					print '</tr>';
				}
				?>
			</table>
		</div>
	</div>

<div id="code_revision_result_properties" class="collapsible-box">
	<div class="collapsible-box-header"><?php print $this->lang->line('CODE_PROPERTIES');?></div>
	<div id="code_revision_result_properties_table_container" class="collapsible-box-panel">
		<table id="code_revision_result_properties_table" class="codepot-fit-width-table">
			<?php
			$rowclasses = array ('odd', 'even');
			$rowcount = 0;
			foreach ($history['paths'] as &$p)
			{
				if (array_key_exists('props', $p) && array_key_exists('prev_props', $p))
				{
					$common_props = array_intersect_assoc ($p['props'], $p['prev_props']);
					$added_props = array_diff_assoc ($p['props'], $common_props);
					$deleted_props = array_diff_assoc ($p['prev_props'], $common_props);

					if (count($added_props) > 0 || count($deleted_props) > 0)
					{
						$rowclass = $rowclasses[++$rowcount % 2];
						$first = TRUE;

						foreach ($added_props as $k => $v)
						{
							print "<tr class='{$rowclass}'>";
							if ($first)
							{
								print "<td class='{$p['action']}'>";
								$xpar = $this->converter->AsciiToHex ($p['path']);
								print anchor ("code/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
								$first = FALSE;
							}
							else print "<td>";
							print '</td>';

							print '<td class="A">';
							printf ('%s - %s', htmlspecialchars($k), htmlspecialchars($v));
							print '</td>';
							print '</tr>';
						}

						foreach ($deleted_props as $k => $v)
						{
							print "<tr class='{$rowclass}'>";
							if ($first)
							{
								print "<td class='{$p['action']}'>";
								$xpar = $this->converter->AsciiToHex ($p['path']);
								print anchor ("code/file/{$project->id}/{$xpar}/{$history['rev']}", htmlspecialchars($p['path']));
								$first = FALSE;
							}
							else print "<td>";
							print '</td>';

							print '<td class="D">';
							printf ('%s - %s', htmlspecialchars($k), htmlspecialchars($v));
							print '</td>';
							print '</tr>';
						}

					}
				}
			}
			?>
		</table>
	</div>
</div>

<div id="code_revision_result_comments" class="collapsible-box">
	<div class="collapsible-box-header"><?php print $this->lang->line('Comment')?>&nbsp;
	<?php if ($is_loggedin): ?>
	<span class='anchor'>
		<?php print anchor ("#", $this->lang->line('New'),
				 array ('id' => 'code_revision_new_review_comment_button'));
		?>
	</span>
	<?php endif; ?>
	</div>

	<div id="code_revision_mainarea_review_comment" class="collapsible-box-panel">
	<?php
		for ($i = 0; $i < $review_count; )
		{
			$rc = $reviews[$i];
			$i++;
			print "<div id='code_revision_mainarea_review_comment_title_{$i}' class='review_comment_title'>\n";
			printf (" <span class='review_comment_title_no'>%d</span>", $rc->sno);
			printf (" <span class='review_comment_title_updatedby'>%s</span>", $rc->updatedby);
			printf (" <span class='review_comment_title_updatedon'>%s</span>", codepot_dbdatetodispdate($rc->updatedon));
			
			if ($login['id'] == $rc->updatedby)
			{
				print '&nbsp;';
				print anchor (
					"#", $this->lang->line('Edit'), 
					array ('id' => 'code_revision_edit_review_comment_button_' . $i)
				);
			}

			print ("</div>\n");

			print "<div id='code_revision_mainarea_review_comment_{$i}' class='review_comment_text'>\n";
			print "<pre id='code_revision_mainarea_review_comment_text_{$i}' style='visibility: hidden'>\n";

			print htmlspecialchars($rc->comment);

			print "</pre>\n";
			print "</div>\n";
		}
	?>
	</div>
</div> <!-- code_revision_result_comments -->

</div> <!-- code_revision_result -->


<?php if ($is_loggedin): ?>
<div id="code_revision_edit_revision_tag_form">
<?php print 
	form_input (
		array ('name' => 'code_edit_revision_tag', 
		       'value' => $history['tag'], 
		       'id' => 'code_revision_edit_revision_tag')
	)
?>
</div>
<?php endif; ?> 

<?php if ($can_edit): ?>
<div id='code_revision_edit_revision_message_form'>
<?php print 
	form_textarea (
		array ('name' => 'code_edit_revision_message', 
		       'value' => $history['msg'], 'rows'=> 10, 'cols' => 70,
		       'id' => 'code_revision_edit_revision_message')
	)
?>
</div>
<?php endif; ?> <!-- $can_edit -->

<?php if ($is_loggedin): ?>
<div id="code_revision_new_review_comment_form">
	<div id='code_revision_new_review_comment_tabs' style='width:100%;'>
		<ul>
			<li><a href='#code_revision_new_review_comment_input'><?php print $this->lang->line('Comment'); ?></a></li>
			<li><a href='#code_revision_new_review_comment_preview'><?php print $this->lang->line('Preview'); ?></a></li>
		</ul>

		<div id='code_revision_new_review_comment_input'>
			<textarea type='textarea' id='code_revision_new_review_comment' name='code_new_review_comment' rows=24 cols=100 style='width:100%;'></textarea>
		</div>

		<div id='code_revision_new_review_comment_preview' class='codepot-styled-text-preview'>
		</div>
	</div>
</div>

<?php
$comment_label = $this->lang->line('Comment');
$preview_label = $this->lang->line('Preview');

for ($i = 0; $i < $review_count; )
{
	$rc = $reviews[$i];
	$i++;
	if ($login['id'] == $rc->updatedby)
	{
		$text = htmlspecialchars ($rc->comment);
		print "
			<div id='code_revision_edit_review_comment_form_{$i}'>
				<div id='code_revision_edit_review_comment_tabs_{$i}' class='code_revision_edit_review_comment_tabs' style='width:100%;'>
					<ul>
						<li><a href='#code_revision_edit_review_comment_input_{$i}'>{$comment_label}</a></li>
						<li><a href='#code_revision_edit_review_comment_preview_{$i}'>{$preview_label}</a></li>
					</ul>

					<div id='code_revision_edit_review_comment_input_{$i}'>
						<textarea type='textarea' id='code_revision_edit_review_comment_{$i}' name='code_edit_review_comment_{$i}' rows=24 cols=100 style='width:100%;'>{$text}</textarea>
					</div>

					<div id='code_revision_edit_review_comment_preview_{$i}' class='codepot-styled-text-preview'>
					</div>
				</div>
			</div>
		";
	}
}
?>
<?php endif; ?> <!-- $is_loggedin -->

<div id='code_revision_mainarea_alert'></div>

</div> <!-- code_revision_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- code_revision_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</body>

</html>

