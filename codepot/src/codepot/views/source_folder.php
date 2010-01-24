<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="project_source_folder_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'pageid' => 'source',
		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="sidebar" id="project_source_folder_sidebar">
<div class="box">
<div class="boxtitle"><?=$this->lang->line('Folder')?></div>
<ul>
<li><?=$this->lang->line('Revision')?>: <?=$revision?></li>
</ul>
</div>
</div> <!-- project_source_folder_sidebar -->


<!---------------------------------------------------------------------------->


<div class="mainarea" id="project_source_folder_mainarea">

<div class="title">
<?php
print anchor ('/source/folder/' . $project->id, htmlspecialchars($project->name));
if ($folder != '') 
{
	$exps = explode ('/', $folder);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		$par .= '/' . $exps[$i];
		$hexpar = $this->converter->AsciiToHex ($par);
		print '/';
		print anchor ('source/folder/' . $project->id . '/' . $hexpar, htmlspecialchars($exps[$i]));
	}
}
?>
</div>

<?php
	function comp_files ($a, $b)	
	{
		if ($a['type'] == $b['type'])
		{
			return ($a['name'] > $b['name'])? -1:
			       ($a['name'] < $b['name'])? 1: 0;
		}	

		return ($a['type'] == 'dir')? -1: 1;
	}

	if (count($files) <= 0)
	{
		 print $this->lang->line('MSG_NO_SOURCE_AVAIL');
	}
	else 
	{
		print '<div class="menu" id="project_source_folder_mainarea_menu">';
		$par = $this->converter->AsciiTohex ($folder);
		print anchor ('source/history/folder/' . $project->id . '/' . $par, $this->lang->line('History'));
		print '</div>';

		usort ($files, 'comp_files');

		print '<table>';
		print '<tr>';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Author') . '</th>';
		print '<th>' . $this->lang->line('Time') . '</th>';
		print '</tr>';
		foreach ($files as $f)
		{
			$fullpath = $folder . '/' . $f['name'];

			if ($f['type'] === 'dir')
			{
				// directory 
				print '<tr>';
				print '<td>';
				$url = 'source/folder/' . $project->id . '/' . 
					$this->converter->AsciiToHex ($fullpath);
				print anchor ($url, htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td></td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td>';
				print date('r', $f['time_t']);
				print '</td>';
				print '</tr>';
			}
			else
			{
				// file
       		         	print '<tr>';
				print '<td>';
				$url = 'source/file/' . $project->id . '/' . 
					$this->converter->AsciiToHex($fullpath);
				print anchor ($url, htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td>';
				print $f['size'];
				print '</td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td>';
				print date('r', $f['time_t']);
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';
	}
?>

</div> <!-- project_source_folder_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_folder_content -->

</body>

</html>

