<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />

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
						$('#filter_status').val ($('#jq_status').val());
						$(this).dialog('close'); 
					}
				},
				close: function() {}
			} 
		); 


		$("#project_issue_home_mainarea_filter_open").button().click (
			function () { 
				$('#jq_owner').val ($('#filter_owner').val());
				$('#jq_status').val ($('#filter_status').val());
				$('#project_issue_home_mainarea_options').dialog('open'); 
			}
		);
	}
);
</script>

<title><?=htmlspecialchars($project->id)?></title>
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

		<?=form_label ($this->lang->line('Status'), 'form_status')?>
		<?=form_input('filter_status', set_value('filter_status', $filter->status), 'id="filter_status"')?>


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
	print '<ul>';
	foreach ($issues as $issue) 
	{
		$hexid = $this->converter->AsciiToHex ($issue->id);
		print '<li>';
		print anchor ("issue/show/{$project->id}/{$hexid}", htmlspecialchars($issue->id));
		print ': ';
		print htmlspecialchars($issue->summary);

		print htmlspecialchars($issue->owner);
		print htmlspecialchars($issue->createdby);

		print '</li>';
	}
	print '</ul>';
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
