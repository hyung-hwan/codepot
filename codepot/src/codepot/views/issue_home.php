<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/issue.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
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

$(function () { 
	$("#project_issue_home_mainarea_search_form").dialog ({
		title: '<?=$this->lang->line('Search')?>',
		autoOpen: false,
		modal: true,
		width: '80%',
		buttons: { 
			'<?=$this->lang->line('Cancel')?>': function () { 
				$(this).dialog('close'); 
			},
			'<?=$this->lang->line('OK')?>': function () { 
				$(this).dialog('close'); 
				var filter = AsciiToHex($('#issue_search_form').serialize());
				var url='<?=site_url()?>/issue/home/<?=$project->id?>/' + filter;	

				$('body').append('<form id="magic_form" method="get" action="'+url+'"></form>');
				$('#magic_form').submit();
			}
		},
		close: function() {}
	}); 


	$("#project_issue_home_mainarea_search_button").button().click (
		function () { 
			$('#project_issue_home_mainarea_search_form').dialog('open'); 
		}
	);
});
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
		'banner' => NULL,
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array (
			array ("issue/create/{$project->id}", $this->lang->line('New'), 'project_issue_home_new')
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_issue_home_mainarea">
<div class="title"><?=$this->lang->line('Issues')?></div>

<div class="infostrip">
<?php printf ($this->lang->line('ISSUE_MSG_TOTAL_NUM_ISSUES'), $total_num_issues); ?> | 
<a id="project_issue_home_mainarea_search_button" href='#'><?=$this->lang->line('Search')?></a>
</div>

<div id="project_issue_home_mainarea_search_form">
	<?php
		$issue_type_array[''] = $this->lang->line('All');
		$issue_status_array[''] = $this->lang->line('All');
		$issue_priority_array[''] = $this->lang->line('All');
	?>
	<form id="issue_search_form">
		<div>
			<?=form_label ($this->lang->line('Type'), 'type')
			?>
			<?=form_dropdown('type',
				$issue_type_array,
				set_value('type', $search->type), 
				'id="issue_search_type"')
			?>
	
			<?=form_label ($this->lang->line('Status'), 'status')
			?>
			<?=form_dropdown('status',
				$issue_status_array,
				set_value('status', $search->status), 'id="status"')
			?>

			<?=form_label ($this->lang->line('Priority'), 'priority')
			?>
			<?=form_dropdown('priority',
				$issue_priority_array,
				set_value('priority', $search->priority),
				'id="issue_search_priority"')
			?>
		</div>


		<div>
			<?=form_label ($this->lang->line('Owner'), 'owner')
			?>
			<?=form_input('owner',
				set_value('owner', $search->owner),
				'id="issue_search_owner"')
			?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Summary'), 'summary')
			?>
			<?=form_input('summary',
				set_value('summary', $search->summary),
				'id="issue_search_summary" size="50"')
			?>
		</div>

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
