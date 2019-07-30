<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<?php
	$fileext = substr(strrchr($file['name'], '.'), 1);
	$fileext = strtolower($fileext);

	$is_image_file = FALSE;
	$is_pdf_file = FALSE;
	$is_odf_file = FALSE;
	$is_special_stream = FALSE;
	$is_wiki_file = FALSE;
	if (array_key_exists('properties', $file) && !is_null($file['properties']) && count($file['properties']) > 0)
	{
		$octet_stream = FALSE;
		foreach ($file['properties'] as $pn => $pv)
		{
			if ($pn == 'svn:mime-type')
			{
				if ($pv == 'application/octet-stream')
				{
					$octet_stream = TRUE;

					if (in_array ($fileext, array ('png', 'jpg', 'jpeg', 'gif', 'tif', 'bmp', 'ico')))
					{
						$img = @imagecreatefromstring ($file['content']);
						if ($img !== FALSE) 
						{
							@imagedestroy ($img);
							$is_image_file = TRUE;
							$is_special_stream = TRUE;
							break;
						}
					}
					else if (in_array ($fileext, array ('pdf')))
					{
						$is_special_stream = TRUE;
						$is_pdf_file = TRUE;
						break;
					}
					else if (in_array ($fileext, array ('odt', 'odp', 'ods')))
					{
						$is_special_stream = TRUE;
						$is_odf_file = TRUE;
						break;
					}
				}
				else if ($pv == 'application/pdf')
				{
					$is_special_stream = TRUE;
					$is_pdf_file = TRUE;
					break;
				}
				else if ($pv == 'application/vnd.oasis.opendocument.text' ||
				         $pv == 'application/vnd.oasis.opendocument.presentation' ||
				         $pv == 'application/vnd.oasis.opendocument.spreadsheet')
				{
					$is_special_stream = TRUE;
					$is_odf_file = TRUE;
					break;
				}
			}
		}
		if ($octet_stream) $is_special_stream = TRUE;
	}
	else if (in_array($fileext, array('md', 'wc')))
	{
		$is_wiki_file = TRUE;
	}
?>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/showdown.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-ada.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-basic.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-pascal.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/vis.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/vis.min.css')?>" />

<?php

if ($is_pdf_file)
{
	printf ('<script type="text/javascript" src="%s"></script>', base_url_make('/js/pdf.min.js'));
}
else if ($is_odf_file)
{
	printf ('<script type="text/javascript" src="%s"></script>', base_url_make('/js/webodf.js'));
}

$enstyle_anchor_text = '<i class="fa fa-magic"></i> ' . $this->lang->line('Enstyle');
$destyle_anchor_text = '<i class="fa fa-times"></i> ' . $this->lang->line('Destyle');

$hex_headpath = $this->converter->AsciiToHex ($headpath);
if ($revision <= 0)
{
	$revreq = '';
	$revreqroot = '';

	$history_path = "/code/history/{$project->id}/{$hex_headpath}";
}
else
{
	$revreq = "/{$file['created_rev']}";
	$revreqroot = '/' . $this->converter->AsciiToHex ('.') . $revreq;

	if ($hex_headpath == '') $revtrailer = $revreqroot;
	else $revtrailer = "/{$hex_headpath}{$revreq}";
	$history_path = "/code/history/{$project->id}{$revtrailer}";
}
?>

<script type="text/javascript">

<?php if ($is_pdf_file): ?>

var pdf_doc = null;
var pdf_page_num = 1;
var pdf_rendering_in_progress = false;
var pdf_page_num_pending = null;
var pdf_canvas = null;
var pdf_ctx = null;
var pdf_scale = "fw";

function render_pdf_page (num)
{
	pdf_rendering_in_progress = true;
	// Using promise to fetch the page
	pdf_doc.getPage(num).then(function(page) {
		var vp1 = page.getViewport (1);
		var scale;
		if (pdf_scale == "fw")
			scale = ($('#code_file_result').innerWidth() - 20) / vp1.width;
		else
			scale = parseFloat (pdf_scale);
		var viewport = page.getViewport(scale);
		pdf_canvas.height = viewport.height;
		pdf_canvas.width = viewport.width;

		// Render PDF page into canvas context
		var renderContext = {
			canvasContext: pdf_ctx,
			viewport: viewport
		};


		var renderTask = page.render(renderContext);

		// Wait for rendering to finish
		renderTask.promise.then(function () {
			pdf_rendering_in_progress = false;
			if (pdf_page_num_pending !== null) {
				// New page rendering is pending
				render_pdf_pagee(pdf_page_num_pending);
				pdf_page_num_pending = null;
			}
		});
	});

	$('#code_file_pdf_page_num').text (pdf_page_num);
	$('#code_file_pdf_page_slider').val (pdf_page_num);
}

