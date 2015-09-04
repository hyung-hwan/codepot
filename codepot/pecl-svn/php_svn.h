/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2008 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,       |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt.                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Authors: Alan Knowles <alan@akbkhome.com>                            |
  |          Wez Furlong <wez@omniti.com>                                |
  |          Luca Furini <lfurini@cs.unibo.it>                           |
  |          Jerome Renard <jerome.renard_at_gmail.com>                  |
  |          Develar <develar_at_gmail.com>                              |
  +----------------------------------------------------------------------+
*/

/* $Id: php_svn.h 336509 2015-04-13 04:45:28Z alan_k $ */

#ifndef PHP_SVN_H
#define PHP_SVN_H

extern zend_module_entry svn_module_entry;
#define phpext_svn_ptr &svn_module_entry

#define PHP_SVN_VERSION "1.0.3-dev"

#ifdef PHP_WIN32
#define PHP_SVN_API __declspec(dllexport)
#else
#define PHP_SVN_API
#endif

#ifdef ZTS
#include "TSRM.h"
#endif

#include "svn_client.h"

#if defined(SVN_DEPTH_INFINITY_OR_FILES)
	/* version 1.5 at least */
#	define PHP_SVN_SUPPORT_DEPTH
#endif

PHP_MINIT_FUNCTION(svn);
PHP_MSHUTDOWN_FUNCTION(svn);
PHP_RINIT_FUNCTION(svn);
PHP_RSHUTDOWN_FUNCTION(svn);
PHP_MINFO_FUNCTION(svn);

PHP_FUNCTION(svn_checkout);
PHP_FUNCTION(svn_cat);
PHP_FUNCTION(svn_ls);
PHP_FUNCTION(svn_log);
PHP_FUNCTION(svn_auth_set_parameter);
PHP_FUNCTION(svn_auth_get_parameter);
PHP_FUNCTION(svn_client_version);
PHP_FUNCTION(svn_config_ensure);
PHP_FUNCTION(svn_diff);
PHP_FUNCTION(svn_cleanup);
PHP_FUNCTION(svn_revert);
PHP_FUNCTION(svn_resolved);
PHP_FUNCTION(svn_lock);
PHP_FUNCTION(svn_unlock);

PHP_FUNCTION(svn_commit);
PHP_FUNCTION(svn_add);
PHP_FUNCTION(svn_status);
PHP_FUNCTION(svn_update);
#if defined(PHP_SVN_SUPPORT_DEPTH)
PHP_FUNCTION(svn_update2);
#endif
PHP_FUNCTION(svn_import);
PHP_FUNCTION(svn_info);
PHP_FUNCTION(svn_export);
PHP_FUNCTION(svn_copy);
PHP_FUNCTION(svn_switch);
PHP_FUNCTION(svn_blame);
PHP_FUNCTION(svn_delete);
PHP_FUNCTION(svn_mkdir);
PHP_FUNCTION(svn_move);
PHP_FUNCTION(svn_proplist);
PHP_FUNCTION(svn_propget);
PHP_FUNCTION(svn_propset);
PHP_FUNCTION(svn_prop_delete);
PHP_FUNCTION(svn_revprop_get);
PHP_FUNCTION(svn_revprop_set);
PHP_FUNCTION(svn_revprop_delete);

PHP_FUNCTION(svn_repos_create);
PHP_FUNCTION(svn_repos_recover);
PHP_FUNCTION(svn_repos_hotcopy);
PHP_FUNCTION(svn_repos_open);
PHP_FUNCTION(svn_repos_fs);
PHP_FUNCTION(svn_repos_fs_begin_txn_for_commit);
PHP_FUNCTION(svn_repos_fs_commit_txn);
PHP_FUNCTION(svn_fs_revision_root);
PHP_FUNCTION(svn_fs_check_path);
PHP_FUNCTION(svn_fs_revision_prop);
PHP_FUNCTION(svn_fs_dir_entries);
PHP_FUNCTION(svn_fs_node_created_rev);
PHP_FUNCTION(svn_fs_youngest_rev);
PHP_FUNCTION(svn_fs_file_contents);
PHP_FUNCTION(svn_fs_file_length);
PHP_FUNCTION(svn_fs_txn_root);
PHP_FUNCTION(svn_fs_make_file);
PHP_FUNCTION(svn_fs_make_dir);
PHP_FUNCTION(svn_fs_apply_text);
PHP_FUNCTION(svn_fs_copy);
PHP_FUNCTION(svn_fs_delete);
PHP_FUNCTION(svn_fs_begin_txn2);
PHP_FUNCTION(svn_fs_is_dir);
PHP_FUNCTION(svn_fs_is_file);
PHP_FUNCTION(svn_fs_node_prop);
PHP_FUNCTION(svn_fs_change_node_prop);
PHP_FUNCTION(svn_fs_contents_changed);
PHP_FUNCTION(svn_fs_props_changed);
PHP_FUNCTION(svn_fs_abort_txn);
PHP_FUNCTION(svn_fs_open_txn);
PHP_FUNCTION(svn_fs_txn_prop);

/* TODO: */


PHP_FUNCTION(svn_merge);
PHP_FUNCTION(svn_url_from_path);
PHP_FUNCTION(svn_uuid_from_url);
PHP_FUNCTION(svn_uuid_from_path);


/** constants **/

#define SVN_REVISION_INITIAL 1
#define SVN_REVISION_HEAD -1
#define SVN_REVISION_BASE -2
#define SVN_REVISION_COMMITTED -3
#define SVN_REVISION_PREV -4
#define SVN_REVISION_UNSPECIFIED -5

#define SVN_NON_RECURSIVE 1 /* --non-recursive */
#define SVN_DISCOVER_CHANGED_PATHS 2 /* --verbose */
#define SVN_OMIT_MESSAGES 4 /* --quiet */
#define SVN_STOP_ON_COPY 8 /* --stop-on-copy */
#define SVN_ALL 16 /* --verbose in svn status */
#define SVN_SHOW_UPDATES 32 /* --show-updates */
#define SVN_NO_IGNORE 64 /* --no-ignore */
#define SVN_IGNORE_EXTERNALS 128 /* --ignore-externals */




ZEND_BEGIN_MODULE_GLOBALS(svn)
	apr_pool_t *pool;
	svn_client_ctx_t *ctx;
ZEND_END_MODULE_GLOBALS(svn)

#ifdef ZTS
#define SVN_G(v) TSRMG(svn_globals_id, zend_svn_globals *, v)
#else
#define SVN_G(v) (svn_globals.v)
#endif

#endif	/* PHP_SVN_H */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
