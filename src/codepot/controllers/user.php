<?php

class User extends CI_Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_LOG = 'log';
	var $VIEW_HOME = 'user_home';
	var $VIEW_ISSUE = 'user_issue';
	var $VIEW_SETTINGS = 'user_settings';

	function __construct ()
	{
		parent::__construct ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->lang->load ('common', CODEPOT_LANG);
		$this->lang->load ('user', CODEPOT_LANG);
	}

	function index ()
	{
		return $this->home ();
	}

	function home ($userid = '')
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '') 
		{
			redirect ('main/signin');
			return;
		}

		if ($userid == '') $userid = $login['id'];
		else $userid = $this->converter->HexToAscii ($userid);
		if ($userid == '')
		{
			redirect ('site/home');
			return;
		}

		$this->load->library ('IssueHelper', 'issuehelper');
		$this->lang->load ('issue', CODEPOT_LANG);

		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('IssueModel', 'issues');
		$this->load->model ('UserModel', 'users');

		$user = new stdClass();
		$user->id = $userid;
		$user->xid = $this->converter->AsciiToHex($user->id);
		$user->summary = '';
		$settings = $this->users->fetchSettings ($user->id);
		if ($settings !== FALSE && $settings !== NULL) $user->summary = $settings->user_summary;

		$projects = $this->projects->getMyProjects ($userid);

		$issues = $this->issues->getMyIssues (
			$userid, $this->issuehelper->_get_open_status_array($this->lang));
		if ($projects === FALSE || $issues === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$data['login'] = $login;
			$data['projects'] = $projects;
			$data['user'] = $user;
			$data['issues'] = $issues;
			$data['issue_type_array'] = $this->issuehelper->_get_type_array($this->lang);
			$data['issue_status_array'] = $this->issuehelper->_get_status_array($this->lang);
			$data['issue_priority_array'] = $this->issuehelper->_get_priority_array($this->lang);
			$this->load->view ($this->VIEW_HOME, $data);
		}
	}

	function log ($userid = '', $offset = 0)
	{
		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '') 
		{
			redirect ('main/signin');
			return;
		}

		if ($userid == '') $userid = $login['id'];
		else $userid = $this->converter->HexToAscii ($userid);
		if ($userid == '')
		{
			redirect ('site/home');
			return;
		}

		$this->load->model ('ProjectModel', 'projects');
		$this->load->model ('UserModel', 'users');

		$user = new stdClass();
		$user->id = $userid;
		$user->xid = $this->converter->AsciiToHex($user->id);
		$user->summary = '';
		$settings = $this->users->fetchSettings ($user->id);
		if ($settings !== FALSE && $settings !== NULL) $user->summary = $settings->user_summary;

		$myprojs = $this->projects->getMyProjects ($user->id);
		if ($myprojs === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($myprojs === NULL || count($myprojs) == 0)
		{
			$data['login'] = $login;
			$data['message'] = 'NO PROJECTS';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->load->library ('pagination');
			$this->load->model ('LogModel', 'logs');

			$numprojs = count($myprojs);
			$projids = array();
			for ($i = 0; $i < $numprojs; $i++) 
				$projids[$i] = $myprojs[$i]->id;

			$num_log_entries = $this->logs->getNumEntries ($projids, $userid);
			if ($num_log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$pagecfg['base_url'] = site_url() . "/user/log/{$user->xid}/";
			$pagecfg['total_rows'] = $num_log_entries;
			$pagecfg['per_page'] = CODEPOT_MAX_LOGS_PER_PAGE; 
			$pagecfg['uri_segment'] = 4;
			$pagecfg['first_link'] = $this->lang->line('First');
			$pagecfg['last_link'] = $this->lang->line('Last');

			$log_entries = $this->logs->getEntries ($offset, $pagecfg['per_page'], $projids, $userid);
			if ($log_entries === FALSE)
			{
				$data['login'] = $login;
				$data['message'] = 'DATABASE ERROR';
				$this->load->view ($this->VIEW_ERROR, $data);
				return;
			}

			$this->pagination->initialize ($pagecfg);

			$data['user'] = $user;
			$data['login'] = $login;
			$data['log_entries'] = $log_entries;
			$data['page_links'] = $this->pagination->create_links ();

			$this->load->view ($this->VIEW_LOG, $data);
		}
	}

	function settings ()
	{
		$this->load->model ('UserModel', 'users');
		$this->load->library(array('encrypt', 'form_validation', 'session'));

		$login = $this->login->getUser ();
		if (CODEPOT_SIGNIN_COMPULSORY && $login['id'] == '')
			redirect ('main/signin');

		if ($login['id'] == '')
		{
			redirect ('site/home');
			return;
		}

		$data['login'] = $login;
		$data['message'] = '';

		$user = new stdClass();
		$user->id = $login['id'];
		$user->xid = $this->converter->AsciiToHex($user->id);
		$user->summary = '';
		$settings = $this->users->fetchSettings($user->id);
		if ($settings !== FALSE && $settings !== NULL) $user->summary = $settings->user_summary;

		$icon_fname = FALSE;
		$uploaded_fname = FALSE;

		if($this->input->post('settings'))
		{
			if (array_key_exists ('icon_img_file_name', $_FILES))
			{
				$fname = $_FILES['icon_img_file_name']['name'];

				if (strpos ($fname, ':') !== FALSE)
				{
					$data['message'] = $this->lang->line ('FILE_MSG_NAME_NO_COLON');
					$data['settings'] = $settings;
					$data['user'] = $user;
					$this->load->view ($this->VIEW_SETTINGS, $data);
					return;
				}

				// delete all \" instances ... 
				$_FILES['icon_img_file_name']['type'] = 
					str_replace('\"', '', $_FILES['icon_img_file_name']['type']);
				// delete all \\ instances ...  
				$_FILES['icon_img_file_name']['type'] = 
					str_replace('\\', '', $_FILES['icon_img_file_name']['type']);

				$config['allowed_types'] = 'png';
				$config['upload_path'] = CODEPOT_USERICON_DIR;
				$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
				$config['max_width'] = 100; // TODO: make it configurable.
				$config['max_height'] = 100;
				$config['encrypt_name'] = TRUE;

				$this->load->library ('upload');
				$this->upload->initialize ($config);

				if ($this->upload->do_upload ('icon_img_file_name'))
				{
					$upload = $this->upload->data ();
					$uploaded_fname = $upload['file_name'];
					$icon_fname = $login['id'] . '.png';
				}
			}

			//
			// make sure that these field also exist in the database
			// also change the sanity check in LoginModel/getUser()
			// if you add/delete fields to the settings object.
			//
			$settings = new stdClass();
			$settings->code_hide_line_num = $this->input->post('code_hide_line_num');
			$settings->code_hide_metadata = $this->input->post('code_hide_metadata');
			$settings->user_summary = $this->input->post('user_summary');

			/* $uploaded_fname will be renamed to this name in users->storeSettings() */
			$settings->icon_name = $icon_fname; 

			if ($this->users->storeSettings ($login['id'], $settings, $uploaded_fname) === FALSE)
			{
				@unlink (CODEPOT_USERICON_DIR . '/' . $uploaded_fname);
				$data['message'] = 'DATABASE ERROR';
				$data['settings'] = $settings;
				$data['user'] = $user;
				$this->load->view ($this->VIEW_SETTINGS, $data);
			}
			else
			{
				$this->login->setUserSettings ($settings);

				// force change user->summary when the new settings has
				// been saved successfully.
				$user->summary = $settings->user_summary;

				$data['message'] = 'SETTINGS STORED SUCCESSFULLY';
				$data['settings'] = $settings;
				$data['user'] = $user;
				$this->load->view ($this->VIEW_SETTINGS, $data);
			}
		}
		else
		{
			$settings = $this->users->fetchSettings ($login['id']);
			if ($settings === FALSE || $settings === NULL)
			{
				if ($settings === FALSE) $data['message'] = 'DATABASE ERROR';
				$settings = new stdClass();
				$settings->code_hide_line_num = ' ';
				$settings->code_hide_metadata = ' ';
				$settings->icon_name = '';
				$settings->user_summary = '';
			}

			$data['settings'] = $settings;
			$data['user'] = $user;
			$this->load->view ($this->VIEW_SETTINGS, $data);
		}
	}

	function icon ($userid = '')
	{
		$userid_len = strlen($userid);
		if ($userid_len > 0)
		{
			$userid = $this->converter->HexToAscii ($userid);
			$userid_len = strlen($userid);
		}

		if ($userid_len > 0)
		{
			$icon_path = CODEPOT_USERICON_DIR . '/' . $userid . '.png';

			$stat = @stat($icon_path);
			if ($stat !== FALSE)
			{
				$etag = sprintf ('%x-%x-%x-%x', $stat['dev'], $stat['ino'], $stat['size'], $stat['mtime']);
				$lastmod = gmdate ('D, d M Y H:i:s', $stat['mtime']);

				header ('Last-Modified: ' . $lastmod . ' GMT');
				header ('Etag: ' . $etag);

				if ((isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) ||
				    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $stat['mtime']))
				{
					header('Not Modified', true, 304);
					flush ();
					return;
				}
			}

			$icon_size = @filesize ($icon_path);
			if (@file_exists($icon_path) === TRUE && 
			    ($icon_size = @filesize($icon_path)) !== FALSE &&
			    @getimagesize($icon_path) !== FALSE)
			{
				header ("Content-Type: image/png");
				header ("Content-Length: $icon_size");
				@readfile ($icon_path);
				return;
			}
		}

		$img = imagecreate(50, 50);

		$bgcolor = imagecolorallocate($img, 250, 255, 250);
		imagefill ($img, 0, 0, $bgcolor);

		if ($userid_len > 0) 
		{
			$fgcolor = imagecolorallocate($img, 0, 0, 0);
			$font_size = 4;
			$font_width = imagefontwidth(5);
			$font_height = imagefontheight(5);
			$img_width = imagesx($img);
			$y = 2;
			$k = 1;
			for ($i = 0; $i < $userid_len; $i++) 
			{
				$x = $k * $font_width;
				if ($x > $img_width - $font_width) 
				{
					$k = 1;
					$y += $font_height + 2;
					$x = $k * $font_width;
				}

				$k++;
				imagechar ($img, $font_size, $x, $y, $userid[$i], $fgcolor);
			}
		}

		header ("Content-Type: image/png");
		imagepng ($img);
		imagedestroy ($img);
	}
}

?>