function queue_pdf_rendering_in_progress (num) {
	if (pdf_rendering_in_progress) 
	{
		pdf_page_num_pending = num;
	}
	else
	{
		render_pdf_page (num);
	}
}

function on_next_pdf_page ()
{
	if (pdf_page_num >= pdf_doc.numPages) return;
	pdf_page_num++;
	queue_pdf_rendering_in_progress (pdf_page_num);
}

function on_prev_pdf_page ()
{
	if (pdf_page_num <= 1) return;
	pdf_page_num--;
	queue_pdf_rendering_in_progress (pdf_page_num);
}

function on_first_pdf_page ()
{
	pdf_page_num = 1;
	queue_pdf_rendering_in_progress (pdf_page_num);
}

function on_last_pdf_page ()
{
	pdf_page_num = pdf_doc.numPages;
	queue_pdf_rendering_in_progress (pdf_page_num);
}

<?php elseif ($is_odf_file): ?>

var odf_canvas = null;

<?php elseif (!$is_special_stream): ?>

var showing_raw_code = false;

function showRawCode()
{
	if (showing_raw_code)
	{
		$("#code_file_style_button").button("option", "label", '<?php print $destyle_anchor_text; ?>');
		$("#code_file_result_code").removeClass("prettyprinted");
		prettyPrint();
	}
	else
	{
		$("#code_file_style_button").button("option", "label", '<?php print $enstyle_anchor_text; ?>');
		$("#code_file_result_code").html($("#code_file_result_raw").html());
	}

	showing_raw_code = !showing_raw_code;
}

function render_wiki_wc ()
{
	creole_render_wiki (
		"code_file_wiki_text",
		"code_diff_new_code_view",
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/show/<?php print $project->id?>/"),
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/attachment0/<?php print $project->id?>/"),
		false
	);
}

function render_wiki_md ()
{
	showdown_render_wiki (
		"code_file_wiki_text",
		"code_diff_new_code_view",
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/show/<?php print $project->id?>/"),
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/attachment0/<?php print $project->id?>/"),
		false
	);
}

<?php endif; ?>

var GraphApp = (function ()
{
	// -------------------------------------------------------------------
	// CONSTRUCTOR
	// -------------------------------------------------------------------
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		//if (this.constructor != App)
		//{
		//	return new App (top_container, graph_container, graph_canvas, graph_button, graph_spin, graph_title);
		//}

		this.top_container = top_container;
		this.graph_container = graph_container;
		this.graph_msgdiv = graph_msgdiv;
		this.graph_canvas = graph_canvas;
		this.graph_button = graph_button;
		this.graph_spin = graph_spin;
		this.graph_ajax = null;
		this.graph_url = graph_url;
		this.graph_title = graph_title;

		this.graph_msgdiv.hide();
		this.graph_msgdiv.css ({'background-color': 'red', 'color': 'white', 'padding': '1em', 'float': 'left'});
		return this;
	}

	// -------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// -------------------------------------------------------------------
	function on_graph_success (response, textStatus, jqXHR)
	{
		var data;
		try { data = $.parseJSON(response); } 
		catch (e) { data = null; }

		if (data == null)
		{
			this.showMessage ('Invalid data received');
		}
		else
		{
			this.renderGraph (data);

			this.graph_container.dialog("option", "height", (this.top_container.height() * 90 / 100));
			this.graph_container.dialog("option", "width", (this.top_container.width() * 90 / 100));
			this.graph_container.dialog ({
				position: {
					my: "center center",
					at: "center center",
					of: this.top_container
				}
			});

			this.resizeGraph ();
		}

		this.graph_button.button("enable");
		this.graph_spin.removeClass ("fa-cog fa-spin");
		this.graph_ajax = null;
	}

	function on_graph_error (jqXHR, textStatus, errorThrown) 
	{
		this.showMessage (jqXHR.status + ' ' + errorThrown);
		this.graph_button.button("enable");
		this.graph_spin.removeClass ("fa-cog fa-spin");
		this.graph_ajax = null;
	}

	function open_graph()
	{
		this.graph_button.button ("disable");
		this.graph_spin.addClass ("fa-cog fa-spin");
		this.graph_container.dialog ("open");

		if (this.graph_ajax != null) this.graph_ajax.abort();
		this.graph_ajax = $.ajax ({
			url: this.graph_url,
			context: this,
			success: on_graph_success,
			error: on_graph_error
		});
	}

	// -------------------------------------------------------------------
	// PUBLIC FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.initWidgets = function () 
	{
		var self = this;

		this.graph_container.dialog ({
			title: this.graph_title,
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('Close')?>': function () {
					if (self.graph_ajax != null) return;
					self.graph_container.dialog('close');
				}
			},

			beforeClose: function() {
				return self.graph_ajax == null;
			},
		});

		this.graph_container.on ("dialogresize", function (evt, ui) {
			self.resizeGraph ();
		});

		this.graph_button.button().click (function ()
		{
			open_graph.call (self);
			return false;
		});
	};


	App.prototype.showMessage = function (msg)
	{
		this.graph_msgdiv.show();
		this.graph_msgdiv.text (msg);
		this.graph_msgdiv.position ({
			my: "center",
			at: "center",
			of: this.graph_container
		});
	}

	App.prototype.clearMessage = function()
	{
		this.graph_msgdiv.hide();
	}

	// -------------------------------------------------------------------
	// VIRTUAL FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.renderGraph = function (json) 
	{
		/* SHOULD BE IMPLEMENTED BY INHERITER */
	}

	App.prototype.resizeGraph = function ()
	{
		/* SHOULD BE IMPLEMENTED BY INHERITER */
	}

	return App;
})();

