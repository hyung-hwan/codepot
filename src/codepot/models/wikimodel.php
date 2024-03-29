<?php

class WikiModel extends CI_Model
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
		$this->db->trans_start ();

		$this->db->where ('projectid', $project->id);
		$this->db->where ('name', $name);
		$query = $this->db->get ('wiki');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result ();
		if (empty($result))
		{
			$this->db->trans_complete ();
			return NULL;
		}

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $name);
		$this->db->order_by ('name', 'ASC');
		$query2 = $this->db->get ('wiki_attachment');

		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$this->db->trans_complete ();

		$wikis = $result[0];
		$wikis->attachments = $query2->result();

		return $wikis;
	}

	function getAll ($userid, $project)
	{
		$this->db->trans_start ();
		$this->db->where ('projectid', $project->id);
		$query = $this->db->get ('wiki');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		return $query->result ();
	}

	function getAttachment ($userid, $project, $wikiname, $name)
	{
		$this->db->trans_start ();

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $wikiname);
		$this->db->where ('name', $name);

		$query = $this->db->get ('wiki_attachment');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		$result = $query->result ();
		if (empty($result)) return NULL;

		return $result[0];
	}

	function getAttachments ($userid, $project, $wikiname)
	{
		$this->db->trans_start ();

		$this->db->select ('name,encname,createdon,createdby');
		$this->db->where ('projectid', $project->id);
		$this->db->where ('wikiname', $wikiname);

		$query = $this->db->get ('wiki_attachment');
		$this->db->trans_complete ();

		if ($this->db->trans_status() === FALSE) return FALSE;
		$result = $query->result ();

		return $result;
	}

	function create ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = codepot_nowtodbdate();

		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('name', $wiki->name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('columns', $wiki->columns);
		$this->db->set ('createdon', $now);
		$this->db->set ('updatedon', $now);
		$this->db->set ('createdby', $userid);
		$this->db->set ('updatedby', $userid);
		$this->db->insert ('wiki');

		foreach ($wiki->delete_attachments as $att)
		{
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('wikiname', $wiki->name);
			$this->db->where ('name', $att->name);
			$this->db->where ('encname', $att->encname);
			$this->db->delete ('wiki_attachment');

			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			if ($this->db->affected_rows() <= 0)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		foreach ($wiki->new_attachments as $att)
		{
			$this->db->set ('projectid', $wiki->projectid);
			$this->db->set ('wikiname', $wiki->name);
			$this->db->set ('name', $att['name']);
			$this->db->set ('encname', $att['encname']);
			$this->db->set ('createdon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->insert ('wiki_attachment');
		}

		$this->db->set ('createdon', $now);
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'create');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $wiki->name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function update ($userid, $wiki, $new_wiki_name = NULL)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = codepot_nowtodbdate();

		if (!is_null($new_wiki_name) && $wiki->name != $new_wiki_name)
		{
			// there is a change in name.
			// rename the wiki document and its attachments

			// check if the new name exists.
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('name', $new_wiki_name);
			$query = $this->db->get ('wiki');
			if ($this->db->trans_status() === FALSE) 
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			$result = $query->result ();
			if (!empty($result))
			{
				// the new name exists in the table.
				$this->db->trans_rollback ();
				return FALSE;
			}

			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('name', $wiki->name);
			$this->db->set ('name', $new_wiki_name);
			$this->db->set ('updatedon', $now);
			$this->db->set ('updatedby', $userid);
			$this->db->update ('wiki');

			// attachment renaming isn't needed because the
			// database table has a proper trigger set.
			//$this->db->where ('projectid', $wiki->projectid);
			//$this->db->where ('wikiname', $wiki->name);
			//$this->db->set ('wikiname', $new_wiki_name);
			//$this->db->update ('wiki_attachment');

			$effective_wiki_name = $new_wiki_name;
		}
		else
		{
			$effective_wiki_name = $wiki->name;
		}

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $effective_wiki_name);
		$this->db->set ('text', $wiki->text);
		$this->db->set ('columns', $wiki->columns);
		$this->db->set ('updatedon', $now);
		$this->db->set ('updatedby', $userid);
		$this->db->update ('wiki');

		foreach ($wiki->delete_attachments as $att)
		{
			$this->db->where ('projectid', $wiki->projectid);
			$this->db->where ('wikiname', $effective_wiki_name);
			$this->db->where ('name', $att->name);
			$this->db->where ('encname', $att->encname);
			$this->db->delete ('wiki_attachment');

			if ($this->db->trans_status() === FALSE)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}

			if ($this->db->affected_rows() <= 0)
			{
				$this->db->trans_rollback ();
				return FALSE;
			}
		}

		foreach ($wiki->new_attachments as $att)
		{
			$this->db->set ('projectid', $wiki->projectid);
			$this->db->set ('wikiname', $effective_wiki_name);
			$this->db->set ('name', $att['name']);
			$this->db->set ('encname', $att['encname']);
			$this->db->set ('createdon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->insert ('wiki_attachment');
		}

		// TODO: put rename message
		//$this->db->set ('createdon', $now);
		//$this->db->set ('type',      'wiki');
		//$this->db->set ('action',    'rename');
		//$this->db->set ('projectid', $wiki->projectid);
		//$this->db->set ('userid',    $userid);
		//$this->db->set ('message',   $effective_wiki_name);
		//$this->db->insert ('log');

		$this->db->set ('createdon', $now);
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'update');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $effective_wiki_name);
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	private function _delete_wiki ($userid, $wiki)
	{
		// TODO: check if userid can do this..
		$this->db->trans_begin ();


		$this->db->where ('projectid', $wiki->projectid);;
		$this->db->where ('wikiname', $wiki->name);
		$this->db->select ('encname');
		$query = $this->db->get ('wiki_attachment');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}
		$file_result = $query->result ();

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('wikiname', $wiki->name);
		$this->db->delete ('wiki_attachment');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->where ('projectid', $wiki->projectid);
		$this->db->where ('name', $wiki->name);
		$this->db->delete ('wiki');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$this->db->set ('createdon', codepot_nowtodbdate());
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    'delete');
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $wiki->name);
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		foreach ($file_result as $r)
		{
			@unlink (CODEPOT_ATTACHMENT_DIR . '/' . $r->encname);
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function delete ($userid, $wiki)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_delete_wiki ($userid, $wiki);
		restore_error_handler ();
		return $x;
	}
	///////////////////////////////////////////////////////////////////
	private function delete_all_files ($files)
	{
		foreach ($files as $f) @unlink ($f);
	}

	private function _edit_wiki ($userid, $wiki, $attached_files, $kill_files, $uploader)
	{
		$this->db->trans_begin (); // manual transaction. not using trans_start().

		$now = codepot_nowtodbdate();
		$is_create = empty($wiki->original_name);

		if ($is_create)
		{
			$this->db->set ('projectid', $wiki->projectid);;
			$this->db->set ('name', $wiki->name);
			$this->db->set ('text', $wiki->text);
			$this->db->set ('doctype', $wiki->doctype);
			$this->db->set ('createdon', $now);
			$this->db->set ('updatedon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->set ('updatedby', $userid);
			$this->db->insert ('wiki');
		}
		else
		{
			$this->db->where ('projectid', $wiki->projectid);;
			$this->db->where ('name', $wiki->original_name);
			$this->db->set ('name', $wiki->name);
			$this->db->set ('text', $wiki->text);
			$this->db->set ('doctype', $wiki->doctype);
			$this->db->set ('updatedon', $now);
			$this->db->set ('updatedby', $userid);
			$this->db->update ('wiki');
		}

		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			return FALSE;
		}

		$config['allowed_types'] = '*';
		$config['upload_path'] = CODEPOT_ATTACHMENT_DIR;
		$config['max_size'] = CODEPOT_MAX_UPLOAD_SIZE;
		$config['encrypt_name'] = TRUE;
		$config['overwrite'] = FALSE;
		$config['remove_spaces'] = FALSE;
		$uploader->initialize ($config);

		$ok_files = array();
		$file_count = count($attached_files);
		for ($i = 0; $i < $file_count; $i++)
		{
			$f = $attached_files[$i];
			if (!$uploader->do_upload($f['fid']))
			{
				$this->errmsg = "Failed to upload {$f['name']}";
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			$ud = $uploader->data();
			array_push ($ok_files, $ud['full_path']);

			/*$md5sum = @md5_file ($ud['full_path']);
			if ($md5sum === FALSE)
			{
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}*/

			$this->db->set ('projectid', $wiki->projectid);
			$this->db->set ('wikiname', $wiki->name);
			$this->db->set ('name', $f['name']);
			$this->db->set ('encname', $ud['file_name']);
			/*$this->db->set ('md5sum', $md5sum);*/
			$this->db->set ('createdon', $now);
			$this->db->set ('createdby', $userid);
			$this->db->insert ('wiki_attachment');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
		}

		$this->db->set ('createdon', $now);
		$this->db->set ('type',      'wiki');
		$this->db->set ('action',    ($is_create? 'create': 'update'));
		$this->db->set ('projectid', $wiki->projectid);
		$this->db->set ('userid',    $userid);
		$this->db->set ('message',   $wiki->name);
		$this->db->insert ('log');
		if ($this->db->trans_status() === FALSE)
		{
			$this->errmsg = $this->db->_error_message(); 
			$this->db->trans_rollback ();
			$this->delete_all_files ($ok_files);
			return FALSE;
		}

		if (!empty($kill_files))
		{
			$this->db->where ('projectid', $wiki->projectid);;
			$this->db->where ('wikiname', $wiki->name);
			$this->db->where_in ('name', $kill_files);
			$this->db->select ('encname');
			
			$query = $this->db->get ('wiki_attachment');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}
			
			$result = $query->result ();

			$this->db->where ('projectid', $wiki->projectid);;
			$this->db->where ('wikiname', $wiki->name);
			$this->db->where_in ('name', $kill_files);
			$this->db->delete ('wiki_attachment');
			if ($this->db->trans_status() === FALSE)
			{
				$this->errmsg = $this->db->_error_message(); 
				$this->db->trans_rollback ();
				$this->delete_all_files ($ok_files);
				return FALSE;
			}

			foreach ($result as $r)
			{
				@unlink (CODEPOT_ATTACHMENT_DIR . '/' . $r->encname);
			}
		}

		$this->db->trans_commit ();
		return TRUE;
	}

	function editWithFiles ($userid, $wiki, $attached_files, $kill_files, $uploader)
	{
		set_error_handler (array ($this, 'capture_error'));
		$errmsg = '';
		$x = $this->_edit_wiki ($userid, $wiki, $attached_files, $kill_files, $uploader);
		restore_error_handler ();
		return $x;
	}

	function search ($needle = '')
	{
		$items = array();
		if ($needle == '') return $items;

		$this->db->trans_start ();

		$this->db->select ('projectid,name,text,doctype');
		$this->db->like ('name', $needle);
		$this->db->or_like ('text', $needle);
		$query = $this->db->get('wiki');
		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}

		$result = $query->result();
		if (!empty($result))
		{
			foreach ($result as $r)
			{
				$posa = stripos($r->name, $needle);
				$posb = stripos($r->text, $needle);
				/* TODO: consider the wiki type and strip special tags, and let matching happen against the normal text only */
				if ($posa !== false || $posb !== false)
				{
					$text = "";
					if ($posb !== false) 
					{
						$start_pos = $posb - 30;
						if ($start_pos < 0) $start_pos = 0;
						$text = substr($r->text, $start_pos, strlen($needle) + 100);
					}
					array_push ($items, array( 'type' => 'wiki', 'projectid' => $r->projectid,  'name' => $r->name, 'partial_text' => $text));
				}
			}
		}

		$this->db->select ('projectid,wikiname,name');
		
		$this->db->like ('name', $needle);
		$query = $this->db->get ('wiki_attachment');

		if ($this->db->trans_status() === FALSE) 
		{
			$this->db->trans_complete ();
			return FALSE;
		}
		
		$result = $query->result();
		if (!empty($result))
		{
			foreach ($result as $r)
			{
				/* TODO: consider the wiki type and strip special tags, and let matching happen against the normal text only */
				if (($posa = stripos($r->name, $needle)) !== false)
				{
					array_push ($items, array( 'type' => 'wiki_attachment', 'projectid' => $r->projectid,  'name' => $r->name, 'wikiname' => $r->wikiname));
				}
			}
		}

		$this->db->trans_complete ();

		return $items;
	}

}

?>
