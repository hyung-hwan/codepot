<?php

class Main extends Controller 
{
	function __construct ()
	{
		parent::__construct ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
	}

	function index ($xurl = '')
	{
		redirect ("main/signin/$xurl");
	}

	function xhr_signin ()
	{
		$this->load->model ('UserModel', 'users');

		if($this->input->post('user_name'))
		{
			$user_name = $this->input->post('user_name');
			$user_pass = $this->input->post('user_pass');

			if ($this->login->authenticate ($user_name, $user_pass) === FALSE)
			{
				print 'error - ' . $this->login->getErrorMessage();
			}
			else
			{
				$settings = $this->users->fetchSettings ($user_name);
				if ($settings !== FALSE) $this->login->setUserSettings ($settings);
				print 'ok';
			}
		}
		else
		{
			$this->login->deauthenticate ();
			print 'ok';
		}
	}

	function signin ($xurl = '')
	{
		$this->load->model ('UserModel', 'users');
		$this->load->library(array('encrypt', 'form_validation', 'session'));

		//$this->form_validation->set_rules('user_name', 'username', 'required|alpha_dash');
		$this->form_validation->set_rules('user_name', 'username', 'required');
		$this->form_validation->set_rules('user_pass', 'password', 'required');
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');
		
		$data['message'] = '';

		if($this->input->post('user_name'))
		{
			$user_name = $this->input->post('user_name');
			$user_pass = $this->input->post('user_pass');
			$user_url = $this->input->post('user_url');

			if($this->form_validation->run())
			{
				if ($this->login->authenticate ($user_name, $user_pass) === FALSE)
				{
					$data['message'] = $this->login->getErrorMessage();
					$data['user_name'] = $user_name;
					$data['user_pass'] = $user_pass;
					$data['user_url'] = $user_url;
					$this->load->view ('login', $data);
				}
				else
				{
					$settings = $this->users->fetchSettings ($user_name);
					if ($settings !== FALSE)
						$this->login->setUserSettings ($settings);

					if ($user_url != '') redirect ($user_url);
					else redirect ('site/home');
				}
			}
			else
			{
				$data['user_name'] = $user_name;
				$data['user_pass'] = $user_pass;
				$data['user_url'] = $user_url;
				$this->load->view ('login', $data);
			}
		}
		else
		{
			$this->login->deauthenticate ();
			$data['user_name'] = '';
			$data['user_pass'] = '';
			$data['user_url'] = $this->converter->HexToAscii($xurl);
			$this->load->view ('login', $data);
		}
	}


	function signout ($xurl = "")
	{
		$this->login->deauthenticate ();
		$url = ($xurl != "")? $this->converter->HexToAscii($xurl): 'site/home';
		redirect ($url);
	}

}

