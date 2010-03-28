<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/user.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />

<script type="text/javascript">
$(function () {
	$('#user_home_mainarea_result').tabs();
});
</script>

<title><?=htmlspecialchars($login['id'])?></title>
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
		'banner' => $login['id'],
		'site' => NULL,
		'project' => NULL,
		'pageid' => '',
                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_home_mainarea">

<?php
$num_projects = count($projects);
$num_issues = count($issues);
?>

<div id="user_home_mainarea_result">

<ul>
	<li>
		<a href='#user_home_mainarea_result_my_projects'>
			<?=$this->lang->line('My projects')?> (<?=$num_projects?>)
		</a>
	</li>
	<li>
		<a href='#user_home_mainarea_result_my_issues'>
			<?=$this->lang->line('My issues')?> (<?=$num_issues?>)
		</a>
	</li>
</ul>

<div id="user_home_mainarea_result_my_projects">
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
</div>

<div id="user_home_mainarea_result_my_issues">
<ul>
<?php 
foreach ($issues as $issue) 
{
	$pro = $issue->projectid;
	$xid = $this->converter->AsciiToHex ((string)$issue->id);

	$anc = anchor ("issue/show/{$issue->projectid}/{$xid}", '#' . htmlspecialchars($issue->id));

	$status = htmlspecialchars(
		array_key_exists($issue->status, $issue_status_array)?
		$issue_status_array[$issue->status]: $issue->status);
	$type = htmlspecialchars(
		array_key_exists($issue->type, $issue_type_array)?
		$issue_type_array[$issue->type]: $issue->type);

	$sum = htmlspecialchars ($issue->summary);
	print "<li>{$pro} {$anc} {$type} {$status} - {$sum}</li>";
}
?>
</ul>
</div>

</div> <!-- user_home_mainarea_result -->

</div> <!-- user_home_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- user_home_content -->


</body>
</html>