var RevGraphApp = (function ()
{
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		GraphApp.call (this, top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title);
		this.revision_network = null;
		return this;
	}
	App.prototype = Object.create (GraphApp.prototype);
	App.prototype.constructor = App;

	App.prototype.renderGraph = function (data)
	{
		if (data.nodes.length <= 0)
		{
			this.showMessage ('No data to show');
			return;
		}

		this.clearMessage ();
		var options = {
			autoResize: false,
			height: '400px',
			width: '90%',
			clickToUse: false,
			layout: {
				hierarchical: {
					enabled: true,

					//levelSeparation: 150,
					//nodeSpacing: 200,
					//treeSpacing: 300,
					direction: 'LR', //'LR' 'UD', 'DU', 'RL'
					sortMethod: 'directed' // 'hubsize'*/
				}
			},

			edges: {
				smooth: {
				    //type: 'cubicBezier',
				    //forceDirection: 'horizontal', // 'vertical',
				    roundness: 0.4
				}
			},
			physics: {
				//enabled: true
				enabled: false
			}
		};

		var i, j;

		j = data.nodes.length;
		for (i = 0; i < j; i++)
		{
			data.nodes[i].labelHighlightBold = false;
			data.nodes[i].shape = 'box';
			if (data.nodes[i]._type == '')
			{
				// no other nodes reache this node 
				data.nodes[i].color = '#553322';
				data.nodes[i].font = { color: 'white' };
			}
			else if (data.nodes[i]._type.indexOf('D') >= 0)
			{
				data.nodes[i].color = '#AA3344';
				data.nodes[i].font = { color: 'white' };
				data.nodes[i].label += '\n<<DELETED>>';
			}
			else if (data.nodes[i]._type.indexOf('A') >= 0)
			{
				data.nodes[i].color = '#227722';
				data.nodes[i].font = { color: 'white' };
				data.nodes[i].label += '\n<<NEW>>';
			}
		}

		j = data.edges.length;
		for (i = 0; i < j; i++)
		{
			data.edges[i].length = 60;
			data.edges[i].width = 1;
			data.edges[i].arrows = 'to';
			data.edges[i].font = { color: 'red' };
		}

		if (this.revision_network === null)
		{
			this.revision_network = new vis.Network(this.graph_canvas[0], data, options);
		}
		else
		{
			this.revision_network.setData (data);
		}
	}

	App.prototype.resizeGraph = function ()
	{
		if (this.revision_network != null)
		{
			this.revision_network.setSize (this.graph_container.width(), this.graph_container.height());
			this.revision_network.redraw();
			this.revision_network.fit();
		}
	}

	return App;
})();


