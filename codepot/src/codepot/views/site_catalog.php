<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/site.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($this->lang->line('Administration'))?></title>
</head>

<body>

<div class="content" id="site_catalog_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$site = new stdClass();
$site->id = '';
$site->name = '';
$site->text = '';

$this->load->view (
	'projectbar',
	array (
		'banner' => $this->lang->line('Administration'),

		'page' => array (
			'type' => 'site',
			'id' => 'catalog',
			'site' => $site,
		),

		'ctxmenuitems' => array (
			array ('site/create', '<i class="fa fa-plus"></i> ' . $this->lang->line('New'))
		)
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="site_catalog_mainarea">


<div class="result" id="site_catalog_mainarea_result">
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
</div>

</div> <!-- site_catalog_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- site_catalog_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->



</body>
</html>
