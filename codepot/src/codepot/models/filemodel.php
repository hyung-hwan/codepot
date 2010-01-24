<?php

class FileModel extends Model
{
	function FileModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function get ($userid, $project, $name)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$this->db->where ('name', $name);
		$query = $this->db->get ('file');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;

		$result = $query->result ();
		return empty($result)? NULL: $result[0];
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$query = $this->db->get ('file');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function create ($userid, $file)
	{
		$this->db->trans_start ();
		$this->db->set ('projectid', $file->projectid);
		$this->db->set ('name', $file->name);
		$this->db->set ('encname', $file->encname);
		$this->db->set ('tag', $file->tag);
		$this->db->set ('summary', $file->summary);
		$this->db->set ('md5sum', $file->md5sum);
		$this->db->set ('description', $file->description);
		$this->db->set ('createdon', $userid);
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);

		$this->db->insert ('file');
		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function update ($userid, $file)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $file->projectid);
		$this->db->where ('name', $file->name);
		$this->db->set ('tag', $file->tag);
		$this->db->set ('summary', $file->summary);
		$this->db->set ('description', $file->description);
		$this->db->set ('updatedon', date('Y-m-d H:i:s'));
		$this->db->set ('updatedby', $userid);

		$this->db->update ('file');
		$this->db->trans_complete ();
                return $this->db->trans_status();
	}

	function delete ($userid, $file)
	{
		$this->db->trans_begin ();

		$this->db->where ('projectid', $file->projectid);
		$this->db->where ('name', $file->name);
		$query = $this->db->get ('file');
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$encname = $result[0]->encname;

		$this->db->where ('projectid', $file->projectid);
		$this->db->where ('name', $file->name);
		$this->db->delete ('file');
	
		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$path = CODEPOT_FILE_DIR . '/' . $encname;
		if (@unlink ($path) === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}
}

?>
