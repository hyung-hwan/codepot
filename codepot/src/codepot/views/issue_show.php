<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/issue.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">

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


$(function () { 
	/*
	$("#issue_change_type").combobox();
	$("#issue_change_status").combobox();
	$("#issue_change_priority").combobox();
	*/
	/*$("#issue_change_owner").combobox();*/

	$("#project_issue_show_mainarea_change_form").dialog (
		{
			title: '<?=$this->lang->line('Change')?>',
			autoOpen: false,
			modal: true,
			width: '85%',
			buttons: { 
				'<?=$this->lang->line('Cancel')?>': function () { 
					$(this).dialog('close'); 
				},
				'<?=$this->lang->line('OK')?>': function () { 
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
						$('#project_issue_change_form').submit ();
					}
				}
			},
			close: function() { }
		} 
	); 

	$("#project_issue_show_mainarea_change_form_open").button().click (
		function () { 
			$('#project_issue_show_mainarea_change_form').dialog('open'); 
		}
	);

	$("#project_issue_show_mainarea_undo_change_confirm").dialog (
		{
			title: '<?=$this->lang->line('Undo')?>',
			resizable: false,
			autoOpen: false,
			modal: true,
			buttons: { 
				'<?=$this->lang->line('Cancel')?>': function () { 
					$(this).dialog('close'); 
				},
				'<?=$this->lang->line('OK')?>': function () { 
					$('#issue_change').val ('undo');
					$('#project_issue_change_form').submit ();
					$(this).dialog('close'); 
				}
			},
			close: function() { }
		} 
	);

	$("#project_issue_show_mainarea_undo_change").button().click (
		function () { 
			$('#project_issue_show_mainarea_undo_change_confirm').dialog('open'); 
		}
	);

});
</script>

<title><?=htmlspecialchars($issue->id)?></title>
</head>

<body onLoad="render_wiki()">

