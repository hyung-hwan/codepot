<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/site.css" />

<script type="text/javascript" src="<?=base_url()?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?=base_url()?>/js/jquery-ui.min.js"></script>
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/jquery-ui.css" />


<script type="text/javascript">
$(function () {
	$('#site_new_button').button();
});
</script>

<title><?=htmlspecialchars($this->lang->line('Administration'))?></title>
</head>

<body>

<div class="content" id="site_adminhome_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
        'projectbar',
        array (
		'site' => NULL,
		'project' => NULL,
		'pageid' => '',
                'ctxmenuitems' => NULL
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="site_adminhome_mainarea">

<div class="title"><?=$this->lang->line('Administration')?></div>

<div class="infostrip">
<span class="title">Front Pages</span>
<?php print anchor ('site/create', $this->lang->line('New'), 'id="site_new_button"'); ?>
</div>

<ul>
<?php 
foreach ($sites as $site) 
{
	$cap = "{$site->name} ({$site->id})";
	$anc = anchor ("site/show/{$site->id}", htmlspecialchars($cap));
	print "<li>{$anc}</li>";
}
?>
</ul>

</div> <!-- site_adminhome_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- site_adminhome_content -->


</body>
</html>
