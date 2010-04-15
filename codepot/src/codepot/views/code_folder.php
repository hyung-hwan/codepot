<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/code.css" />
<title><?=htmlspecialchars($project->name)?></title>
</head>

<body>

<div class="content" id="code_folder_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->


<div class="sidebar" id="code_folder_sidebar">
	<div class="box" id="code_folder_sidebar_info">
		<div class="boxtitle"><?=$this->lang->line('Revision')?>: <?=$file['created_rev']?></div>
		<pre><?=$file['logmsg']?></pre>
		<?php
			if (array_key_exists('properties', $file) &&
			    count($file['properties']))
			{
				print '<div class="boxtitle">';
				print $this->lang->line('CODE_PROPERTIES');
				print '</div>';

				print '<ul>';
				foreach ($file['properties'] as $pk => $pv)
				{
					print '<li>';
					print htmlspecialchars ($pk);
					if ($pv != '')
					{
						print ' - ';
						print htmlspecialchars ($pv);
					}
					print '</li>';
				}	
				print '</ul>';
			}
		?>
	</div>
</div> <!-- code_folder_sidebar -->


<!---------------------------------------------------------------------------->


<div class="mainarea" id="code_folder_mainarea">

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
		$revreqroot = '/' . $this->converter->AsciiToHex('.') . $revreq;
	}

	// print the main anchor for the root folder. 
	// let the anchor text be the project name.
	print anchor (
		"code/file/{$project->id}{$revreqroot}", 
		htmlspecialchars($project->name));

	// explode non-root folder parts to anchors
	$exps = explode ('/', $headpath);
	$expsize = count($exps);
	$par = '';
	for ($i = 1; $i < $expsize; $i++)
	{
		print '/';
		$par .= '/' . $exps[$i];
		$xpar = $this->converter->AsciiToHex ($par);
		print anchor (
			"code/file/{$project->id}/{$xpar}{$revreq}",
			htmlspecialchars($exps[$i]));
	}

	if ($headpath != $file['fullpath'])
	{
		print ' - ';
		print htmlspecialchars ($file['fullpath']);
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

	if (count($file['content']) <= 0)
	{
		 print $this->lang->line('MSG_NO_CODE_AVAIL');
	}
	else 
	{
		print '<div class="menu" id="code_folder_mainarea_menu">';
		$xpar = $this->converter->AsciiTohex ($headpath);
		if ($revision > 0 && $revision < $next_revision)
		{
			print anchor ("code/file/{$project->id}/{$xpar}", $this->lang->line('Head revision'));
			print ' | ';
		}
		print anchor ("code/history/{$project->id}/{$xpar}", $this->lang->line('History'));
		print '</div>';

		usort ($file['content'], 'comp_files');

		print '<div id="code_folder_mainarea_result">';
		print '<table id="code_folder_mainarea_result_table">';
		print '<tr class="heading">';
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Author') . '</th>';
		print '<th>' . $this->lang->line('Date') . '</th>';
		print '<th>' . $this->lang->line('Blame') . '</th>';
		print '<th>' . $this->lang->line('Difference') . '</th>';
		print '</tr>';

		$rowclasses = array ('even', 'odd');
		$rownum = 0;
		foreach ($file['content'] as $f)
		{
			$fullpath = $headpath . '/' . $f['name'];

			$rowclass = $rowclasses[++$rownum % 2];
			if ($f['type'] === 'dir')
			{
				// directory 
				$hexpath = $this->converter->AsciiToHex($fullpath);
       		         	print "<tr class='{$rowclass}'>";
				print '<td>';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
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
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
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
					"code/file/{$project->id}/{$hexpath}{$revreq}",
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
				//print date('r', $f['time_t']);
				print date('Y-m-d', $f['time_t']);
				print '</code></td>';

				print '<td>';
				print anchor (
					"code/blame/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Blame'));
				print '</td>';
				print '<td>';
				print anchor (
					"code/diff/{$project->id}/{$hexpath}{$revreq}",
					$this->lang->line('Difference'));
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';
		print '</div>';
	}
?>

</div> <!-- code_folder_mainarea -->


<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div> <!--  code_folder_content -->

</body>

</html>