<div class="content" id="project_issue_show_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$hexid = $this->converter->AsciiToHex ($issue->id);
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array (
			array ("issue/create/{$project->id}", $this->lang->line('New')),
			array ("issue/update/{$project->id}/{$hexid}", $this->lang->line('Edit')),
			array ("issue/delete/{$project->id}/{$hexid}", $this->lang->line('Delete'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="mainarea" id="project_issue_show_mainarea">
<div class="title">
	<?=$this->lang->line('Issue')?> <?=htmlspecialchars($issue->id)?>: 
	<?=htmlspecialchars($issue->summary)?>
</div>

<div class="infostrip" id="project_issue_show_mainarea_infostrip">
	<?php
		print $this->lang->line('Type');
		print ': '; 
		print htmlspecialchars(
			array_key_exists($issue->type, $issue_type_array)? 
				$issue_type_array[$issue->type]: $issue->type
		);
		print ' | ';

		print $this->lang->line('Status');
		print ': '; 
		print htmlspecialchars(
			array_key_exists($issue->status, $issue_status_array)? 
				$issue_status_array[$issue->status]: $issue->status
		);
		print ' | ';

		print $this->lang->line('Priority');
		print ': '; 
		print htmlspecialchars(
			array_key_exists($issue->priority, $issue_priority_array)? 
				$issue_priority_array[$issue->priority]: $issue->priority
		);
		print ' | ';
		if ($issue->owner != '')
		{
			print $this->lang->line('Owner');
			print ': '; 
			print htmlspecialchars($issue->owner);
			print ' | ';
		}
	?>
	<a id="project_issue_show_mainarea_change_form_open" href="#"><?=$this->lang->line('Change')?></a>
</div>

<div id="project_issue_show_mainarea_description">
<pre id="project_issue_show_mainarea_description_pre" style="visibility: hidden">
<?php print htmlspecialchars($issue->description); ?>
</pre>
</div> <!-- project_issue_mainarea_description -->

<div id="project_issue_show_mainarea_changes">
<?php
	$commentno = 0;

	$msgfmt_changed = $this->lang->line ('ISSUE_MSG_CHANGED_X_FROM_Y_TO_Z');
	$count = count($issue->changes);
	if ($count > 1) 
	{
		print '<div class="infostrip">';
		print '<span class="title">';
		print $this->lang->line('Change log');
		print '</span>';
		print '<a id="project_issue_show_mainarea_undo_change" href="#">';
		print $this->lang->line('Undo');
		print '</a>';
		print '</div>';
	}

	print '<table id="project_issue_show_mainarea_changes_table">';
	while ($count > 1)
	{
		$new = $issue->changes[--$count];
		$old = $issue->changes[$count-1];

		print "<tr>";
		
		print '<td class="project_issue_show_mainarea_changes_table_date">'; 
		print '<span title="';
		print date ('Y-m-d H:s:i', strtotime($new->updatedon));
		print '">';
		print date ('Y-m-d', strtotime($new->updatedon));
		print '</span>';
		print '</td>';

		print '<td class="project_issue_show_mainarea_changes_table_updater">'; 
		print htmlspecialchars($new->updatedby);
		print '</td>';

		print '<td class="project_issue_show_mainarea_changes_table_details">';
		if ($new->comment != "")
		{
			print "<div id='project_issue_show_mainarea_changes_comment_{$commentno}' class='project_issue_show_mainarea_changes_comment'>";
			print "<pre id='project_issue_show_mainarea_changes_comment_pre_{$commentno}'>";
			print htmlspecialchars($new->comment);
			print '</pre>';	
			print '</div>';
			$commentno++;
		}

		print '<div class="project_issue_show_mainarea_changes_list">';
		print '<ul>';
		if ($new->type != $old->type)
		{
			printf ("<li>{$msgfmt_changed}</li>", 
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
			printf ("<li>{$msgfmt_changed}</li>", 
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
			printf ("<li>{$msgfmt_changed}</li>", 
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
			printf ("<li>{$msgfmt_changed}</li>", 
				strtolower($this->lang->line('Owner')),
				htmlspecialchars($old->owner), htmlspecialchars($new->owner));
		}
		print '</ul>';
		print '</div>';

		print '</td>';
		print '</tr>';
	}
	print '</table>';
?>

</div>

<div id="project_issue_show_mainarea_change_form">

	<?=form_open("issue/show/{$project->id}/{$hexid}/", 'id="project_issue_change_form"')?>

		<input type='hidden' name='issue_change' id='issue_change' value='change' />

		<div>
			<?=form_label ($this->lang->line('Type'),
				'issue_change_type')
			?>
			<?=form_dropdown('issue_change_type', 
				$issue_type_array,
				set_value('issue_change_type', $issue->type),
				'id="issue_change_type"')
			?>

			<?=form_label ($this->lang->line('Status'),
				'issue_change_status')
			?>
			<?=form_dropdown('issue_change_status', 
				$issue_status_array,
				set_value('issue_change_status', $issue->status),
				'id="issue_change_status"')
			?>

			<?=form_label ($this->lang->line('Priority'),
				'issue_change_priority')
			?>

			<?=form_dropdown (
				'issue_change_priority', 
				$issue_priority_array,
				set_value('issue_change_priority', $issue->priority),
				'id="issue_change_priority"')
			?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Owner'),
				'issue_change_owner')
			?>
			<?=form_input('issue_change_owner',
				set_value('issue_change_owner', $issue->owner),
				'id="issue_change_owner"')
			?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Comment'), 'issue_change_comment')?>
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
	<?=form_close()?>
</div> <!-- project_issue_show_change_form -->


<div id="project_issue_show_mainarea_undo_change_confirm">
	<?=$this->lang->line ('ISSUE_MSG_CONFIRM_UNDO')?>
</div>

</div> <!-- project_issue_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_issue_show_content -->

<script type="text/javascript">
function render_wiki()
{
	<?php $creole_base = site_url() . "/wiki/show/{$project->id}/"; ?>

	creole_render_wiki (
		"project_issue_show_mainarea_description_pre", 
		"project_issue_show_mainarea_description", 
		"<?=$creole_base?>"
	);

	<?php
	if ($commentno > 0)
	{
		for ($xxx = 0; $xxx < $commentno; $xxx++)
		{
			print "creole_render_wiki (
					'project_issue_show_mainarea_changes_comment_pre_{$xxx}', 
					'project_issue_show_mainarea_changes_comment_{$xxx}', 
					'{$creole_base}');";
		}
	}
	?>
}
</script>

</body>

</html>
