<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<script type="text/javascript" src="<?=base_url()?>/js/creole.js"></script>
<title><?=htmlspecialchars($issue->id)?></title>
</head>

<script type="text/javascript">
function render_wiki()
{
	creole_render_wiki (
		"project_issue_show_textpre", 
		"project_issue_show_textarea", 
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
$hexname = $this->converter->AsciiToHex ($issue->id);
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'pageid' => 'issue',
		'ctxmenuitems' => array (
			array ("issue/create/{$project->id}", $this->lang->line('New')),
			array ("issue/update/{$project->id}/{$hexname}", $this->lang->line('Edit')),
			array ("issue/delete/{$project->id}/{$hexname}", $this->lang->line('Delete'))
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
</div>

<div id="project_issue_show_textarea">
<pre id="project_issue_show_textpre" style="visibility: hidden">
<?php print htmlspecialchars($issue->description); ?>
</pre>
</div> <!-- project_issue_show_textarea -->

</div> <!-- project_issue_show_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_issue_show_content -->

</body>

</html>

