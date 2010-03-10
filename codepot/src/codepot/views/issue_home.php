<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
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

<div id="project_issue_home_textarea">
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
		print '</li>';
	}
	print '</ul>';
}
?>
</div>
</div> <!-- project_issue_home_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_issue_home_content -->

</body>
</html>
