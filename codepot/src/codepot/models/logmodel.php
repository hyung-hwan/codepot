<?php

class LogModel extends Model
{
	function LogModel ()
	{
		parent::Model ();
		$this->load->database ();
	}

	function getNumSvnCommits ($projectid = '')
	{
		$this->db->trans_start ();

		$this->db->where ('type', 'svn-commit');
		if ($projectid != '') $this->db->where ('projectid', $projectid);
		$num = $this->db->count_all ('log');

		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		return $num;
	}

	function getSvnCommits ($offset, $limit, $projectid = '')
	{
		$this->db->trans_start ();

		$this->db->where ('type', 'svn-commit');
		if ($projectid != '') $this->db->where ('projectid', $projectid);
		$this->db->order_by ('createdon', 'desc');
		$query = $this->db->get ('log', $limit, $offset);

		$result = $query->result ();
		$this->db->trans_complete ();
		if ($this->db->trans_status() === FALSE) return FALSE;

		$count = 0;
		$commits = array ();
		foreach ($result as $row)
		{
			list($repo,$rev) = split('[,]', $row->message);

			/* $row->project must be equal to $repo */
			$commits[$count]['time'] = $row->createdon;
			$commits[$count]['type'] = $row->type;
			$commits[$count]['projectid'] = $row->projectid;

			$commits[$count]['svn_repo'] = $repo;
			$commits[$count]['svn_rev'] = $rev;

			$log = @svn_log (
				'file:///'.CODEPOT_SVNREPO_DIR."/{$repo}",
				$rev, $rev, 1,SVN_DISCOVER_CHANGED_PATHS);
			if ($log === FALSE || count($log) < 1)
			{
				$commits[$count]['svn_author'] = '';
				$commits[$count]['svn_message'] = '';
				$commits[$count]['svn_time'] = '';
			}
			else
			{
				$commits[$count]['svn_author'] = $log[0]['author'];
				$commits[$count]['svn_message'] = $log[0]['msg'];
				$commits[$count]['svn_time'] = $log[0]['date'];
			}
	
			$count++;
		}	
	
		return $commits;
	}

	function writeSvnCommit ($repo, $rev)
	{
		$log->type = 'svn-commit';
		$log->projectid = $repo;
		$log->message = "{$repo},{$rev}";
		$this->write ($log);
	}

	function write ($log)
	{
		$this->db->trans_begin ();

		$this->db->set ('type', $log->type);
		$this->db->set ('projectid', $log->projectid);
		$this->db->set ('message', $log->message);
		$this->db->set ('createdon', date('Y-m-d H:i:s'));
		$this->db->insert ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$this->db->trans_commit ();
			return TRUE;
		}
	}

	function delete ($log)
	{
		$this->db->trans_begin ();

		$this->db->where ('id', $log->id);
		$this->db->delete ('log');

		if ($this->db->trans_status() === FALSE)
		{
			$this->db->trans_rollback ();
			return FALSE;
		}
		else
		{
			$this->db->trans_commit ();
			return TRUE;
		}
	}
}

?>
