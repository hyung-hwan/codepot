<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
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

<div class="title"><?=$this->lang->line('My projects')?></div>
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

<div class="title"><?=$this->lang->line('Other projects')?></div>
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

</div> <!-- project_list_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_list_content -->


</body>
</html>
