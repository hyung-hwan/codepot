<html>

<?php if (!isset($title)) $title = $this->lang->line('Error'); ?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/project.css" />
<title><?=$title?></title>
</head>

<body>

<div class="content" id="project_error_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
if (!isset($project))  $project = NULL;
if (!isset($site))  $site = NULL;

$this->load->view (
        'projectbar',
        array (
                'site' => $site,
                'project' => $project,
                'pageid' => '',
                'ctxmenuitems' => array ()
        )
);
?>

<!---------------------------------------------------------------------------->
<div class="mainarea" id="project_error_mainarea">
<div class="title" id="project_error_title">
<?= $title ?>
</div>
<?= $message ?>
</div>

<?php $this->load->view ('footer'); ?>

</div> <!-- project_error_content -->

</body>

</html>

