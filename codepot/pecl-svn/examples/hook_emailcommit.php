<?php


/* 
  example email on commit hook script
  usage:
  php ..path..to..this..file../hook_emailcommit.php "$REPOS" "$REV" who@gets.it >> /tmp/svnlog 
  
  Features:
    - emails diff to email address
    - adds error messages if it's a PHP file.
    - sends popup messages to author on errors (using /hooks/popup.ini)
      (use www.realpoup.it for winxp boxes)
      
  TODO: 
    - write bindings for diff so that it doesnt have to use the command line..
  

*/
dl('svn.so');

class Subversion_EmailCommit {

    var $repos;
    var $rev;
    var $email; //who gets the commit messages.
    
    function start($args) {
        print_r($args);
        list( $cmd , $this->repos, $this->rev , $this->email ) = $args; 
        
        if ($this->repos{0} == '/') {
            $this->repos = $this->repos = 'file://'. $this->repos;
        }
        
        $this->rev  = (int) $this->rev;
        $last = $this->rev -1 ;
        // techncially where the diff is!?
        require_once 'System.php';
        $svn = System::which('svn','/usr/bin/svn');
        
        $cmd = "$svn diff -r{$last}:{$this->rev} $this->repos";
        $this->log = svn_log($this->repos, $this->rev,  $this->rev-1, 0, SVN_DISCOVER_CHANGED_PATHS);
        
        $syntax = $this->checkSyntax();
        //echo $cmd;
        
        
        $diff = `$cmd`;
        $diff = $this->log[0]['msg'] ."\n\n". $diff;
        
        
        if ($syntax) {
            $diff = $syntax ."\n\n". $diff;
        }
        
       
        $bits = explode('@', $this->email);
        
        $headers['From']    = "{$this->log[0]['author']} <{$this->log[0]['author']}@{$bits[1]}>";
        $headers['To']      = $this->email;
        $headers['Subject'] = "[SVN {$bits[1]}] ".
            ($syntax ? "ERROR!" : "") . 
            $this->getFilenames() . " ({$this->rev})";
            
        $headers['Date']    = date('r');
        $headers['X-Mailer']  = 'svn hook';
        // Create the mail object using the Mail::factory method
        require_once 'Mail.php';
        $mail_object =& Mail::factory('smtp', $params);
        $mail_object->send($this->email, $headers, $diff); 
        
        $this->sendPopup($syntax);
        
    }
    
    function sendPopup($syntax) {
        if (!$syntax) {
            
            return;
        }
        if (substr($this->repos,0,strlen("file://")) != "file://") {
        //    echo "repos is not file://";
            return;
        }
        $file = substr($this->repos,strlen("file://")) . '/hooks/popup.ini';
        if (!file_exists($file)) {
        //    echo "$file does not exist";
            return;
        }
        $ar = parse_ini_file($file);
        //print_r($ar);
        if (!isset($ar[$this->log[0]['author']])) {
            // no ip for this author.
            echo "no match for author";
            return;
        }
        $ip = $ar[$this->log[0]['author']];
        $cmd = "/usr/bin/smbclient -M {$this->log[0]['author']} -I  {$ip}";
        //echo $cmd;
        $fh = popen($cmd,'w');
        fwrite($fh, $data);
        // end
        fwrite($fh,chr(04));
        fclose($fh); 
    }
    
    
    
    
    function checkSyntax()
    {
        $ret = '';
        $ar = $this->log;
        foreach($ar[0]['paths'] as $action) {
            if (!in_array($action['action'],array('M','A'))) {
                continue;
            }
            if (!preg_match('#\.php$#', $action['path'])) {
                continue;
            }
            $tmp = ini_get('session.save_path') . '/'.uniqid('tmp_php.').'.php';
            
            $this->writeFile($tmp , 
                    svn_cat($this->repos . $action['path'],$this->rev));
            
            $data = $data = `/usr/bin/php -l $tmp`;
            unlink($tmp);
            if (preg_match('/^No syntax errors/',$data)) {
                continue;
            }
            $ret .= "Error in {$action['path']}\n".$data;
        }
        return strlen($ret) ? $ret : false; 
             
    }
    
    function writeFile($target,$data) 
    {
        $fh = fopen($target,'w');
        fwrite($fh, $data);
        fclose($fh);
    }
    
    function getFileNames()
    {
        $ar = $this->log;
        if (count($ar[0]['paths']) > 1) {
            return "Multiple Files";
        }
        return $ar[0]['paths'][0]['path'];
    }
     
}
ini_set('memory_limit','64M');
$x = new Subversion_EmailCommit;
$x->start($_SERVER['argv']);
