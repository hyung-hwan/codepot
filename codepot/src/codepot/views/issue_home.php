<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/issue.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
$(
	function () { 
		$("#project_issue_home_mainarea_options").dialog (
			{
				title: 'Options',
				autoOpen: false,
				modal: true,
				buttons: { 
					'Ok': function () { 
						$('#filter_owner').val ($('#jq_owner').val());
						$('#filter_summary').val ($('#jq_status').val());
						$(this).dialog('close'); 
					}
				},
				close: function() {}
			} 
		); 


		$("#project_issue_home_mainarea_filter_open").button().click (
			function () { 
				$('#jq_owner').val ($('#filter_owner').val());
				$('#jq_status').val ($('#filter_summary').val());
				$('#project_issue_home_mainarea_options').dialog('open'); 
			}
		);
	}
);
</script>

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_issue_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar', 
	array (
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array (
			array ("issue/create/{$project->id}", $this->lang->line('New')) 
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_issue_home_mainarea">
<div class="title"><?=$this->lang->line('Issues')?></div>

<div id="project_issue_home_mainarea_filter">
	<?=form_open("issue/home/{$project->id}/")?>

		<input type="hidden" id="filter_owner" name="filter_owner" value='<?=$filter->owner?>' />

		<?=form_label ($this->lang->line('Summary'), 'form_summary')?>
		<?=form_input('filter_summary', set_value('filter_summary', $filter->summary), 'id="filter_summary"')?>


		<?=form_submit('filter', 'Search')?>
		<a id="project_issue_home_mainarea_filter_open" href='#'>Options</a>

	<?=form_close()?>
</div>

<div id="project_issue_home_mainarea_options">
	<form>
		<label for="jq_owner"><?=$this->lang->line('Owner')?></label>
		<input type="text" id="jq_owner" name="jq_owner" />
		<label for="jq_status"><?=$this->lang->line('Status')?></label>
		<input type="text" id="jq_status" name="jq_status" />
	</form>
</div>


<div id="project_issue_home_mainarea_result">
<?php
if (empty($issues))
{
	print $this->lang->line('MSG_NO_ISSUES_AVAIL');
}
else
{
	print '<table id="project_issue_home_mainarea_result_table">';
	print '<tr class="heading">';
	print '<th class="project_issue_home_mainarea_result_table_id">' . $this->lang->line('ID') . '</th>';
	print '<th class="project_issue_home_mainarea_result_table_type">' . $this->lang->line('Type') . '</th>';
	print '<th class="project_issue_home_mainarea_result_table_status">' . $this->lang->line('Status') . '</th>';
	print '<th class="project_issue_home_mainarea_result_table_priority">' . $this->lang->line('Priority') . '</th>';
	print '<th class="project_issue_home_mainarea_result_table_owner">' . $this->lang->line('Owner') . '</th>';
	print '<th class="project_issue_home_mainarea_result_table_summary">' . $this->lang->line('Summary') . '</th>';
	print '</tr>';

	$rowclasses = array ('odd', 'even'); $rowno = 1;
	foreach ($issues as $issue)
	{
		$hexid = $this->converter->AsciiToHex ($issue->id);

		$rowclass = $rowclasses[++$rowno % 2];
		print "<tr class='{$rowclass}'>";

		print '<td class="project_issue_home_mainarea_result_table_id">';
		print anchor ("issue/show/{$project->id}/{$hexid}", htmlspecialchars($issue->id));
		print '</td>';

		print '<td class="project_issue_home_mainarea_result_table_type">';
		print (htmlspecialchars(
			array_key_exists($issue->type, $issue_type_array)?
				$issue_type_array[$issue->type]: $issue->type));
		print '</td>';

		print '<td class="project_issue_home_mainarea_result_table_status">';
		print (htmlspecialchars(
			array_key_exists($issue->status, $issue_status_array)?
				$issue_status_array[$issue->status]: $issue->status));
		print '</td>';

		print '<td class="project_issue_home_mainarea_result_table_priority">';
		print (htmlspecialchars(
			array_key_exists($issue->priority, $issue_priority_array)?
				$issue_priority_array[$issue->priority]: $issue->priority));
		print '</td>';

		print '<td class="project_issue_home_mainarea_result_table_owner">';
		print htmlspecialchars($issue->owner);
		print '</td>';

		print '<td class="project_issue_home_mainarea_result_table_summary">';
		print htmlspecialchars($issue->summary);
		print '</td>';

		print '</tr>';
	}

	print '<tr class="foot">';
	print "<td colspan='6' class='pages'>{$page_links}</td>";
	print '</tr>';

	print '</table>';
}
?>
</div> <!-- project_issue_home_mainarea_result -->

</div> <!-- project_issue_home_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_issue_home_content -->

</body>
</html>
