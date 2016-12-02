<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/user.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">
$(function () {
	$("#user_home_mainarea_open_issues").accordion ({
		collapsible: true,
		heightStyle: "content"
	});

	$("#user_home_mainarea_projects").accordion ({
		collapsible: true,
		heightStyle: "content"
	}); 
});
</script>

<title><?php print htmlspecialchars($login['id'])?></title>
</head>

<body>

<div class="content" id="user_home_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$user = new stdClass();
if ($target_userid == $login['id'])
{
	$user->id = $login['id'];
	$projectbar_type = 'user';
}
else
{
	$user->id = $target_userid;
	$projectbar_type = 'user-other';
}
$user->xid = $this->converter->AsciiTohex($user->id);

$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => $projectbar_type,
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

<div id="user_home_mainarea_result" class="result">

	<div id="user_home_mainarea_open_issues" class="collapsible-box">
		<div class="collapsible-box-header"><?php print $this->lang->line('Open issues')?></div>
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

	<div id="user_home_mainarea_projects" class="collapsible-box">
		<div class="collapsible-box-header"><?php print $this->lang->line('Projects')?></div>
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

</div> <!-- user_home_mainarea_result -->

</div> <!-- user_home_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- user_home_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->




</body>
</html>
