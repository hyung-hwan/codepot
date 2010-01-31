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

<?php if ($revision >= 0): ?>
	<div class="sidebar" id="project_source_folder_sidebar">
	<div class="box">
	<ul>
	<li><?=$this->lang->line('Revision')?>: <?=$revision?></li>
	</ul>
	</div>
	</div> <!-- project_source_folder_sidebar -->
<?php endif; ?>


<!---------------------------------------------------------------------------->


<div class="mainarea" id="project_source_folder_mainarea">

<div class="title">
<?php
if ($revision <= 0)
{
	$revreq = '';
	$revreqroot = '';
}
else
{
	$revreq = "/{$revision}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;
}

print anchor ("/source/folder/{$project->id}{$revreqroot}", htmlspecialchars($project->name));
if ($folder != '') 
{
	$exps = explode ('/', $folder);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';

		$par .= '/' . $exps[$i];
		$hexpar = $this->converter->AsciiToHex ($par);
		print anchor ("source/folder/{$project->id}/{$hexpar}{$revreq}", htmlspecialchars($exps[$i]));
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
		$hexpar = $this->converter->AsciiTohex ($folder);
		if ($revision > 0 && $revision < $next_revision)
		{
			print anchor ("source/folder/{$project->id}", $this->lang->line('Head revision'));
			print ' | ';
		}
		print anchor ("source/history/folder/{$project->id}/{$hexpar}", $this->lang->line('History'));
		print '</div>';

		usort ($files, 'comp_files');

		print '<div id="project_source_folder_mainarea_result">';
		print '<table id="project_source_folder_mainarea_result_table">';
		print '<tr class="heading">';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Author') . '</th>';
		print '<th>' . $this->lang->line('Time') . '</th>';
		print '<th>' . '</th>';
		print '<th>' . '</th>';
		print '</tr>';

		$rowclasses = array ('even', 'odd');
		$rownum = 0;
		foreach ($files as $f)
		{
			$fullpath = $folder . '/' . $f['name'];

			$rowclass = $rowclasses[++$rownum % 2];
			if ($f['type'] === 'dir')
			{
				// directory 
				$hexpath = $this->converter->AsciiToHex($fullpath);
       		         	print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"source/folder/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td></td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><code>';
				print date('r', $f['time_t']);
				print '</code></td>';
				print '<td></td>';
				print '<td></td>';
				print '</tr>';
			}
			else
			{
				// file
				$hexpath = $this->converter->AsciiToHex($fullpath);
       		         	print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"source/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
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
				print '<td><code>';
				print date('r', $f['time_t']);
				print '</code></td>';

				print '<td>';
				print anchor (
					"source/blame/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Blame'));
				print '</td>';
				print '<td>';
				print anchor (
					"source/diff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Difference'));
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';
		print '</div>';
	}
?>

</div> <!-- project_source_folder_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  project_source_folder_content -->

</body>

</html>

