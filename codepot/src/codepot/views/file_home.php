<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_file_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'pageid' => 'file',
		'ctxmenuitems' => array (
			array ("file/create/{$project->id}", $this->lang->line('New')) 
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_file_home_mainarea">
<div class="title"><?=$this->lang->line('Files')?></div>

<div id="project_file_home_textarea">
<?php
if (empty($files))
{
	print htmlspecialchars($this->lang->line('MSG_NO_FILES_AVAIL'));
}
else
{
	function comp_files ($a, $b)
	{
		if ($a->tag == $b->tag)
		{
			return ($a->name > $b->name)? -1:
			       ($a->name < $b->name)? 1: 0;
		}

		return ($a->tag < $b->tag)? 1: -1;
	}

        usort ($files, 'comp_files');

	print '<table>';
	print '<tr>';
	print '<th>' . $this->lang->line('Tag') . '</th>';
	print '<th>' . $this->lang->line('Name') . '</th>';
	print '<th>' . $this->lang->line('Summary') . '</th>';
	print '<th>' . $this->lang->line('MD5') . '</th>';
	print '<th>' . $this->lang->line('Download') . '</th>';
	print '</tr>';
	
	$oldtag = '';
	foreach ($files as $file) 
	{
		$hexname = $this->converter->AsciiToHex ($file->name);
		print '<tr>';
		print '<td>';
		if ($file->tag != $oldtag)
		{
			print htmlspecialchars($file->tag);
			$oldtag = $file->tag;
		}
		print '</td>';
		print '<td>';
		print anchor ("file/show/{$project->id}/{$hexname}", htmlspecialchars($file->name));
		print '</td>';
		print '<td>';
		print htmlspecialchars($file->summary);
		print '</td>';
		print '<td>';
		print $file->md5sum;
		print '</td>';
		print '<td>';
		print anchor ("file/get/{$project->id}/{$hexname}", $this->lang->line('Download'));
		print '</td>';
		print '</tr>';
	}
	print '</table>';
}
?>
</div>
</div> <!-- project_file_home_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!-- project_file_home_content -->

</body>
</html>
