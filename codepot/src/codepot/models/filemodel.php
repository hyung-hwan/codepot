<?php

class FileModel extends CI_Model
{
	protected $errmsg = '';

	function capture_error ($errno, $errmsg)
	{
		$this->errmsg = $errmsg;
	}

	function getErrorMessage ()
	{
		return $this->errmsg;
	}

	function __construct ()
	{
		parent::__construct ();
		$this->load->database ();
	}

	function get ($userid, $project, $name)
	{
		$this->db->trans_begin ();
		$this->db->where ('projectid', $project->id);
		$this->db->where ('name', $name);
		$query = $this->db->get ('file');

		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result)) $file = NULL;
		else
		{
			$file = $result[0];

			$this->db->where ('projectid', $project->id);
			$this->db->where ('name', $file->name);
			$this->db->select ('filename,encname,md5sum,description,createdon,createdby');
			$query = $this->db->get('file_list');
			if ($this->db->trans_status() === FALSE) 
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback();
				return FALSE;
			}

			$file->file_list = $query->result();
		}

		$this->db->trans_commit();
		return $file;
	}

	function fetchFile ($userid, $project, $name)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$this->db->where ('filename', $name);
		$query = $this->db->get ('file_list');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;

		$result = $query->result ();
		return empty($result)? NULL: $result[0];
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_begin ();
		$this->db->where ('projectid', $project->id);
		$this->db->order_by ('tag', 'desc');
		$this->db->order_by ('name', 'asc');
		$query = $this->db->get ('file');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback();
			return FALSE;
		}

		$files = $query->result ();

		$subfiles = array ();
		foreach ($files as &$f)
		{
			$this->db->where ('projectid', $project->id);
			$this->db->where ('name', $f->name);
			$this->db->select ('filename,encname,md5sum,description,createdon,createdby');
			$query = $this->db->get('file_list');
			if ($this->db->trans_status() === FALSE) 
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback();
				return FALSE;
			}

			$f->file_list = $query->result();
		}

		$this->db->trans_commit ();

		return $files;
	}

	/*
	function create ($userid, $file)
	{
		$this->db->trans_start ();
		$this->db->set ('projectid', $file->projectid);
		$this->db->set ('name', $file->name);
		$this->db->set ('encname', $file->encname);
		$this->db->set ('tag', $file->tag);
		$this->db->set ('md5sum', $file->md5sum);
		$this->db->set ('description', $file->description);
		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedon', codepot_nowtodbdate());
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('file');

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $file->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $file->name);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		return $this->db->trans_status();
	}
	*/

	private function _update_file ($userid, $projectid, $name, $file)
	{
		$this->db->trans_start ();

		$this->db->where ('projectid', $projectid);
		$this->db->where ('name', $name);
		$this->db->set ('name', $file->name);
		$this->db->set ('tag', $file->tag);
		$this->db->set ('description', $file->description);
		$this->db->set ('updatedon', codepot_nowtodbdate());
		$this->db->set ('updatedby', $userid);
		$this->db->update ('file');
		// file_list gets updated for the schema itself (reference/trigger)
		/*
		// this way of updating is bad in that it won't update info
		// if there is no file items in file_list for the target file.
		$this->db->where ('f.projectid', $projectid);
		$this->db->where ('f.name', $name);
		$this->db->where ('f.projectid = fl.projectid');
		$this->db->where ('f.name = fl.name');
		$this->db->set ('f.name', $file->name);
		$this->db->set ('f.tag', $file->tag);
		$this->db->set ('f.description', $file->description);
		$this->db->set ('f.updatedon', codepot_nowtodbdate());
		$this->db->set ('f.updatedby', $userid);
		$this->db->set ('fl.name', $file->name);
		$this->db->update ('file as f, file_list as fl');
		*/

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $file->name);
		$this->db->insert ('log');

		$this->db->trans_complete ();
		return $this->db->trans_status();
	}

	function update ($userid, $projectid, $name, $file)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_update_file ($userid, $projectid, $name, $file);
		restore_error_handler ();
		return $x;
	}

	private function _delete_file ($userid, $projectid, $name)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->where ('projectid', $projectid);
		$this->db->where ('name', $name);
		$query = $this->db->get ('file_list');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$result = $query->result ();
		$file_names = array ();
		foreach ($result as $f)
		{
			array_push ($file_names, $f->encname);
		}

		$this->db->where ('projectid', $projectid);
		$this->db->where ('name', $name);
		$this->db->delete ('file_list');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->where ('projectid', $projectid);
		$this->db->where ('name', $name);
		$this->db->delete ('file');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$file_name_count = count($file_names);
		for ($i = 0; $i < $file_name_count; $i++)
		{
			$encname = $file_names[$i];
			$path = CODEPOT_FILE_DIR . '/' . $encname;
			if (@unlink ($path) === FALSE)
			{
				if ($i == 0)
				{
					$this->db->trans_rollback ();
					return FALSE;
				}
				else
				{
					// there is no good way to recover from the error.
					// carry on. some files will get orphaned.
				}
			}
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function delete ($userid, $projectid, $name)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_delete_file ($userid, $projectid, $name);
		restore_error_handler ();
		return $x;
	}

	private function delete_all_files ($files)
	{
		foreach ($files as $f) @unlink ($f);
	}

	private function _import_files ($userid, $projectid, $name, $tag, $description, $import_files, $uploader)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$this->db->set ('projectid', $projectid);
		$this->db->set ('name', $name);
		$this->db->set ('tag', $tag);
		$this->db->set ('description', $description);
		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedon', codepot_nowtodbdate());
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('file');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$config['allowed_types'] = '*';
		$config['upload_path'] = CODEPOT_FILE_DIR;
		$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
		$config['encrypt_name'] = TRUE;
		$config['overwrite'] = FALSE;
		$config['remove_spaces'] = FALSE;
		$uploader->initialize ($config);

		$ok_files = array();
		$file_count = count($import_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $import_files[$i];
			if (!$uploader->do_upload($f['fid']))
			{
				$this->errmsg = "Failed to upload {$f['name']}";
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$ud = $uploader->data();
			array_push ($ok_files, $ud['full_path']);

			$md5sum = @md5_file ($ud['full_path']);
			if ($md5sum === FALSE)
			{
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$this->db->set ('projectid', $projectid);
			$this->db->set ('name', $name);
			$this->db->set ('filename', $f['name']);
			$this->db->set ('encname', $ud['file_name']);
			$this->db->set ('md5sum', $md5sum);
			$this->db->set ('description', $f['desc']);
			$this->db->set ('createdby', $userid);
			$this->db->set ('createdon', codepot_nowtodbdate());
			$this->db->set ('updatedby', $userid);
			$this->db->set ('updatedon', codepot_nowtodbdate());
			$this->db->insert ('file_list');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
		}

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function import ($userid, $projectid, $name, $tag, $description, $import_files, $uploader)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_import_files ($userid, $projectid, $name, $tag, $description, $import_files, $uploader);
		restore_error_handler ();
		return $x;
	}

	private function _add_files ($userid, $projectid, $name, $import_files, $uploader)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$config['allowed_types'] = '*';
		$config['upload_path'] = CODEPOT_FILE_DIR;
		$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
		$config['encrypt_name'] = TRUE;
		$config['overwrite'] = FALSE;
		$config['remove_spaces'] = FALSE;
		$uploader->initialize ($config);

		$ok_files = array();
		$file_count = count($import_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $import_files[$i];
			if (!$uploader->do_upload($f['fid']))
			{
				$this->errmsg = "Failed to upload {$f['name']}";
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$ud = $uploader->data();
			array_push ($ok_files, $ud['full_path']);

			$md5sum = @md5_file ($ud['full_path']);
			if ($md5sum === FALSE)
			{
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$this->db->set ('projectid', $projectid);
			$this->db->set ('name', $name);
			$this->db->set ('filename', $f['name']);
			$this->db->set ('encname', $ud['file_name']);
			$this->db->set ('md5sum', $md5sum);
			$this->db->set ('description', $f['desc']);
			$this->db->set ('createdby', $userid);
			$this->db->set ('createdon', codepot_nowtodbdate());
			$this->db->set ('updatedby', $userid);
			$this->db->set ('updatedon', codepot_nowtodbdate());
			$this->db->insert ('file_list');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
		}

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function addFiles ($userid, $projectid, $name, $import_files, $uploader)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_add_files ($userid, $projectid, $name, $import_files, $uploader);
		restore_error_handler ();
		return $x;
	}

	private function _edit_files ($userid, $projectid, $name, $edit_files)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$kill_files = array();
		$file_count = count($edit_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $edit_files[$i];

			if (array_key_exists('kill', $f))
			{
				$this->db->where ('projectid', $projectid);
				$this->db->where ('name', $name);
				$this->db->where ('filename', $f['name']);
				$this->db->select ('encname');
				$query = $this->db->get('file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}

				$result = $query->result ();
				if (empty($result)) 
				{
					$this->errmsg = "no such file - {$f['name']}";
					$this->db->trans_rollback ();
					return FALSE;
				}

				array_push ($kill_files, CODEPOT_FILE_DIR . '/' . $result[0]->encname);

				$this->db->where ('projectid', $projectid);
				$this->db->where ('name', $name);
				$this->db->where ('filename', $f['name']);
				$query = $this->db->delete('file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}
			}
			else if (array_key_exists('desc', $f))
			{
				$this->db->where ('projectid', $projectid);
				$this->db->where ('name', $name);
				$this->db->where ('filename', $f['name']);
				$this->db->set ('description', $f['desc']);
				$this->db->set ('updatedby', $userid);
				$this->db->set ('updatedon', codepot_nowtodbdate());
				$this->db->update ('file_list');
				if ($this->db->trans_status() === FALSE)
				{
					$this->errmsg = $this->db->_error_message(); 
					$this->db->trans_rollback ();
					return FALSE;
				}
			}
		}

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'file');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->delete_all_files ($kill_files);
		$this->db->trans_commit ();
		return TRUE;
	}

	function editFiles ($userid, $projectid, $name, $edit_files)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_edit_files ($userid, $projectid, $name, $edit_files);
		restore_error_handler ();
		return $x;
	}
}

?>