$(function () {

	$('#code_file_metadata').accordion({
		collapsible: true,
		heightStyle: "content"
	});

<?php if (!$is_special_stream): ?>
	$("#code_file_loc_info").hide();
	btn = $("#code_file_loc_button").button().click (function () {
		if ($("#code_file_loc_info").is(":visible"))
		{
			$("#code_file_loc_info").hide("blind",{},200);
		}
		else
		{
			$("#code_file_loc_info").show("blind",{},200);
		}

		return false; // prevent the default behavior
	});

	
	$("#code_file_edit_button").button();
<?php endif; ?>


	var rev_graph_app = new RevGraphApp (
		$(window), 
		$("#code_file_revision_graph_container"),
		$("#code_file_revision_graph_error"),
		$("#code_file_revision_graph"),
		$("#code_file_revision_graph_button"),
		$("#code_file_revision_graph_spin"),
		codepot_merge_path ("<?php print site_url(); ?>", "/graph/enjson_revision_graph/<?php print $project->id; ?>/<?php print $hex_headpath;?><?php print $revreq?>"),
		"<?php print $this->lang->line('Revision')?>"
	);
	rev_graph_app.initWidgets ();

<?php if ($file['created_rev'] != $file['head_rev']): ?>
	$("#code_file_headrev_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}"; ?>'));
		return false;
	});
<?php endif; ?>

	$("#code_file_blame_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/blame/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});
	$("#code_file_diff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/diff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_file_fulldiff_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fulldiff/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	$("#code_file_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});
	$("#code_file_download_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

<?php if ($is_pdf_file): ?>
	pdf_canvas = document.getElementById('code_file_pdf_canvas');
	pdf_ctx = pdf_canvas.getContext('2d');

	PDFJS.workerSrc = "<?php print base_url_make('/js/pdf.worker.min.js'); ?>";

	//var pdf_data = new Uint8Array( [
		<?php
		/*
		$fc = &$file['content'];
		$len = strlen ($fc);
		printf ("%d", ord($fc[0]));
		for ($i = 1; $i < $len; $i++) printf (",%d", ord($fc[$i]));
		*/
		?>
	//]);
	var pdf_data = codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>');

	PDFJS.getDocument(pdf_data).then(function (pdf) {
		pdf_doc = pdf;
		render_pdf_page (pdf_page_num);

		$('#code_file_pdf_page_count').text (pdf_doc.numPages);

		$('#code_file_pdf_first_page').click (on_first_pdf_page);
		$('#code_file_pdf_last_page').click (on_last_pdf_page);
		$('#code_file_pdf_next_page').click (on_next_pdf_page);
		$('#code_file_pdf_prev_page').click (on_prev_pdf_page);
		$(window).resize(function () { queue_pdf_rendering_in_progress (pdf_page_num); });

		$('#code_file_pdf_page_slider').prop ('min', 1);
		$('#code_file_pdf_page_slider').prop ('max', pdf_doc.numPages);
		$('#code_file_pdf_page_slider').change (function () { 
			pdf_page_num = parseInt(this.value);
			queue_pdf_rendering_in_progress (pdf_page_num);
		});

		$('#code_file_pdf_scale').change (function () {
			pdf_scale = this.value;
			queue_pdf_rendering_in_progress (pdf_page_num);
		});

		$(document).keydown (function(event) {
			if (event.keyCode == 37)
			{
				// left-arrow
				on_prev_pdf_page();
			}
			else if (event.keyCode == 39) 
			{
				// right-arrow
				on_next_pdf_page();
			}
		});
	});

<?php elseif ($is_odf_file): ?>

	odf_canvas = new odf.OdfCanvas (document.getElementById('code_file_result'));
	odf_canvas.load (codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));

<?php elseif (!$is_special_stream): ?>

	$("#code_file_style_button").button({"label": '<?php print $destyle_anchor_text; ?>'}).click (function () {
		showRawCode();
		return false;
	});

	<?php if ($is_wiki_file): ?>

	<?php if ($fileext == "wc"): ?>
	render_wiki_wc ();
	<?php else: ?>
	render_wiki_md ();
	<?php endif; ?>

	<?php endif; ?>

	// for code rendering
	$("#code_file_result_raw").html ($("#code_file_result_code").html())
	prettyPrint ();

<?php endif; ?>
});


</script>

<title><?php 
	if ($headpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($headpath));
?></title>
</head>

<body>

<div class="content" id="code_file_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="code_file_mainarea">

<div class="codepot-title-band" id="code_file_title_band">

	<div class="title">
		<?php
		print anchor (
			"code/file/{$project->id}{$revreqroot}",
			htmlspecialchars($project->name));

		$exps = explode ('/', $headpath);
		$expsize = count($exps);
		$par = '';
		for ($i = 1; $i < $expsize; $i++)
		{
			$par .= '/' . $exps[$i];
			$xpar = $this->converter->AsciiToHex ($par);

			print '/';
			print anchor (
				"code/file/{$project->id}/{$xpar}{$revreq}",
				htmlspecialchars($exps[$i]));
		}

		if ($headpath != $file['fullpath'])
		{
			// it looks like the subversion module returns a URL-encoded
			// path when it contains a whitespace and the revision is given.
			// for example, "UOML SAMPLE.ODT" is returned as "UOML%20SAMPLE.ODT" 
			// when revision is specified. let's work around it.
			$decpath = urldecode ($file['fullpath']);
			if ($headpath != $decpath)
			{
				print ' - ';
				print htmlspecialchars($file['fullpath']);
			}
		}
		?>
	</div>

	<div class="actions">
		<?php 
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$file['prev_rev']}", '<i class="fa fa-arrow-circle-left"></i>');
		print ' ';

		// anchor to the revision history at the root directory
		print anchor (
			//"code/revision/{$project->id}/!/{$file['created_rev']}",
			"code/revision/{$project->id}/${hex_headpath}/{$file['created_rev']}",
			sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
		);

		if (!empty($file['created_tag']))
		{
			print ' ';
			print ('<span class="left_arrow_indicator">');
			print htmlspecialchars($file['created_tag']);
			print ('</span>');
		}

		print ' ';
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$file['next_rev']}", '<i class="fa fa-arrow-circle-right"></i>');

		print ' | ';
		printf ('%s: %s', $this->lang->line('Size'), $file['size']);

		if (!$is_special_stream)
		{
			if ((isset($login['id']) && $login['id'] != '') )
			{
				print ' ';
				print anchor ("code/edit/{$project->id}/{$hex_headpath}{$revreq}", $this->lang->line('Edit'), 'id="code_file_edit_button"');
			}
			print anchor ("#", "LOC", "id=code_file_loc_button");
		}
		?>
	</div>

	<div style="clear: both;"></div>
