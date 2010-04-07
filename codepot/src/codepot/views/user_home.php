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
	$('#user_home_mainarea_result').accordion();
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
$user->id = $login['id'];

$this->load->view (
        'projectbar',
        array (
		'banner' => NULL,

		'page' => array (
			'type' => 'user',
			'id' => 'issues',
			'user' => $user,
		),

                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_home_mainarea">

<?php
$num_projects = count($projects);
$num_issues = count($issues);
$num_activities = 0;
?>

<div id="user_home_mainarea_result">

<h3>
	<a href='#user_home_mainarea_result_projects'>
		<?=$this->lang->line('Projects')?> (<?=$num_projects?>)
	</a>
</h3>
<div id="user_home_mainarea_result_projects">
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

<h3>
	<a href='#user_home_mainarea_result_issues'>
		<?=$this->lang->line('Open issues')?> (<?=$num_issues?>)
	</a>
</h3>
<div id="user_home_mainarea_result_issues">
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
