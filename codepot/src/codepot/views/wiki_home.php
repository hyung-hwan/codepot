<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_wiki_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar', 
	array (
		'site' => NULL,
		'pageid' => 'wiki',
		'ctxmenuitems' => array (
			array ("wiki/create/{$project->id}", $this->lang->line('New')) 
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_wiki_home_mainarea">
<div class="title"><?=$this->lang->line('Wikis')?></div>

<div id="project_wiki_home_textarea">
<?php
if (empty($wikis))
{
	print $this->lang->line('MSG_NO_WIKIS_AVAIL');
}
else
{
	print '<ul>';
	foreach ($wikis as $wiki) 
	{
		$hexname = $this->converter->AsciiToHex ($wiki->name);
		print '<li>' . anchor ("wiki/show/{$project->id}/{$hexname}", htmlspecialchars($wiki->name)) .'</li>';
	}
	print '</ul>';
}
?>
</div>
</div> <!-- project_wiki_home_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_wiki_home_content -->

</body>
</html>