</div>

<div id='code_file_metadata' class='collapsible-box'>
	<div id='code_file_metadata_header' class='collapsible-box-header'>
		<?php
		print '<div class="metadata-committer">';
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['last_author']));
		print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
		print htmlspecialchars ($file['last_author']);
		print '</div>';

		print '<div class="metadata-menu">';

		$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame');
		$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
		$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
		$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
		$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');

		if ($file['created_rev'] != $file['head_rev']) 
		{
			$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head');
			print anchor ('#', $head_revision_text, 'id="code_file_headrev_button"');
		}

		print anchor ('#', $blame_anchor_text, 'id="code_file_blame_button"');
		print anchor ('#', $diff_anchor_text, 'id="code_file_diff_button"');
		print anchor ('#', $fulldiff_anchor_text, 'id="code_file_fulldiff_button"');
		print anchor ('#', $history_anchor_text, 'id="code_file_history_button"');
		//print anchor ('', $download_anchor_text, 'id="code_file_download_button"');
		print anchor ("code/fetch/{$project->id}/${hex_headpath}{$revreq}", $download_anchor_text, 'id="code_file_download_button"');
		if (!$is_special_stream) print anchor ('#', $this->lang->line('Enstyle'), 'id="code_file_style_button"');

		print '<a id="code_file_revision_graph_button" href="#">';
		print '<i id="code_file_revision_graph_spin" class="fa"></i>';
		print $this->lang->line('CODE_REVISION_GRAPH'); 
		print '</a>';
	
		print '</div>';

		print '<div class="metadata-commit-date">';
		printf ('[%s] ', $file['created_rev']);
		print codepot_unixtimetodispdate ($file['time_t']);
		print '</div>';
		?>
		<div style='clear: both'></div>
	</div>

	<div id='code_file_metadata_body' class='codepot-metadata-collapsible-body'>
		<div class='codepot-plain-text-view'>
			<pre><?php print htmlspecialchars ($file['logmsg']); ?></pre>
		</div>

		<?php
		if (array_key_exists('properties', $file) && !is_null($file['properties']) && count($file['properties']) > 0)
		{
			print '<ul id="code_file_property_list">';
			foreach ($file['properties'] as $pn => $pv)
			{
				print '<li>';
				print htmlspecialchars($pn);
				if ($pv != '')
				{
					print ' - ';
					print htmlspecialchars($pv);
				}
				print '</li>';
			}
			print '</ul>';
		}
		?>
	</div>
