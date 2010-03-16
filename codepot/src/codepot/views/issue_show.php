<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>


<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
$(
	function () { 
		$("#project_issue_show_mainarea_change_form").dialog (
			{
				title: 'Change',
				autoOpen: false,
				modal: true,
				width: '85%',
				buttons: { 
					'Submit': function () { 
						$(this).dialog('close'); 
						$('#issue').submit ();
					}
				},
				close: function() {}
			} 
		); 


		$("#project_issue_show_mainarea_change_form_open").button().click (
			function () { 
				$('#project_issue_show_mainarea_change_form').dialog('open'); 
			}
		);
	}
);
</script>

<title><?=htmlspecialchars($issue->id)?></title>
</head>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"project_issue_show_mainarea_description_pre", 
		"project_issue_show_mainarea_description", 
		"<?=site_url()?>/issue/show/<?=$project->id?>/"
	);
}
</script>

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
        Reported by <?=htmlspecialchars($issue->createdby)?> on <?=$issue->createdon?> |
	<?=$this->lang->line('Status') ?>: <?=htmlspecialchars($issue->status)?> |
	<?=$this->lang->line('Type') ?>: <?=htmlspecialchars($issue->type)?> 
	<a id="project_issue_show_mainarea_change_form_open" href='#'>Change</a>
</div>

<div id="project_issue_show_mainarea_description">
<pre id="project_issue_show_mainarea_description_pre" style="visibility: hidden">
<?php print htmlspecialchars($issue->description); ?>
</pre>
</div> <!-- project_issue_mainarea_description -->

<div id="project_issue_show_mainarea_changes">
<?php
print "<ul>";
foreach ($issue->changes as $change)
{
		
	print '<li>';
	print date ('Y-m-d', strtotime($change->updatedon))  . ' ' . htmlspecialchars($change->status);
	print ' ';
	print htmlspecialchars($change->comment);
	print '</li>';
}
print "</ul>";
?>
</div>

<div id="project_issue_show_mainarea_change_form">

	<?=form_open("issue/show/{$project->id}/{$hexid}/", 'id="issue"')?>

		<?=form_hidden ('issue_change', 'yes');?>

		<div>
			<?=form_label ($this->lang->line('Type'), 'issue_change_type')?>
			<?=form_input('issue_change_type', set_value('issue_change_type', $issue->type), 'id="issue_change_type" class="text ui-widget-content ui-corner-all"')?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Status'), 'issue_change_status')?>
			<?=form_input('issue_change_status', set_value('issue_change_status', $issue->status), 'id="issue_change_status" class="text ui-widget-content ui-corner-all"')?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Owner'), 'issue_change_owner')?>
			<?=form_input('issue_change_owner', set_value('issue_change_owner', $issue->owner), 'id="issue_change_owner" class="text ui-widget-content ui-corner-all"')?>
		</div>

		<div>
			<?=form_label ($this->lang->line('Priority'), 'issue_change_priority')?>
			<?=form_input('issue_change_priority', set_value('issue_change_priority', $issue->priority), 'id="issue_change_priority" class="text ui-widget-content ui-corner-all"')?>
		</div>

		<div>
			<?=form_label ('Comment', 'issue_change_comment')?>
			<?php
				$xdata = array (
					'name' => 'issue_change_comment',
					'value' => set_value ('issue_change_comment', ''),
					'id' => 'issue_change_comment',
					'rows' => 3,
					'cols' => 80,
					'class' => 'text ui-widget-content ui-corner-all'
				);
				print form_textarea ($xdata);
			?>
		</div>
	<?=form_close()?>


</div> <!-- project_issue_show_change_form -->


</div> <!-- project_issue_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_issue_show_content -->

</body>

</html>

