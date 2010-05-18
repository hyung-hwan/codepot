<?php

class UserModel extends Model
{
	function UserModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function fetchSettings ($userid)
	{
		$this->db->trans_start ();
		$this->db->where ('userid', $userid);
		$query = $this->db->get ('user_settings');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_complete ();
			if ($this->db->trans_status() === FALSE) return FALSE;
			return NULL;
		}

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $result[0];
	}

	function storeSettings ($userid, $settings)
	{
		$this->db->trans_begin ();

		$this->db->where ('userid', $userid);
		$query = $this->db->get ('user_settings');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->set ('userid', $userid);
			$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
			$this->db->set ('code_hide_details', (string)$settings->code_hide_details);
			$this->db->insert ('user_settings');
		}
		else
		{
			$this->db->where ('userid', $userid);
			$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
			$this->db->set ('code_hide_details', (string)$settings->code_hide_details);
			$this->db->update ('user_settings');
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;

		/* affected_rows() does not seem to work reliably ...
		$this->db->trans_begin ();

		$this->db->where ('userid', $userid);
		$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
		$this->db->set ('code_hide_details', (string)$settings->code_hide_details);
		$this->db->update ('user_settings');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		if ($this->db->affected_rows () <= 0)
		{
			$this->db->set ('userid', $userid);
			$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
			$this->db->set ('code_hide_details', (string)$settings->code_hide_details);
			$this->db->insert ('user_settings');

			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		$this->db->trans_commit ();
		return TRUE;
		*/
	}

}

?>
