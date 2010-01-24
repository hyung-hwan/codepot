<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<?php
	$caption = $this->lang->line('Home');
	if (isset($loginid) && $loginid != '') $caption .= "({$loginid})";
?>
<title><?=htmlspecialchars($caption)?></title>
</head>

<body>

<div class="content" id="user_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
        'projectbar',
        array (
		'loginid' => $loginid,
		'project' => NULL,
		'pageid' => '',
                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_home_mainarea">

<div class="sidebar">

<div class="box">
<div class="boxtitle"><?=$this->lang->line('Latest projects')?></div>
<ul>
<?php
foreach ($latest_projects as $project)
{
	//$cap = "{$project->name} ({$project->id})";
	$cap = "{$project->name}";
	$anc = anchor ("project/home/{$project->id}", htmlspecialchars($cap));
	//$date = date ('Y/m/d', strtotime($project->createdon));
	//print "<tr><td>{$anc}</td><td>{$date}</td></tr>";
	print "<li>{$anc}</li>";
}
?>
</ul>
</div>
</div>

<!----------------------------------------------------------->

</div> <!-- user_home_mainarea -->

<?php $this->load->view ('footer'); ?>

</div> <!-- user_home_content -->

</body>
</html>
