<html>

<?php if (!isset($title)) $title = 'ERROR'; ?>

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
$this->load->view (
        'projectbar',
        array (
                'project' => NULL,
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

</div> <!-- project_error_taskbar -->

</body>

</html>

