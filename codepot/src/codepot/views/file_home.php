<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/file.css')?>" />

<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="file_home_content">

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
			'id' => 'file',
			'project' => $project,
		),

		'ctxmenuitems' => array (
			array ("file/create/{$project->id}", $this->lang->line('New')) 
		)
	)
);
?>

<!-- ============================================================ -->

<div class="mainarea" id="file_home_mainarea">
<div class="title"><?=$this->lang->line('Files')?></div>

<div id="file_home_mainarea_result">
<?php
if (empty($files))
{
	print htmlspecialchars($this->lang->line('FILE_MSG_NO_FILES_AVAILABLE'));
}
else
{
	function comp_files ($a, $b)
	{
		//$cmp = strcmp ($b->tag, $a->tag);
		$cmp = version_compare ($b->tag, $a->tag);
		if ($cmp == 0)
		{
			return strcmp ($a->name, $b->name);
		}
		return $cmp;
	}

        usort ($files, 'comp_files');

	print '<table id="file_home_mainarea_result_table">';
	print '<tr class="heading">';
	print '<th>' . $this->lang->line('Tag') . '</th>';
	print '<th>' . $this->lang->line('Name') . '</th>';
	print '<th>' . $this->lang->line('Summary') . '</th>';
	print '<th>' . $this->lang->line('MD5') . '</th>';
	print '<th>' . $this->lang->line('Download') . '</th>';
	print '</tr>';
	
	$oldtag = '';
	$rownum = 0;
	$rowclasses = array ('odd', 'even');
	foreach ($files as $file) 
	{
		$hexname = $this->converter->AsciiToHex ($file->name);
		$rowclass = $rowclasses[$rownum++ % 2];
		print "<tr class='{$rowclass}'>";
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
		print '<td><tt>';
		print $file->md5sum;
		print '</tt></td>';
		print '<td>';
		print anchor ("file/get/{$project->id}/{$hexname}", $this->lang->line('Download'));
		print '</td>';
		print '</tr>';
	}
	print '</table>';
}
?>
</div>
</div> <!-- file_home_mainarea -->

<!-- ============================================================ -->

<?php $this->load->view ('footer'); ?>

<!-- ============================================================ -->

</div> <!-- file_home_content -->

</body>
</html>
