<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/site.css" />

<title><?=htmlspecialchars($this->lang->line('Administration'))?></title>
</head>

<body>

<div class="content" id="site_catalog_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$site->id = '';
$site->name = '';
$site->text = '';

$this->load->view (
        'projectbar',
        array (
		'banner' => $this->lang->line('Administration'),
		'site' => $site,
		'project' => NULL,
		'pageid' => 'site',
                'ctxmenuitems' => array (
			array ('site/create', $this->lang->line('New'))
		)
        )
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="site_catalog_mainarea">

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

</div> <!-- site_catalog_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- site_catalog_content -->


</body>
</html>
