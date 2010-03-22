<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
$(function () {
	$('#project_list_mainarea_result').tabs();
});
</script>

<?php
	$caption = $this->lang->line('Projects');
	if ($login['id'] != '') $caption .= "({$login['id']})";
?>
<title><?=htmlspecialchars($caption)?></title>
</head>

<body>

<div class="content" id="project_list_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
        'projectbar',
        array (
		'banner' => NULL,
		'site' => NULL,
		'project' => NULL,
		'pageid' => '',
                'ctxmenuitems' => array (
			array ('project/create', $this->lang->line('New')) 
		)
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_list_mainarea">

<?php
$num_projects = count($projects);
$num_other_projects = count($other_projects);
?>

<div id="project_list_mainarea_result">
<ul>
<?php if ($login['id'] != '' || $num_projects > 0): ?>
<li>
	<a href='#project_list_mainarea_result_my_projects'>
		<?=$this->lang->line('My projects')?> (<?=$num_projects?>)
	</a>
</li>
<?php endif; ?>
<li>
	<a href='#project_list_mainarea_result_other_projects'>
		<?=$this->lang->line('Other projects')?> (<?=$num_other_projects?>)
	</a>
</li>
</ul>

<?php if ($login['id'] != '' || $num_projects > 0): ?>
<div id="project_list_mainarea_result_my_projects">
<ul>
<?php 
foreach ($projects as $project) 
{
	$cap = "{$project->name} ({$project->id})";
	$anc = anchor ("project/home/{$project->id}", htmlspecialchars($cap));
	$sum = htmlspecialchars ($project->summary);
	print "<li>{$anc} - {$sum}</li>";
}
?>
</ul>
</div>
<?php endif; ?>

<div id="project_list_mainarea_result_other_projects">
<ul>
<?php 
foreach ($other_projects as $project) 
{
	$anc = anchor ("project/home/{$project->id}", htmlspecialchars($project->name));
	$sum = htmlspecialchars ($project->summary);
	print "<li>{$anc} - {$sum}</li>";
}
?>
</ul>
</div>

</div> <!-- project_list_mainarea_result -->

</div> <!-- project_list_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_list_content -->


</body>
</html>
