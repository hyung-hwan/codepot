<?php

class Site extends Controller 
{
	var $VIEW_ERROR = 'error';
	var $VIEW_EDIT = 'site_edit';
	var $VIEW_DELETE = 'site_delete';

	function Site ()
	{
		parent::Controller ();

		$this->load->helper ('url');
		$this->load->helper ('form');
		$this->load->library ('Converter', 'converter');
		$this->load->model (CODEPOT_LOGIN_MODEL, 'login');

		$this->load->library ('Language', 'lang');
		$this->lang->load ('common', CODEPOT_LANG);
	}

	function _edit_site ($site, $mode, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;

                // SET VALIDATION RULES
		$this->form_validation->set_rules (
			'site_id', 'ID', 'required|alpha_dash|max_length[32]');
		$this->form_validation->set_rules (
			'site_name', 'name', 'required|max_length[128]');
		$this->form_validation->set_rules (
			'site_text', 'text', 'required');
		$this->form_validation->set_error_delimiters(
			'<span class="form_field_error">','</span>');

		$data['message'] = '';
		$data['mode'] = $mode;

		if($this->input->post('site'))
		{
			$tmpid = ($mode == 'update')? 
				$site->id: $this->input->post('site_id');

			// recompose the site information from POST data.
			unset ($site);
			$site->id = $tmpid;
			$site->name = $this->input->post('site_name');
			$site->text = $this->input->post('site_text');

			// validate the form
			if ($this->form_validation->run())
			{
				// if ok, take action
				$result = ($mode == 'update')?
					$this->sites->update ($login['id'], $site):
					$this->sites->create ($login['id'], $site);
				if ($result === FALSE)
				{
					$data['message'] = 'DATABASE ERROR';
					$data['site'] = $site;
					$this->load->view ($this->VIEW_EDIT, $data);
				}
				else
				{
					//redirect ('user/home/' . $site->id);
					redirect ('user/home');
				}
			}
			else
			{
				// if not, reload the edit view with an error message
				$data['message'] = 'Your input is not complete, Bro.';
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
		else
		{
			if ($mode == 'update')
			{
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
			else
			{
				$data['site'] = $site;
				$this->load->view ($this->VIEW_EDIT, $data);
			}
		}
	}

	function create ($siteid = "")
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$site->id = $siteid;
			$site->name = '';
			$site->text = '';

			$this->_edit_site ($site, 'create', $login);
		}
	}

	function update ($siteid)
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		$site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($site === NULL)
		{
			$data['login'] = $login;
			$data['message'] = "NO SUCH SITE - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_edit_site ($site, 'update', $login);
		}
	}

	function _delete_site ($site, $login)
	{
		$this->load->helper ('form');
		$this->load->library ('form_validation');

		$data['login'] = $login;
		$data['message'] = '';

		$this->form_validation->set_rules ('site_confirm', 'confirm', 'alpha');
		$this->form_validation->set_error_delimiters('<span class="form_field_error">','</span>');

		if($this->input->post('site'))
		{
			/* the site form has been posted */
			$data['site_confirm'] = $this->input->post('site_confirm');

			if ($this->form_validation->run())
			{
				if ($data['site_confirm'] == 'yes')
				{
					$result = $this->sites->delete ($login['id'], $site);
					if ($result === FALSE)
					{
						$data['message'] = 'DATABASE ERROR';
						$data['site'] = $site;
						$this->load->view ($this->VIEW_DELETE, $data);
					}
					else 
					{
						// the site has been deleted successfully.
						// go back to the user home.	
						redirect ('user/home');
					}
				}
				else 
				{
					// the confirm checkbox is not checked.
					// go back to the site home page.
					//redirect ('user/home/' . $site->id);
					redirect ('user/home');
				}
			}
			else
			{
				// the form validation failed.
				// reload the form with an error message.
				$data['message'] = "Your input is not complete, Bro.";
				$data['site'] = $site;
				$this->load->view ($this->VIEW_DELETE, $data);
			}
		}
		else
		{
			/* no site posting is found. this is the fresh load */
			$data['site_confirm'] = 'no';
			$data['site'] = $site;
			$this->load->view ($this->VIEW_DELETE, $data);
		}
	}

	function delete ($siteid)
	{
		$this->load->model ('SiteModel', 'sites');

		$login = $this->login->getUser ();
		if ($login['id'] == '') redirect ('main/signin');

		$site = $this->sites->get ($siteid);
		if ($site === FALSE)
		{
			$data['login'] = $login;
			$data['message'] = 'DATABASE ERROR';
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if ($site === NULL)
		{
			$data['login'] = $login;
			$data['message'] = "NO SUCH SITE - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else if (!$login['sysadmin?'])
		{
			$data['login'] = $login;
			$data['message'] = "NO PERMISSION - $siteid";
			$this->load->view ($this->VIEW_ERROR, $data);
		}
		else
		{
			$this->_delete_site ($site, $login);
		}
	}
}

?>
