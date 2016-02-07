<html>

<?php
$num_issues = count($issues);

$issues_by_projects = array();
foreach ($issues as $issue)
{
	if (!array_key_exists ($issue->projectid, $issues_by_projects))
		$issues_by_projects[$issue->projectid] = array();

	$arr = &$issues_by_projects[$issue->projectid];
	array_push ($arr, $issue);

	$unique_projects = array_keys ($issues_by_projects);
}
?>

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
	<?php
	for ($i = 0; $i < count($unique_projects); $i++)
	{
		printf ('$("#user_home_mainarea_open_issues_%d").accordion({collapsible:true, heightStyle:"content"}); ', $i);
	}
	?>
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

<div id="user_home_mainarea_result" class="result">

<div id="user_home_mainarea_open_issues">

	<?php
	for ($i = 0; $i < count($unique_projects); $i++)
	{
		printf ('<div id="user_home_mainarea_open_issues_%d" class="collapsible-box">', $i);
		$issues = &$issues_by_projects[$unique_projects[$i]];

		printf ('<div class="collapsible-box-header">%s</div>', htmlspecialchars($unique_projects[$i]));
		print '<ul>';
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
		print '</ul>';
		print '</div>';
	}
	?>

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