</div>

<div id="code_file_graph" class="graph">
	<div id="code_file_revision_graph_container">
		<div id="code_file_revision_graph"></div>
		<div id="code_file_revision_graph_error"></div>
	</div>
</div>

<div style="display:none">
<pre id="code_file_result_raw">
</pre>
</div>

<?php if ($is_special_stream): ?>
<div id="code_file_result">
<?php else: ?>
<div id="code_file_result" class="codepot-relative-container-view codepot-styled-code-view" >
<?php endif; ?>

<?php
if ($is_wiki_file) print '<div id="code_diff_old_code_view">';

if ($fileext == 'adb' || $fileext == 'ads') $fileext = 'ada';
else if ($fileext == 'pas') $fileext = 'pascal';
else if ($fileext == 'bas') $fileext = 'basic';

$prettyprint_lang = ($fileext != '')?  "lang-$fileext": '';

$prettyprint_linenums = 'linenums';
if ($login['settings'] != NULL &&
    $login['settings']->code_hide_line_num == 'Y') $prettyprint_linenums = '';
?>

<?php 
	if ($is_image_file)
	{
		print ('<img src="data:image;base64,' . base64_encode ($file['content']) . '" alt="[image]" />');
	}
	else if ($is_pdf_file)
	{
		print '<div id="code_file_pdf_navigator">';
		print '<button id="code_file_pdf_first_page"><i class="fa fa-fast-backward"></i></button>';
		print '<button id="code_file_pdf_prev_page"><i class="fa fa-backward"></i></button>';
		print '<input type="range" id="code_file_pdf_page_slider" step="1" />';
		print '<span id="code_file_pdf_page"><span id="code_file_pdf_page_num"></span>/<span id="code_file_pdf_page_count"></span></span>';
		print '<button id="code_file_pdf_next_page"><i class="fa fa-forward"></i></button>';
		print '<button id="code_file_pdf_last_page"><i class="fa fa-fast-forward"></i></button>';
		print '<select id="code_file_pdf_scale">';
		print '<option value="fw">Full-width</option>';
		for ($i = 50; $i <= 200; $i += 10) printf  ('<option value="%f">%d%%</option>', $i / 100, $i);
		print '</select>';
		print '</div>';
		print '<canvas id="code_file_pdf_canvas" style="border:1px solid black;"/>';
	}
	else if ($is_odf_file)
	{
	}
	else
	{
		printf ('<pre class="prettyprint %s %s" id="code_file_result_code">', $prettyprint_linenums, $prettyprint_lang);
		$charset = '';
		if (array_key_exists('properties', $file) && !is_null($file['properties']) && count($file['properties']) > 0)
		{
			$p = &$file['properties'];
			if (array_key_exists('svn:mime-type', $p))
			{
				if (@preg_match('|\s*[\w/+]+;\s*charset=(\S+)|i', $p['svn:mime-type'], $matches)) 
				{
					$charset = $matches[1];
				}
			}
		}

		if ($charset == '')
		{
			if (property_exists($project, 'codecharset') && strlen($project->codecharset))
				$charset = $project->codecharset;
		}

		if ($charset == '')
		{
			print htmlspecialchars($file['content']); 
		}
		else
		{
			// ignore iconv error
			print htmlspecialchars(@iconv ($charset, 'UTF-8//IGNORE', $file['content'])); 
		}

		print '</pre>';

		if (!$is_special_stream)
		{
			print '<div id="code_file_loc_info" class="codepot-infobox">';
			print '<div class="title">LOC</div>';
			/* TODO: show this if it's enabled in the user settings  */
			$graph_url = codepot_merge_path (site_url(), "/code/graph/cloc-file/{$project->id}/{$hex_headpath}{$revreq}");
			print "<img src='{$graph_url}' id='code_file_loc_info_locgraph' />";
			print '</div>';
		}
	}

	if ($is_wiki_file)
	{
		print '</div>';
		print '<div id="code_diff_new_code_view">';
		print '<pre id="code_file_wiki_text">';
		if ($charset == '')
		{
			print htmlspecialchars($file['content']);
		}
		else
		{
			// ignore iconv error
			print htmlspecialchars(@iconv ($charset, 'UTF-8//IGNORE', $file['content']));
		}
		print '</pre>';
		print '</div>';
	}
?>

</div> <!-- code_file_result -->

</div> <!-- code_file_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_file_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>

