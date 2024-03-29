<?php

class UserModel extends CI_Model
{
	function __construct ()
	{
		parent::__construct ();
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

	function storeSettings ($userid, $settings, $uploaded_icon_name)
	{
		$icon_name_set = strlen($settings->icon_name) > 0;

		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('userid', $userid);
		$query = $this->db->get ('user_settings');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->set ('userid', $userid);
			$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
			$this->db->set ('code_hide_metadata', (string)$settings->code_hide_metadata);
			if ($icon_name_set) $this->db->set ('icon_name', (string)$settings->icon_name);
			$this->db->set ('user_summary', (string)$settings->user_summary);
			$this->db->insert ('user_settings');
		}
		else
		{
			$this->db->where ('userid', $userid);
			$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
			$this->db->set ('code_hide_metadata', (string)$settings->code_hide_metadata);
			if ($icon_name_set) $this->db->set ('icon_name', (string)$settings->icon_name);
			$this->db->set ('user_summary', (string)$settings->user_summary);
			$this->db->update ('user_settings');
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		if ($icon_name_set)
		{
			if (@rename (CODEPOT_USERICON_DIR . '/' . $uploaded_icon_name,
			             CODEPOT_USERICON_DIR . '/' . $settings->icon_name) === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		$this->db->trans_commit ();
		return TRUE;

		/* affected_rows() does not seem to work reliably ...
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('userid', $userid);
		$this->db->set ('code_hide_line_num', (string)$settings->code_hide_line_num);
		$this->db->set ('code_hide_metadata', (string)$settings->code_hide_metadata);
		if (strlen($icon_name_set) $this->db->set ('icon_name', (string)$settings->icon_name);
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
			$this->db->set ('code_hide_metadata', (string)$settings->code_hide_metadata);
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
