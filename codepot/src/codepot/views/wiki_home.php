<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/wiki.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="wiki_home_content">

<!-- =================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- =================================================================== -->

<?php
$this->load->view (
	'projectbar', 
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'wiki',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("wiki/create/{$project->id}", $this->lang->line('New')) 
		)
	)
); 
?>

<!-- =================================================================== -->

<div class="mainarea" id="wiki_home_mainarea">
<div class="title"><?php print $this->lang->line('Wikis')?></div>

<div id="wiki_home_mainarea_result" class="result">
<?php
if (empty($wikis))
{
	print $this->lang->line('WIKI_MSG_NO_PAGES_AVAILABLE');
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
</div> <!-- wiki_home_mainarea -->

<!-- =================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- =================================================================== -->


</div> <!-- wiki_home_content -->

</body>
</html>
