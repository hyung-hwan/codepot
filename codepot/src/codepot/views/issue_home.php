<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/issue.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">
/* <![CDATA[ */
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
	$("#issue_home_mainarea_search_form").dialog ({
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


	$("#issue_home_mainarea_search_button").button().click (
		function () { 
			$('#issue_home_mainarea_search_form').dialog('open'); 
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
			array ("issue/create/{$project->id}", '<i class="fa fa-plus"></i> ' . $this->lang->line('New'), 'issue_home_new')
		)
	)
); 
?>

<!-- ============================================================ -->

<div class="mainarea" id="issue_home_mainarea">
<div class="title"><?php print $this->lang->line('Issues')?></div>

<div class="infostrip">
<?php printf ($this->lang->line('ISSUE_MSG_TOTAL_NUM_ISSUES'), $total_num_issues); ?> | 
<a id="issue_home_mainarea_search_button" href='#'><?php print $this->lang->line('Search')?></a>
</div>

<div id="issue_home_mainarea_search_form">
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


<div class="result" id="issue_home_mainarea_result">
<?php
if (empty($issues))
{
	print $this->lang->line('ISSUE_MSG_NO_ISSUES_AVAILABLE');
}
else
{
	print '<table id="issue_home_mainarea_result_table" class="full-width-result-table">';
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

	print '<div id="issue_home_mainarea_result_pages">';
	print $page_links;
	print '</div>';
}
?>
</div> <!-- issue_home_mainarea_result -->

</div> <!-- issue_home_mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- issue_home_content -->

<!-- ============================================================ -->

<?php $this->load->view ('footer'); ?>

<!-- ============================================================ -->



</body>
</html>
