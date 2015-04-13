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
  |          Scott MacVicar <scottmac@php.net>                           |
  |          Luca Furini <lfurini@cs.unibo.it>                           |
  |          Jerome Renard <jerome.renard_at_gmail.com>                  |
  |          Develar <develar_at_gmail.com>                              |
  +----------------------------------------------------------------------+
*/

/* $Id: svn.c 336509 2015-04-13 04:45:28Z alan_k $ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_svn.h"

#include "apr_version.h"
#include "svn_pools.h"
#include "svn_sorts.h"
#include "svn_config.h"
#include "svn_auth.h"
#include "svn_path.h"
#include "svn_fs.h"
#include "svn_repos.h"
#include "svn_utf.h"
#include "svn_time.h"
#include "svn_props.h"
#include "svn_version.h"

ZEND_DECLARE_MODULE_GLOBALS(svn)

/* custom property for ignoring SSL cert verification errors */
#define PHP_SVN_AUTH_PARAM_IGNORE_SSL_VERIFY_ERRORS "php:svn:auth:ignore-ssl-verify-errors"
#define PHP_SVN_INIT_CLIENT() \
	do { \
		if (init_svn_client(TSRMLS_C)) RETURN_FALSE; \
	} while (0)
 
static void php_svn_get_version(char *buf, int buflen);

/* True global resources - no need for thread safety here */

struct php_svn_repos {
	long rsrc_id;
	apr_pool_t *pool;
	svn_repos_t *repos;
};

struct php_svn_fs {
	struct php_svn_repos *repos;
	svn_fs_t *fs;
};

struct php_svn_fs_root {
	struct php_svn_repos *repos;
	svn_fs_root_t *root;
};

struct php_svn_repos_fs_txn {
	struct php_svn_repos *repos;
	svn_fs_txn_t *txn;
};


struct php_svn_log_receiver_baton {
	zval *result;
	svn_boolean_t omit_messages;
};

/* class entry constants */
static zend_class_entry *ce_Svn;

/* resource constants */
static int le_svn_repos;
static int le_svn_fs;
static int le_svn_fs_root;
static int le_svn_repos_fs_txn;

static ZEND_RSRC_DTOR_FUNC(php_svn_repos_dtor)
{
	struct php_svn_repos *r = rsrc->ptr;
	/* If root pool doesn't exist, then this resource's pool was already destroyed */
	if (SVN_G(pool)) {
		svn_pool_destroy(r->pool);
	}
	efree(r);
}

static ZEND_RSRC_DTOR_FUNC(php_svn_fs_dtor)
{
	struct php_svn_fs *r = rsrc->ptr;
	zend_list_delete(r->repos->rsrc_id);
	efree(r);
}

static ZEND_RSRC_DTOR_FUNC(php_svn_fs_root_dtor)
{
	struct php_svn_fs_root *r = rsrc->ptr;
	zend_list_delete(r->repos->rsrc_id);
	efree(r);
}

static ZEND_RSRC_DTOR_FUNC(php_svn_repos_fs_txn_dtor)
{
	struct php_svn_repos_fs_txn *r = rsrc->ptr;
	zend_list_delete(r->repos->rsrc_id);
	efree(r);
}

#define SVN_STATIC_ME(name) ZEND_FENTRY(name, ZEND_FN(svn_ ## name), NULL, ZEND_ACC_PUBLIC | ZEND_ACC_STATIC)
/** Fixme = this list needs padding out... */
static zend_function_entry svn_methods[] = {
	SVN_STATIC_ME(cat)
	SVN_STATIC_ME(checkout)
	SVN_STATIC_ME(log)
	SVN_STATIC_ME(status)

	{NULL, NULL, NULL}
};


/* {{{ svn_functions[] */
zend_function_entry svn_functions[] = {
	PHP_FE(svn_checkout,		NULL)
	PHP_FE(svn_cat,			NULL)
	PHP_FE(svn_ls,			NULL)
	PHP_FE(svn_log,			NULL)
	PHP_FE(svn_auth_set_parameter,	NULL)
	PHP_FE(svn_auth_get_parameter,	NULL)
	PHP_FE(svn_client_version, NULL)
	PHP_FE(svn_config_ensure, NULL)
	PHP_FE(svn_diff, NULL)
	PHP_FE(svn_cleanup, NULL)
	PHP_FE(svn_revert, NULL)
	PHP_FE(svn_resolved, NULL)
	PHP_FE(svn_commit, NULL)
	PHP_FE(svn_lock, NULL)
	PHP_FE(svn_unlock, NULL)
	PHP_FE(svn_add, NULL)
	PHP_FE(svn_status, NULL)
	PHP_FE(svn_update, NULL)
	PHP_FE(svn_import, NULL)
	PHP_FE(svn_info, NULL)
	PHP_FE(svn_export, NULL)
	PHP_FE(svn_copy, NULL)
	PHP_FE(svn_switch, NULL)
	PHP_FE(svn_blame, NULL)
	PHP_FE(svn_delete, NULL)
	PHP_FE(svn_mkdir, NULL)
	PHP_FE(svn_move, NULL)
	PHP_FE(svn_proplist, NULL)
	PHP_FE(svn_propget, NULL)
	PHP_FE(svn_propset, NULL)
	PHP_FE(svn_prop_delete, NULL)
	PHP_FE(svn_revprop_get, NULL)
	PHP_FE(svn_revprop_set, NULL)
	PHP_FE(svn_revprop_delete, NULL)
	PHP_FE(svn_repos_create, NULL)
	PHP_FE(svn_repos_recover, NULL)
	PHP_FE(svn_repos_hotcopy, NULL)
	PHP_FE(svn_repos_open, NULL)
	PHP_FE(svn_repos_fs, NULL)
	PHP_FE(svn_repos_fs_begin_txn_for_commit, NULL)
	PHP_FE(svn_repos_fs_commit_txn, NULL)
	PHP_FE(svn_fs_revision_root, NULL)
	PHP_FE(svn_fs_check_path, NULL)
	PHP_FE(svn_fs_revision_prop, NULL)
	PHP_FE(svn_fs_dir_entries, NULL)
	PHP_FE(svn_fs_node_created_rev, NULL)
	PHP_FE(svn_fs_youngest_rev, NULL)
	PHP_FE(svn_fs_file_contents, NULL)
	PHP_FE(svn_fs_file_length, NULL)
	PHP_FE(svn_fs_txn_root, NULL)
	PHP_FE(svn_fs_make_file, NULL)
	PHP_FE(svn_fs_make_dir, NULL)
	PHP_FE(svn_fs_apply_text, NULL)
	PHP_FE(svn_fs_copy, NULL)
	PHP_FE(svn_fs_delete, NULL)
	PHP_FE(svn_fs_begin_txn2, NULL)
	PHP_FE(svn_fs_is_dir, NULL)
	PHP_FE(svn_fs_is_file, NULL)
	PHP_FE(svn_fs_node_prop, NULL)
	PHP_FE(svn_fs_change_node_prop, NULL)
	PHP_FE(svn_fs_contents_changed, NULL)
	PHP_FE(svn_fs_props_changed, NULL)
	PHP_FE(svn_fs_abort_txn, NULL)
	PHP_FE(svn_fs_open_txn, NULL)
	PHP_FE(svn_fs_txn_prop, NULL)

	{NULL, NULL, NULL}
};
/* }}} */

/* {{{ svn_module_entry */
zend_module_entry svn_module_entry = {
#if ZEND_MODULE_API_NO >= 20010901
	STANDARD_MODULE_HEADER,
#endif
	"svn",
	svn_functions,
	PHP_MINIT(svn),
	NULL,
	NULL,
	PHP_RSHUTDOWN(svn),
	PHP_MINFO(svn),
#if ZEND_MODULE_API_NO >= 20010901
	PHP_SVN_VERSION,
#endif
	STANDARD_MODULE_PROPERTIES
};
/* }}} */


#ifdef COMPILE_DL_SVN
ZEND_GET_MODULE(svn)
#endif

/* {{{ php_svn_get_revision_kind */
static enum svn_opt_revision_kind php_svn_get_revision_kind(svn_opt_revision_t revision)
{
	switch(revision.value.number) {
 		case svn_opt_revision_unspecified:
 			/* through  */
 		case SVN_REVISION_HEAD:
 			return svn_opt_revision_head;
 		case SVN_REVISION_BASE:
 			return svn_opt_revision_base;
 		case SVN_REVISION_COMMITTED:
 			return svn_opt_revision_committed;
 		case SVN_REVISION_PREV:
 			return svn_opt_revision_previous;
 		default:
 			return svn_opt_revision_number;
	}
}
/* }}} */


#include "ext/standard/php_smart_str.h"
static void php_svn_handle_error(svn_error_t *error TSRMLS_DC)
{
	svn_error_t *itr = error;
	smart_str s = {0,0,0};

	smart_str_appendl(&s, "svn error(s) occured\n", sizeof("svn error(s) occured\n")-1);

	while (itr) {
		char buf[256];

		smart_str_append_long(&s, itr->apr_err);
		smart_str_appendl(&s, " (", 2);

		svn_strerror(itr->apr_err, buf, sizeof(buf));
		smart_str_appendl(&s, buf, strlen(buf));
		smart_str_appendl(&s, ") ", 2);
		if (itr->message) {
			smart_str_appendl(&s, itr->message, strlen(itr->message));
		}

		if (itr->child) {
			smart_str_appendl(&s, "\n", 1);
		}
		itr = itr->child;
	}

	smart_str_appendl(&s, "\n", 1);
	smart_str_0(&s);
	php_error_docref(NULL TSRMLS_CC, E_WARNING, "%s", s.c);
	smart_str_free(&s);
}

static svn_error_t *php_svn_auth_ssl_client_server_trust_prompter(
	svn_auth_cred_ssl_server_trust_t **cred,
	void *baton,
	const char *realm,
	apr_uint32_t failures,
	const svn_auth_ssl_server_cert_info_t *cert_info,
	svn_boolean_t may_save,
	apr_pool_t *pool)
{
	const char *ignore;
	TSRMLS_FETCH();

	ignore = (const char*)svn_auth_get_parameter(SVN_G(ctx)->auth_baton, PHP_SVN_AUTH_PARAM_IGNORE_SSL_VERIFY_ERRORS);
	if (ignore && atoi(ignore)) {
		*cred = apr_palloc(SVN_G(pool), sizeof(**cred));
		(*cred)->may_save = 0;
		(*cred)->accepted_failures = failures;
	}

	return SVN_NO_ERROR;
}

static void php_svn_init_globals(zend_svn_globals *g)
{
	memset(g, 0, sizeof(*g));
}

static svn_error_t *php_svn_get_commit_log(const char **log_msg, const char **tmp_file,
		apr_array_header_t *commit_items, void *baton, apr_pool_t *pool)
{
	*log_msg = (const char*)baton;
	*tmp_file = NULL;
	return SVN_NO_ERROR;
}

static int init_svn_client(TSRMLS_D)
{
	svn_error_t *err;
	svn_auth_provider_object_t *provider;
	svn_auth_baton_t *ab;
	apr_array_header_t *providers;

	if (SVN_G(pool)) return 0;

	SVN_G(pool) = svn_pool_create(NULL);

	if ((err = svn_client_create_context (&SVN_G(ctx), SVN_G(pool)))) {
		php_svn_handle_error(err TSRMLS_CC);
		svn_pool_destroy(SVN_G(pool));
		SVN_G(pool) = NULL;
		return 1;
	}

	if ((err = svn_config_get_config(&SVN_G(ctx)->config, NULL, SVN_G(pool)))) {
		if (err->apr_err == APR_EACCES) {
			/* Should possible consider a notice here */
			svn_error_clear(err);
		} else {
			php_svn_handle_error(err TSRMLS_CC);
			svn_pool_destroy(SVN_G(pool));
			SVN_G(pool) = NULL;
			return 1;
		}
	}

	SVN_G(ctx)->log_msg_func = php_svn_get_commit_log;

	/* The whole list of registered providers */

	providers = apr_array_make (SVN_G(pool), 10, sizeof (svn_auth_provider_object_t *));

	/* The main disk-caching auth providers, for both
	   'username/password' creds and 'username' creds.  */
	svn_client_get_simple_provider (&provider, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;

	svn_client_get_username_provider (&provider, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;

	svn_client_get_ssl_server_trust_prompt_provider (&provider, php_svn_auth_ssl_client_server_trust_prompter, NULL, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;

	/* The server-cert, client-cert, and client-cert-password providers. */
	svn_client_get_ssl_server_trust_file_provider (&provider, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;

	svn_client_get_ssl_client_cert_file_provider (&provider, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;

	svn_client_get_ssl_client_cert_pw_file_provider (&provider, SVN_G(pool));
	APR_ARRAY_PUSH (providers, svn_auth_provider_object_t *) = provider;


	/* skip prompt stuff */
	svn_auth_open (&ab, providers, SVN_G(pool));
	/* turn off prompting */
	svn_auth_set_parameter(ab, SVN_AUTH_PARAM_NON_INTERACTIVE, "");
	SVN_G(ctx)->auth_baton = ab;

	return 0;
}

/* {{{ proto string svn_auth_get_parameter(string key)
	Retrieves authentication parameter at key */
PHP_FUNCTION(svn_auth_get_parameter)
{
	char *key;
	int keylen;
	const char *value;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &key, &keylen)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();

	value = svn_auth_get_parameter(SVN_G(ctx)->auth_baton, key);
	if (value) {
		RETVAL_STRING((char*)value, 1);
	}
}
/* }}} */

/* {{{ proto void svn_auth_set_parameter(string key, string value)
	Sets authentication parameter at key to value */
PHP_FUNCTION(svn_auth_set_parameter)
{
	char *key, *actual_value = NULL;
	zval *value;
	int keylen;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sz", &key, &keylen, &value)) {
		return;
	}
	PHP_SVN_INIT_CLIENT();

	if (strcmp(key, SVN_AUTH_PARAM_DEFAULT_PASSWORD) == 0) {
		svn_auth_set_parameter(SVN_G(ctx)->auth_baton, SVN_AUTH_PARAM_DONT_STORE_PASSWORDS, "");
	}

	if (Z_TYPE_P(value) != IS_NULL) {
		convert_to_string_ex(&value);
		actual_value = Z_STRVAL_P(value);
	}

	svn_auth_set_parameter(SVN_G(ctx)->auth_baton, apr_pstrdup(SVN_G(pool), key), apr_pstrdup(SVN_G(pool), actual_value));

}
/* }}} */

/* {{{ proto bool svn_config_ensure(string config_path)
	Ensure that the specified path looks like a subversion config path.
	This function will create skeleton files if required. */
PHP_FUNCTION(svn_config_ensure)
{
	const char *config_path = NULL;
	const char *utf8_path = NULL;
	int config_path_len;
	apr_pool_t *subpool;
	svn_error_t *err;
	
	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s!", &config_path, &config_path_len)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	if (config_path) {
		err = svn_utf_cstring_to_utf8 (&utf8_path, config_path, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}

		config_path = svn_path_canonicalize(utf8_path, subpool);
	}

	err = svn_config_ensure(config_path, subpool);
	if (err) {
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_import(string path, string url, bool nonrecursive)
	Imports unversioned path into repository at url */
PHP_FUNCTION(svn_import)
{
	svn_client_commit_info_t *commit_info_p = NULL;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	char *url;
	int urllen;
	svn_boolean_t nonrecursive;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssb",
				&path, &pathlen, &url, &urllen, &nonrecursive)) {
		RETURN_FALSE;
	}

	PHP_SVN_INIT_CLIENT();

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_client_import(&commit_info_p, path, url, nonrecursive,
			SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error (err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(svn)
{
	zend_class_entry ce;
	zend_class_entry *ce_SvnWc;
	zend_class_entry *ce_SvnWcSchedule;
	zend_class_entry *ce_SvnNode;
	apr_version_t apv;

	apr_initialize();

	/* Print something useful when old APR is used like that of Apache 2.0.x */
	apr_version(&apv);
	if (apv.major < APR_MAJOR_VERSION) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "libsvn was compiled against a newer version of APR than was loaded");
	}

	ZEND_INIT_MODULE_GLOBALS(svn, php_svn_init_globals, NULL);

	INIT_CLASS_ENTRY(ce, "Svn", svn_methods);
	ce_Svn = zend_register_internal_class(&ce TSRMLS_CC);


	INIT_CLASS_ENTRY(ce, "SvnWc", NULL);
		ce_SvnWc = zend_register_internal_class(&ce TSRMLS_CC);

	INIT_CLASS_ENTRY(ce, "SvnWcSchedule", NULL);
		ce_SvnWcSchedule = zend_register_internal_class(&ce TSRMLS_CC);

	INIT_CLASS_ENTRY(ce, "SvnNode", NULL);
		ce_SvnNode = zend_register_internal_class(&ce TSRMLS_CC);


#define CLASS_CONST_LONG(class_name, const_name, value) \
		zend_declare_class_constant_long(ce_ ## class_name, const_name, \
			sizeof(const_name)-1, (long)value TSRMLS_CC);

	CLASS_CONST_LONG(Svn, "NON_RECURSIVE", SVN_NON_RECURSIVE);
	CLASS_CONST_LONG(Svn, "DISCOVER_CHANGED_PATHS", SVN_DISCOVER_CHANGED_PATHS);
	CLASS_CONST_LONG(Svn, "OMIT_MESSAGES", SVN_OMIT_MESSAGES);
	CLASS_CONST_LONG(Svn, "STOP_ON_COPY", SVN_STOP_ON_COPY);
	CLASS_CONST_LONG(Svn, "ALL", SVN_ALL);
	CLASS_CONST_LONG(Svn, "SHOW_UPDATES", SVN_SHOW_UPDATES);
	CLASS_CONST_LONG(Svn, "NO_IGNORE", SVN_NO_IGNORE);
	CLASS_CONST_LONG(Svn, "IGNORE_EXTERNALS", SVN_IGNORE_EXTERNALS);

	CLASS_CONST_LONG(Svn, "INITIAL", SVN_REVISION_INITIAL);
	CLASS_CONST_LONG(Svn, "HEAD", SVN_REVISION_HEAD);
	CLASS_CONST_LONG(Svn, "BASE", SVN_REVISION_BASE);
	CLASS_CONST_LONG(Svn, "COMMITTED", SVN_REVISION_COMMITTED);
	CLASS_CONST_LONG(Svn, "PREV", SVN_REVISION_PREV);
	CLASS_CONST_LONG(Svn, "UNSPECIFIED", SVN_REVISION_UNSPECIFIED);


	CLASS_CONST_LONG(SvnWc, "NONE", svn_wc_status_none);
	CLASS_CONST_LONG(SvnWc, "UNVERSIONED", svn_wc_status_unversioned);
	CLASS_CONST_LONG(SvnWc, "NORMAL", svn_wc_status_normal);
	CLASS_CONST_LONG(SvnWc, "ADDED", svn_wc_status_added);
	CLASS_CONST_LONG(SvnWc, "MISSING", svn_wc_status_missing);
	CLASS_CONST_LONG(SvnWc, "DELETED", svn_wc_status_deleted);
	CLASS_CONST_LONG(SvnWc, "REPLACED", svn_wc_status_replaced);
	CLASS_CONST_LONG(SvnWc, "MODIFIED", svn_wc_status_modified);
	CLASS_CONST_LONG(SvnWc, "MERGED", svn_wc_status_merged);
	CLASS_CONST_LONG(SvnWc, "CONFLICTED", svn_wc_status_conflicted);
	CLASS_CONST_LONG(SvnWc, "IGNORED", svn_wc_status_ignored);
	CLASS_CONST_LONG(SvnWc, "OBSTRUCTED", svn_wc_status_obstructed);
	CLASS_CONST_LONG(SvnWc, "EXTERNAL", svn_wc_status_external);
	CLASS_CONST_LONG(SvnWc, "INCOMPLETE", svn_wc_status_incomplete);

	CLASS_CONST_LONG(SvnWcSchedule, "NORMAL", svn_wc_schedule_normal);
	CLASS_CONST_LONG(SvnWcSchedule, "ADD", svn_wc_schedule_add);
	CLASS_CONST_LONG(SvnWcSchedule, "DELETE", svn_wc_schedule_delete);
	CLASS_CONST_LONG(SvnWcSchedule, "REPLACE", svn_wc_schedule_replace);

	CLASS_CONST_LONG(SvnNode, "NONE", svn_node_none);
	CLASS_CONST_LONG(SvnNode, "FILE", svn_node_file);
	CLASS_CONST_LONG(SvnNode, "DIR", svn_node_dir);
	CLASS_CONST_LONG(SvnNode, "UNKNOWN", svn_node_unknown);


	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_DEFAULT_USERNAME", SVN_AUTH_PARAM_DEFAULT_USERNAME, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_DEFAULT_PASSWORD", SVN_AUTH_PARAM_DEFAULT_PASSWORD, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_NON_INTERACTIVE", SVN_AUTH_PARAM_NON_INTERACTIVE, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_DONT_STORE_PASSWORDS", SVN_AUTH_PARAM_DONT_STORE_PASSWORDS, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_NO_AUTH_CACHE", SVN_AUTH_PARAM_NO_AUTH_CACHE, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_SSL_SERVER_FAILURES", SVN_AUTH_PARAM_SSL_SERVER_FAILURES, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_SSL_SERVER_CERT_INFO", SVN_AUTH_PARAM_SSL_SERVER_CERT_INFO, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_CONFIG", SVN_AUTH_PARAM_CONFIG, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_SERVER_GROUP", SVN_AUTH_PARAM_SERVER_GROUP, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_AUTH_PARAM_CONFIG_DIR", SVN_AUTH_PARAM_CONFIG_DIR, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("PHP_SVN_AUTH_PARAM_IGNORE_SSL_VERIFY_ERRORS", PHP_SVN_AUTH_PARAM_IGNORE_SSL_VERIFY_ERRORS, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_FS_CONFIG_FS_TYPE", SVN_FS_CONFIG_FS_TYPE, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_FS_TYPE_BDB", SVN_FS_TYPE_BDB, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_FS_TYPE_FSFS", SVN_FS_TYPE_FSFS, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_PROP_REVISION_DATE", SVN_PROP_REVISION_DATE, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_PROP_REVISION_ORIG_DATE", SVN_PROP_REVISION_ORIG_DATE, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_PROP_REVISION_AUTHOR", SVN_PROP_REVISION_AUTHOR, CONST_CS|CONST_PERSISTENT);
	REGISTER_STRING_CONSTANT("SVN_PROP_REVISION_LOG", SVN_PROP_REVISION_LOG, CONST_CS|CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("SVN_REVISION_INITIAL", SVN_REVISION_INITIAL, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_REVISION_HEAD", SVN_REVISION_HEAD, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_REVISION_BASE", SVN_REVISION_BASE, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_REVISION_COMMITTED", SVN_REVISION_COMMITTED, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_REVISION_PREV", SVN_REVISION_PREV, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_REVISION_UNSPECIFIED", SVN_REVISION_UNSPECIFIED, CONST_CS|CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("SVN_NON_RECURSIVE", SVN_NON_RECURSIVE, CONST_CS|CONST_PERSISTENT);   /* --non-recursive */
	REGISTER_LONG_CONSTANT("SVN_DISCOVER_CHANGED_PATHS", SVN_DISCOVER_CHANGED_PATHS, CONST_CS|CONST_PERSISTENT);    /* --verbose */
	REGISTER_LONG_CONSTANT("SVN_OMIT_MESSAGES", SVN_OMIT_MESSAGES, CONST_CS|CONST_PERSISTENT);    /* --quiet */
	REGISTER_LONG_CONSTANT("SVN_STOP_ON_COPY", SVN_STOP_ON_COPY, CONST_CS|CONST_PERSISTENT);    /* --stop-on-copy */
	REGISTER_LONG_CONSTANT("SVN_ALL", SVN_ALL, CONST_CS|CONST_PERSISTENT);    /* --verbose in svn status */
	REGISTER_LONG_CONSTANT("SVN_SHOW_UPDATES", SVN_SHOW_UPDATES, CONST_CS|CONST_PERSISTENT);   /* --show-updates */
	REGISTER_LONG_CONSTANT("SVN_NO_IGNORE", SVN_NO_IGNORE, CONST_CS|CONST_PERSISTENT);   /* --no-ignore */

	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_NONE", svn_wc_status_none, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_UNVERSIONED", svn_wc_status_unversioned, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_NORMAL", svn_wc_status_normal, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_ADDED", svn_wc_status_added, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_MISSING", svn_wc_status_missing, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_DELETED", svn_wc_status_deleted, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_REPLACED", svn_wc_status_replaced, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_MODIFIED", svn_wc_status_modified, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_MERGED", svn_wc_status_merged, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_CONFLICTED", svn_wc_status_conflicted, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_IGNORED", svn_wc_status_ignored, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_OBSTRUCTED", svn_wc_status_obstructed, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_EXTERNAL", svn_wc_status_external, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_STATUS_INCOMPLETE", svn_wc_status_incomplete, CONST_CS|CONST_PERSISTENT);

	REGISTER_LONG_CONSTANT("SVN_NODE_NONE", svn_node_none, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_NODE_FILE", svn_node_file, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_NODE_DIR", svn_node_dir, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_NODE_UNKNOWN", svn_node_unknown, CONST_CS|CONST_PERSISTENT);


	REGISTER_LONG_CONSTANT("SVN_WC_SCHEDULE_NORMAL", svn_wc_schedule_normal, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_SCHEDULE_ADD", svn_wc_schedule_add, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_SCHEDULE_DELETE", svn_wc_schedule_delete, CONST_CS|CONST_PERSISTENT);
	REGISTER_LONG_CONSTANT("SVN_WC_SCHEDULE_REPLACE", svn_wc_schedule_replace, CONST_CS|CONST_PERSISTENT);

	le_svn_repos = zend_register_list_destructors_ex(php_svn_repos_dtor,
			NULL, "svn-repos", module_number);

	le_svn_fs = zend_register_list_destructors_ex(php_svn_fs_dtor,
			NULL, "svn-fs", module_number);

	le_svn_fs_root = zend_register_list_destructors_ex(php_svn_fs_root_dtor,
			NULL, "svn-fs-root", module_number);

	le_svn_repos_fs_txn = zend_register_list_destructors_ex(php_svn_repos_fs_txn_dtor,
			NULL, "svn-repos-fs-txn", module_number);

	return SUCCESS;
}
/* }}} */

/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION(svn)
{
	if (SVN_G(pool)) {
		svn_pool_destroy(SVN_G(pool));
		SVN_G(pool) = NULL;
	}
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(svn)
{
	char vstr[128];

	php_info_print_table_start();
	php_info_print_table_header(2, "svn support", "enabled");

	php_svn_get_version(vstr, sizeof(vstr));

	php_info_print_table_row(2, "svn client version", vstr);
	php_info_print_table_row(2, "svn extension version", PHP_SVN_VERSION);
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

/* {{{ proto bool svn_checkout(string repository_url, string target_path [, int revision = SVN_REVISION_HEAD [, int flags]])
	Checks out a particular revision from a repository into target_path. */
PHP_FUNCTION(svn_checkout)
{
	char *repos_url = NULL, *target_path = NULL;
	const char *utf8_repos_url = NULL, *utf8_target_path = NULL;
	const char *can_repos_url = NULL, *can_target_path = NULL;
	int repos_url_len, target_path_len;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 };
	long flags = 0;
	apr_pool_t *subpool;
	const char *true_path;

	revision.value.number = svn_opt_revision_unspecified;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|ll",
			&repos_url, &repos_url_len, &target_path, &target_path_len, &revision.value.number, &flags) == FAILURE) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_repos_url, repos_url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_target_path, target_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	can_repos_url= svn_path_canonicalize(utf8_repos_url, subpool);
	can_target_path = svn_path_canonicalize(utf8_target_path, subpool);

	revision.kind = php_svn_get_revision_kind(revision);

	err = svn_opt_parse_path(&peg_revision, &true_path, can_repos_url, subpool);
	if (err) {
		php_svn_handle_error (err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	err = svn_client_checkout2 (NULL,
			true_path,
			can_target_path,
			&peg_revision,
			&revision,
			!(flags & SVN_NON_RECURSIVE),
			flags & SVN_IGNORE_EXTERNALS,
			SVN_G(ctx),
			subpool);

	if (err) {
		php_svn_handle_error (err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto string svn_cat(string repository_url [, int revision_no])
	Returns the contents of a file in a working copy or repository, optionally at revision_no. */
PHP_FUNCTION(svn_cat)
{
	const char *url = NULL;
	const char *utf8_url = NULL;
	int url_len;
	apr_size_t size;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 } ;
	svn_stream_t *out = NULL;
	svn_stringbuf_t *buf = NULL;
	char *retdata =NULL;
	apr_pool_t *subpool;
	const char *true_path;

	revision.value.number = svn_opt_revision_unspecified;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|l",
		&url, &url_len, &revision.value.number) == FAILURE) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	RETVAL_FALSE;

	revision.kind = php_svn_get_revision_kind(revision);

	buf = svn_stringbuf_create("", subpool);
	if (!buf) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "failed to allocate stringbuf");
		goto cleanup;
	}

	out = svn_stream_from_stringbuf(buf, subpool);
	if (!out) {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "failed to create svn stream");
		goto cleanup;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	url = svn_path_canonicalize(utf8_url, subpool);

	err = svn_opt_parse_path(&peg_revision, &true_path, url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	err = svn_client_cat2(out, true_path, &peg_revision, &revision, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	retdata = emalloc(buf->len + 1);
	size = buf->len;
	err = svn_stream_read(out, retdata, &size);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	retdata[size] = '\0';
	RETVAL_STRINGL(retdata, size, 0);
	retdata = NULL;

cleanup:
	svn_pool_destroy(subpool);
	if (retdata) efree(retdata);
}
/* }}} */


/* {{{ proto array svn_ls(string repository_url [, int revision [, bool recurse [, bool peg]]])
	Returns a list of a directory in a working copy or repository, optionally at revision_no. */
PHP_FUNCTION(svn_ls)
{
	const char *repos_url = NULL;
	const char *utf8_repos_url = NULL;
	int repos_url_len;
	zend_bool recurse = 0, peg = 0;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 };
	apr_hash_t *dirents;
	apr_array_header_t *array;
	int i;
	apr_pool_t *subpool;
	svn_opt_revision_t peg_revision;
	const char *true_path;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|lbb",
			&repos_url, &repos_url_len, &revision.value.number, &recurse, &peg) == FAILURE) {
		return;
	}
	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_repos_url, repos_url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	repos_url = svn_path_canonicalize(utf8_repos_url, subpool);

	revision.kind = php_svn_get_revision_kind(revision); 

	err = svn_opt_parse_path(&peg_revision, &true_path, repos_url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	err = svn_client_ls2 (&dirents,
		true_path,
		&peg_revision,
		&revision,
		recurse,
		SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	array = svn_sort__hash (dirents, svn_sort_compare_items_as_paths, subpool);
	array_init(return_value);

	for (i = 0; i < array->nelts; ++i)
	{
		const char *utf8_entryname;
		svn_dirent_t *dirent;
		svn_sort__item_t *item;
		apr_time_t now = apr_time_now();
		apr_time_exp_t exp_time;
		apr_status_t apr_err;
		apr_size_t size;
		char timestr[20];
		const char   *utf8_timestr;
		zval 	*row;

		item = &APR_ARRAY_IDX (array, i, svn_sort__item_t);
		utf8_entryname = item->key;
		dirent = apr_hash_get (dirents, utf8_entryname, item->klen);

		/* svn_time_to_human_cstring gives us something *way* too long
		to use for this, so we have to roll our own.  We include
		the year if the entry's time is not within half a year. */
		apr_time_exp_lt (&exp_time, dirent->time);
		if (apr_time_sec(now - dirent->time) < (365 * 86400 / 2)
			&& apr_time_sec(dirent->time - now) < (365 * 86400 / 2))
		{
			apr_err = apr_strftime (timestr, &size, sizeof (timestr),
				      "%b %d %H:%M", &exp_time);
		} else {
			apr_err = apr_strftime (timestr, &size, sizeof (timestr),
				      "%b %d %Y", &exp_time);
		}

		/* if that failed, just zero out the string and print nothing */
		if (apr_err)
			timestr[0] = '\0';

		/* we need it in UTF-8. */
		err = svn_utf_cstring_to_utf8 (&utf8_timestr, timestr, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}

		MAKE_STD_ZVAL(row);
		array_init(row);
		add_assoc_long(row,   "created_rev", 	(long) dirent->created_rev);
		add_assoc_string(row, "last_author", 	dirent->last_author ? (char *) dirent->last_author : " ? ", 1);
		add_assoc_long(row,   "size", 		dirent->size);
		add_assoc_string(row, "time", 		timestr,1);
		add_assoc_long(row,   "time_t", 	apr_time_sec(dirent->time));
		/* this doesnt have a matching struct name */
		add_assoc_string(row, "name", 		(char *) utf8_entryname,1);
		/* should this be a integer or something? - not very clear though.*/
		add_assoc_string(row, "type", 		(dirent->kind == svn_node_dir) ? "dir" : "file", 1);

		add_assoc_zval(return_value, (char *)utf8_entryname, row);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

static svn_error_t *
php_svn_log_receiver (void *ibaton,
				apr_hash_t *changed_paths,
				svn_revnum_t rev,
				const char *author,
				const char *date,
				const char *msg,
				apr_pool_t *pool)
{
	struct php_svn_log_receiver_baton *baton = (struct php_svn_log_receiver_baton*) ibaton;
	zval  *row, *paths;
	apr_array_header_t *sorted_paths;
	int i;
	TSRMLS_FETCH();

	if (rev == 0) {
		return SVN_NO_ERROR;
	}

	MAKE_STD_ZVAL(row);
	array_init(row);
	add_assoc_long(row, "rev", (long) rev);

	if (author) {
		add_assoc_string(row, "author", (char *) author, 1);
	}
	if (!baton->omit_messages && msg) {
		add_assoc_string(row, "msg", (char *) msg, 1);
	}
	if (date) {
		add_assoc_string(row, "date", (char *) date, 1);
	}

	if (changed_paths) {


		MAKE_STD_ZVAL(paths);
		array_init(paths);

		sorted_paths = svn_sort__hash(changed_paths, svn_sort_compare_items_as_paths, pool);

		for (i = 0; i < sorted_paths->nelts; i++)
		{
			svn_sort__item_t *item;
			svn_log_changed_path_t *log_item;
			zval *zpaths;
			const char *path;

			MAKE_STD_ZVAL(zpaths);
			array_init(zpaths);
			item = &(APR_ARRAY_IDX (sorted_paths, i, svn_sort__item_t));
			path = item->key;
			log_item = apr_hash_get (changed_paths, item->key, item->klen);

			add_assoc_stringl(zpaths, "action", &(log_item->action), 1,1);
			add_assoc_string(zpaths, "path", (char *) item->key, 1);

			if (log_item->copyfrom_path
					&& SVN_IS_VALID_REVNUM (log_item->copyfrom_rev)) {
				add_assoc_string(zpaths, "copyfrom", (char *) log_item->copyfrom_path, 1);
				add_assoc_long(zpaths, "rev", (long) log_item->copyfrom_rev);
			} else {

			}

			add_next_index_zval(paths,zpaths);
		}
		add_assoc_zval(row,"paths",paths);
	}

	add_next_index_zval(baton->result, row);
	return SVN_NO_ERROR;
}

/* {{{ proto array svn_log(string repository_url [, int start_revision =  SVN_REVISION_HEAD [, int end_revision = SVN_REVISION_INITIAL [, int limit [, int flags ]]]])
	Returns the commit log messages on the working copy or repository object specified. */
PHP_FUNCTION(svn_log)
{
	const char *url = NULL, *utf8_url = NULL;
	int url_len;

	svn_error_t *err;
	svn_opt_revision_t 	start_revision = { 0 }, end_revision = { 0 };

	apr_array_header_t *targets;

	apr_pool_t *subpool;
	long limit = 0;
	long flags = SVN_DISCOVER_CHANGED_PATHS | SVN_STOP_ON_COPY;
	struct php_svn_log_receiver_baton baton;

	svn_opt_revision_t peg_revision;
	const char *true_path;

	start_revision.value.number = svn_opt_revision_unspecified;
	end_revision.value.number = svn_opt_revision_unspecified;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|llll",
			&url, &url_len,
			&start_revision.value.number, &end_revision.value.number,
			&limit,  &flags) == FAILURE) {
		return;
	}
	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	if ((ZEND_NUM_ARGS() > 2) && (end_revision.value.number == svn_opt_revision_unspecified)) {
		end_revision.value.number = SVN_REVISION_INITIAL;
	}

	start_revision.kind = php_svn_get_revision_kind(start_revision);

	if (start_revision.value.number == svn_opt_revision_unspecified) {
		end_revision.kind = svn_opt_revision_number;
	} else if (end_revision.value.number == svn_opt_revision_unspecified) {
		end_revision = start_revision;
	} else {
 		end_revision.kind = php_svn_get_revision_kind(end_revision);
 	}

	url = svn_path_canonicalize(utf8_url, subpool);

	err = svn_opt_parse_path(&peg_revision, &true_path, url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	targets = apr_array_make (subpool, 1, sizeof(char *));

	APR_ARRAY_PUSH(targets, const char *) = true_path;
	array_init(return_value);
	baton.result = (zval *)return_value;
	baton.omit_messages = flags & SVN_OMIT_MESSAGES;

	err = svn_client_log3(
		targets,
		&peg_revision,
		&start_revision,
		&end_revision,
		limit,
		flags & SVN_DISCOVER_CHANGED_PATHS,
		flags & SVN_STOP_ON_COPY,
		php_svn_log_receiver,
		(void *) &baton,
		SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

static size_t php_apr_file_write(php_stream *stream, const char *buf, size_t count TSRMLS_DC)
{
	apr_file_t *thefile = (apr_file_t*)stream->abstract;
	apr_size_t nbytes = (apr_size_t)count;

	apr_file_write(thefile, buf, &nbytes);

	return (size_t)nbytes;
}

static size_t php_apr_file_read(php_stream *stream, char *buf, size_t count TSRMLS_DC)
{
	apr_file_t *thefile = (apr_file_t*)stream->abstract;
	apr_size_t nbytes = (apr_size_t)count;

	apr_file_read(thefile, buf, &nbytes);

	if (nbytes == 0) stream->eof = 1;

	return (size_t)nbytes;
}

static int php_apr_file_close(php_stream *stream, int close_handle TSRMLS_DC)
{
	if (close_handle) {
		apr_file_close((apr_file_t*)stream->abstract);
	}
	return 0;
}

static int php_apr_file_flush(php_stream *stream TSRMLS_DC)
{
	apr_file_flush((apr_file_t*)stream->abstract);
	return 0;
}

static int php_apr_file_seek(php_stream *stream, off_t offset, int whence, off_t *newoffset TSRMLS_DC)
{
	apr_file_t *thefile = (apr_file_t*)stream->abstract;
	apr_off_t off = (apr_off_t)offset;

	/* NB: apr_seek_where_t is defined using the standard SEEK_XXX whence values */
	apr_file_seek(thefile, whence, &off);

	*newoffset = (off_t)off;
	return 0;
}

static php_stream_ops php_apr_stream_ops = {
	php_apr_file_write,
	php_apr_file_read,
	php_apr_file_close,
	php_apr_file_flush,
	"svn diff stream",
	php_apr_file_seek,
	NULL, /* cast */
	NULL, /* stat */
	NULL /* set_option */
};

/* {{{ proto array svn_diff(string path1, int revision1, string path2, int revision2)
	Produce diff output which describes the delta between path1/revision1 and path2/revision2.
	Returns an array consisting of two streams: the first is the diff output and the second contains error stream output */
PHP_FUNCTION(svn_diff)
{
	const char *tmp_dir;
	char outname[256], errname[256];
	apr_pool_t *subpool;
	apr_file_t *outfile = NULL, *errfile = NULL;
	svn_error_t *err;
	char *path1, *path2;
	const char *utf8_path1 = NULL,*utf8_path2 = NULL;
	const char *can_path1 = NULL,*can_path2 = NULL;
	int path1len, path2len;
	long rev1 = -1, rev2 = -1;
	apr_array_header_t diff_options = { 0, 0, 0, 0, 0};
	svn_opt_revision_t revision1, revision2;
	zend_bool ignore_content_type = 0;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sl!sl!",
			&path1, &path1len, &rev1,
			&path2, &path2len, &rev2)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}
	RETVAL_FALSE;

	if (rev1 <= 0) {
		revision1.kind = svn_opt_revision_head;
	} else {
		revision1.kind = svn_opt_revision_number;
		revision1.value.number = rev1;
	}
	if (rev2 <= 0) {
		revision2.kind = svn_opt_revision_head;
	} else {
		revision2.kind = svn_opt_revision_number;
		revision2.value.number = rev2;
	}

 	apr_temp_dir_get(&tmp_dir, subpool);
	sprintf(outname, "%s/phpsvnXXXXXX", tmp_dir);
	sprintf(errname, "%s/phpsvnXXXXXX", tmp_dir);

	/* use global pool, so stream lives after this function call */
	apr_file_mktemp(&outfile, outname,
			APR_CREATE|APR_READ|APR_WRITE|APR_EXCL|APR_DELONCLOSE,
			SVN_G(pool));

	/* use global pool, so stream lives after this function call */
	apr_file_mktemp(&errfile, errname,
			APR_CREATE|APR_READ|APR_WRITE|APR_EXCL|APR_DELONCLOSE,
			SVN_G(pool));

	err = svn_utf_cstring_to_utf8 (&utf8_path1, path1, subpool);
	if (err) {
		apr_file_close(errfile);
		apr_file_close(outfile);
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path2, path2, subpool);
	if (err) {
		apr_file_close(errfile);
		apr_file_close(outfile);
		php_svn_handle_error(err TSRMLS_CC);
		goto cleanup;
	}

	can_path1= svn_path_canonicalize(utf8_path1, subpool);
	can_path2= svn_path_canonicalize(utf8_path2, subpool);

	err = svn_client_diff3(&diff_options,
			can_path1, &revision1,
			can_path2, &revision2,
			1, /* recurse */
			0, /* ignore_ancestry */
			0, /* no diff deleted */
			ignore_content_type,
			APR_LOCALE_CHARSET, /* header encoding, for 1.4+ use SVN_APR_LOCALE_CHARSET */
			outfile, errfile,
			SVN_G(ctx), subpool);

	if (err) {
		apr_file_close(errfile);
		apr_file_close(outfile);
		php_svn_handle_error(err TSRMLS_CC);
	} else {
		zval *t;
		php_stream *stm = NULL;
		apr_off_t off = (apr_off_t)0;

		array_init(return_value);

		/* set the file pointer to the beginning of the file */
		apr_file_seek(outfile, APR_SET, &off);
		apr_file_seek(errfile, APR_SET, &off);

		/* 'bless' the apr files into streams and return those */
		stm = php_stream_alloc(&php_apr_stream_ops, outfile, 0, "rw");
		MAKE_STD_ZVAL(t);
		php_stream_to_zval(stm, t);
		add_next_index_zval(return_value, t);

		stm = php_stream_alloc(&php_apr_stream_ops, errfile, 0, "rw");
		MAKE_STD_ZVAL(t);
		php_stream_to_zval(stm, t);
		add_next_index_zval(return_value, t);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_cleanup(string workingdir)
	Recursively cleanup a working copy directory, finishing any incomplete operations, removing lockfiles, etc. */
PHP_FUNCTION(svn_cleanup)
{
	const char *workingdir = NULL;
	const char *utf8_workingdir = NULL;
	int workingdir_len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &workingdir, &workingdir_len)) {
		RETURN_FALSE;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_workingdir, workingdir, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	workingdir = svn_path_canonicalize(utf8_workingdir, subpool);

	err = svn_client_cleanup(workingdir, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_revert(string path [, bool recursive = false])
	Revert any local changes to the path in a working copy. */
PHP_FUNCTION(svn_revert)
{
	const char *path = NULL, *utf8_path = NULL;
	long pathlen;
	zend_bool recursive = 0;
	svn_error_t *err;
	apr_array_header_t *targets;
	apr_pool_t *subpool;

	if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s|b", &path, &pathlen, &recursive) ) {
		RETURN_FALSE;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	targets = apr_array_make (subpool, 1, sizeof(char *));

	APR_ARRAY_PUSH(targets, const char *) = svn_path_canonicalize(utf8_path, subpool);

	err = svn_client_revert(
			targets,
			recursive,
			SVN_G(ctx),
			subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_resolved(string path [, bool recursive = false])
	Marks a conflicted path as resolved. */
PHP_FUNCTION(svn_resolved)
{
	const char *path = NULL, *utf8_path = NULL;
	long pathlen;
	zend_bool recursive = 0;
	svn_error_t *err;
	apr_pool_t *subpool;

	if( zend_parse_parameters( ZEND_NUM_ARGS() TSRMLS_CC, "s|b", &path, &pathlen, &recursive) ) {
		RETURN_FALSE;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}
	RETVAL_FALSE;

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_client_resolved(
			path,
			recursive,
			SVN_G(ctx),
			subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

static int replicate_hash(void *pDest TSRMLS_DC, int num_args, va_list args, zend_hash_key *key)
{
	zval **val = (zval **)pDest;
	apr_hash_t *hash = va_arg(args, apr_hash_t*);

	if (key->nKeyLength && Z_TYPE_PP(val) == IS_STRING) {
		/* apr doesn't want the NUL terminator in its keys */
		apr_hash_set(hash, key->arKey, key->nKeyLength-1, Z_STRVAL_PP(val));
	}

	va_end(args);

	return ZEND_HASH_APPLY_KEEP;
}

static apr_hash_t *replicate_zend_hash_to_apr_hash(zval *arr, apr_pool_t *pool TSRMLS_DC)
{
	apr_hash_t *hash;

	if (!arr) return NULL;

	hash = apr_hash_make(pool);

	zend_hash_apply_with_arguments(Z_ARRVAL_P(arr) TSRMLS_CC, replicate_hash, 1, hash);

	return hash;
}

/* {{{ proto string svn_fs_revision_prop(resource fs, int revnum, string propname)
	Fetches the value of property propname at revision revnum in the filesystem. */
PHP_FUNCTION(svn_fs_revision_prop)
{
	zval *zfs;
	long revnum;
	struct php_svn_fs *fs;
	svn_error_t *err;
	svn_string_t *str;
	char *propname;
	int propnamelen;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rls",
				&zfs, &revnum, &propname, &propnamelen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fs, struct php_svn_fs *, &zfs, -1, "svn-fs", le_svn_fs);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_fs_revision_prop(&str, fs->fs, revnum, propname, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (!str) {
		/* the property is not found. return an empty string */
		RETVAL_STRINGL("", 0, 1);
	} else {
		RETVAL_STRINGL((char*)str->data, str->len, 1);
	}

	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto int svn_fs_youngest_rev(resource fs)
	Returns the number of the youngest revision in the filesystem. */
PHP_FUNCTION(svn_fs_youngest_rev)
{
	zval *zfs;
	struct php_svn_fs *fs;
	svn_error_t *err;
	svn_revnum_t revnum;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r",
				&zfs)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fs, struct php_svn_fs *, &zfs, -1, "svn-fs", le_svn_fs);

	err = svn_fs_youngest_rev(&revnum, fs->fs, fs->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	}

	RETURN_LONG(revnum);
}
/* }}} */


/* {{{ proto resource svn_fs_revision_root(resource fs, int revnum)
	Get a handle on a specific revision of the repository root. */
PHP_FUNCTION(svn_fs_revision_root)
{
	zval *zfs;
	long revnum;
	struct php_svn_fs *fs;
	svn_fs_root_t *root;
	svn_error_t *err;
	struct php_svn_fs_root *resource;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rl",
				&zfs, &revnum)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fs, struct php_svn_fs *, &zfs, -1, "svn-fs", le_svn_fs);

	err = svn_fs_revision_root(&root, fs->fs, revnum, fs->repos->pool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	}

	resource = emalloc(sizeof(*resource));
	resource->root = root;
	resource->repos = fs->repos;
	zend_list_addref(fs->repos->rsrc_id);
	ZEND_REGISTER_RESOURCE(return_value, resource, le_svn_fs_root);
}
/* }}} */

static size_t php_svn_stream_write(php_stream *stream, const char *buf, size_t count TSRMLS_DC)
{
	svn_stream_t *thefile = (svn_stream_t*)stream->abstract;
	apr_size_t nbytes = (apr_size_t)count;

	svn_stream_write(thefile, buf, &nbytes);

	return (size_t)nbytes;
}

static size_t php_svn_stream_read(php_stream *stream, char *buf, size_t count TSRMLS_DC)
{
	svn_stream_t *thefile = (svn_stream_t*)stream->abstract;
	apr_size_t nbytes = (apr_size_t)count;

	svn_stream_read(thefile, buf, &nbytes);

	if (nbytes == 0) stream->eof = 1;

	return (size_t)nbytes;
}

static int php_svn_stream_close(php_stream *stream, int close_handle TSRMLS_DC)
{
	if (close_handle) {
		svn_stream_close((svn_stream_t*)stream->abstract);
	}
	return 0;
}

static int php_svn_stream_flush(php_stream *stream TSRMLS_DC)
{
	return 0;
}

static php_stream_ops php_svn_stream_ops = {
	php_svn_stream_write,
	php_svn_stream_read,
	php_svn_stream_close,
	php_svn_stream_flush,
	"svn content stream",
	NULL, /* seek */
	NULL, /* cast */
	NULL, /* stat */
	NULL /* set_option */
};

/* {{{ proto resource svn_fs_file_contents(resource fsroot, string path)
	Returns a stream to access the contents of the file at path. */
PHP_FUNCTION(svn_fs_file_contents)
{
	zval *zfsroot;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	svn_error_t *err;
	svn_stream_t *svnstm;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfsroot, &path, &pathlen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_file_contents(&svnstm, fsroot->root, path, SVN_G(pool));

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		php_stream *stm;
		stm = php_stream_alloc(&php_svn_stream_ops, svnstm, 0, "r");
		php_stream_to_zval(stm, return_value);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto int svn_fs_file_length(resource fsroot, string path)
	Tthe length of the file path in fsroot, in bytes. */
PHP_FUNCTION(svn_fs_file_length)
{
	zval *zfsroot;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	svn_filesize_t len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfsroot, &path, &pathlen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_file_length(&len, fsroot->root, path, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		/* TODO: 64 bit */
		RETVAL_LONG(len);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto int svn_fs_node_prop(resource fsroot, string path, string propname)
	Returns the value of property propname for a path. */
PHP_FUNCTION(svn_fs_node_prop)
{
	zval *zfsroot;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	char *propname;
	int pathlen, propnamelen;
	svn_error_t *err;
	apr_pool_t *subpool;
	svn_string_t *val;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rss",
				&zfsroot, &path, &pathlen, &propname, &propnamelen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_node_prop(&val, fsroot->root, path, propname, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		if (val != NULL && val->data != NULL) {
			RETVAL_STRINGL((char *)val->data, val->len, 1);
		} else {
			RETVAL_EMPTY_STRING();
		}
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */


/* {{{ proto int svn_fs_node_created_rev(resource fsroot, string path)
	Returns the revision in which path under fsroot was created. */
PHP_FUNCTION(svn_fs_node_created_rev)
{
	zval *zfsroot;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	svn_error_t *err;
	apr_pool_t *subpool;
	svn_revnum_t rev;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfsroot, &path, &pathlen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_node_created_rev(&rev, fsroot->root, path, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_LONG(rev);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto array svn_fs_dir_entries(resource fsroot, string path)
	Lists the entries at path, the key is the name and the value is the node type. */
PHP_FUNCTION(svn_fs_dir_entries)
{
	zval *zfsroot;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	svn_error_t *err;
	apr_pool_t *subpool;
	apr_hash_t *hash;
	apr_hash_index_t *hi;
	union {
		void *vptr;
		svn_fs_dirent_t *ent;
	} pun;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfsroot, &path, &pathlen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_dir_entries(&hash, fsroot->root, path, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		array_init(return_value);

		for (hi = apr_hash_first(subpool, hash); hi; hi = apr_hash_next(hi)) {
			apr_hash_this(hi, NULL, NULL, &pun.vptr);
			add_assoc_long(return_value, (char*)pun.ent->name, pun.ent->kind);
		}
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto int svn_fs_check_path(resource fsroot, string path)
	Determines what kind of node is present at path in a given repository fsroot. */
PHP_FUNCTION(svn_fs_check_path)
{
	zval *zfsroot;
	svn_node_kind_t kind;
	struct php_svn_fs_root *fsroot;
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfsroot, &path, &pathlen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fsroot, struct php_svn_fs_root*, &zfsroot, -1, "svn-fs-root", le_svn_fs_root);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_fs_check_path(&kind, fsroot->root, path, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_LONG(kind);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto resource svn_repos_fs(resource repos)
	Returns the filesystem resource associated with the repository resource repos.  */
PHP_FUNCTION(svn_repos_fs)
{
	struct php_svn_repos *repos = NULL;
	struct php_svn_fs *resource = NULL;
	zval *zrepos;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r",
				&zrepos)) {
		return;
	}

	ZEND_FETCH_RESOURCE(repos, struct php_svn_repos *, &zrepos, -1, "svn-repos", le_svn_repos);

	resource = emalloc(sizeof(*resource));
	resource->repos = repos;
	zend_list_addref(repos->rsrc_id);
	resource->fs = svn_repos_fs(repos->repos);

	ZEND_REGISTER_RESOURCE(return_value, resource, le_svn_fs);
}
/* }}} */

/* {{{ proto resource svn_repos_open(string path)
	Acquires a shared lock on the repository at path. */
PHP_FUNCTION(svn_repos_open)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_repos_t *repos = NULL;
	struct php_svn_repos *resource = NULL;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s",
				&path, &pathlen)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		svn_pool_destroy(subpool);
		RETURN_FALSE;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_repos_open(&repos, path, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
	}

	if (repos) {
		resource = emalloc(sizeof(*resource));
		resource->pool = subpool;
		resource->repos = repos;
		ZEND_REGISTER_RESOURCE(return_value, resource, le_svn_repos);
	} else {
		svn_pool_destroy(subpool);
		RETURN_FALSE;
	}
}
/* }}} */

static svn_error_t *info_func (void *baton, const char *path, const svn_info_t *info, apr_pool_t *pool) {
	zval *return_value = (zval*)baton;
	zval *entry;
	TSRMLS_FETCH();

	MAKE_STD_ZVAL(entry);
	array_init(entry);

	add_assoc_string(entry, "path", (char*)path, 1);
	if (info) {
		if (info->URL) {
			add_assoc_string(entry, "url", (char *)info->URL, 1);
		}

		add_assoc_long(entry, "revision", info->rev);
		add_assoc_long(entry, "kind", info->kind);

		if (info->repos_root_URL) {
			add_assoc_string(entry, "repos", (char *)info->repos_root_URL, 1);
		}

		add_assoc_long(entry, "last_changed_rev", info->last_changed_rev);
		add_assoc_string(entry, "last_changed_date", (char *) svn_time_to_cstring(info->last_changed_date, pool), 1);

		if (info->last_changed_author) {
			add_assoc_string(entry, "last_changed_author", (char *)info->last_changed_author, 1);
		}

		if (info->lock) {
			add_assoc_bool(entry, "locked", 1);
		}

		if (info->has_wc_info) {
			add_assoc_bool(entry, "is_working_copy", 1);
		}
	}

	add_next_index_zval(return_value, entry);

	return NULL;
}

/* {{{ proto array svn_info(string path [, bool recurse = false [, int revision = -1]])
	Returns subversion information about a working copy. */
PHP_FUNCTION(svn_info)
{
	const char *path = NULL, *utf8_path = NULL;
	int pathlen;
	long revnum = -1;
	apr_pool_t *subpool;
	zend_bool recurse = 1;
	svn_error_t *err;
	svn_opt_revision_t peg_revision, revision;
	const char *true_path;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|bl",
				&path, &pathlen, &recurse, &revnum)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	revision.value.number = revnum;
	revision.kind = !svn_path_is_url(path) && revnum == SVN_REVISION_UNSPECIFIED ? 
		svn_opt_revision_unspecified : php_svn_get_revision_kind(revision); 

	if (svn_path_is_url(path))
	{
		err = svn_opt_parse_path(&peg_revision, &true_path, path, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}
	}
	else {
		peg_revision.kind = svn_opt_revision_unspecified;
		true_path = path;
	}

	array_init(return_value);
	err = svn_client_info(true_path, &peg_revision, &revision, info_func, return_value, recurse, SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto resource svn_export(string frompath, string topath [, bool working_copy = true  [, long revision = -1 ])
	Export the contents of either a working copy or repository into a 'clean' directory.
	If working_copy is true it will export uncommitted files from a working copy.
    To export revisions, you must set working copy to false - the default is to export HEAD. */
PHP_FUNCTION(svn_export)
{
	const char *from = NULL, *to = NULL;
	const char *utf8_from_path = NULL, *utf8_to_path = NULL;
	int fromlen, tolen;
	long revision_no = -1;
	apr_pool_t *subpool;
	zend_bool working_copy = 1;
	svn_error_t *err;
	svn_opt_revision_t revision, peg_revision;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|bl",
				&from, &fromlen, &to, &tolen, &working_copy, &revision_no )) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_from_path, from, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_to_path, to, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	from = svn_path_canonicalize(utf8_from_path, subpool);
	to = svn_path_canonicalize(utf8_to_path, subpool);

	if (working_copy) {
		revision.kind = svn_opt_revision_working;
	} else {
		revision.value.number = revision_no;
		revision.kind = php_svn_get_revision_kind(revision); 
	}

	peg_revision.kind = svn_opt_revision_unspecified;

	err = svn_client_export3(NULL, from, to, &peg_revision, &revision, TRUE, FALSE, TRUE, NULL, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto resource svn_switch(string path, string url [, bool working_copy = true])
	Switch an existing working copy to another development URL within the same repository. */
PHP_FUNCTION(svn_switch)
{
	const char *url = NULL, *path = NULL;
	const char *utf8_url = NULL, *utf8_path = NULL;
	int urllen, pathlen;
	apr_pool_t *subpool;
	zend_bool working_copy = 1;
	svn_error_t *err;
	svn_opt_revision_t revision;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|b",
					&path, &pathlen, &url, &urllen, &working_copy)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);
	url = svn_path_canonicalize(utf8_url, subpool);

	if (working_copy) {
		revision.kind = svn_opt_revision_working;
	} else {
		revision.kind = svn_opt_revision_head;
	}

	err = svn_client_switch(NULL, path, url, &revision, TRUE, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto resource svn_copy(string log, string src_path, string destination_path [, bool working_copy = true [, int revision = -1]])
	Copies src path to destination path in a working copy or respository. */
PHP_FUNCTION(svn_copy)
{
	const char *src_path = NULL, *dst_path = NULL;
	const char *utf8_src_path = NULL, *utf8_dst_path = NULL;
	char *log;
	int src_pathlen, dst_pathlen, loglen;
	long revnum = -1;
	apr_pool_t *subpool;
	zend_bool working_copy = 1;
	svn_error_t *err;
	svn_commit_info_t *info = NULL;
	svn_opt_revision_t revision;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss|bl",
					&log, &loglen, &src_path, &src_pathlen, &dst_path, &dst_pathlen,
					&working_copy, &revnum)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_src_path, src_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_dst_path, dst_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	src_path = svn_path_canonicalize(utf8_src_path, subpool);
	dst_path = svn_path_canonicalize(utf8_dst_path, subpool);

	revision.value.number = revnum;

	if (working_copy) {
		revision.kind = svn_opt_revision_working;
	} else {
		revision.kind = php_svn_get_revision_kind(revision);
	}

	SVN_G(ctx)->log_msg_baton = log;

	err = svn_client_copy2(&info, (const char*)src_path, &revision, (const char*)dst_path, SVN_G(ctx), subpool);
	SVN_G(ctx)->log_msg_baton = NULL;

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (info) {
		array_init(return_value);
		add_next_index_long(return_value, info->revision);
		if (info->date) {
			add_next_index_string(return_value, (char*)info->date, 1);
		} else {
			add_next_index_null(return_value);
		}

		if (info->author) {
			add_next_index_string(return_value, (char*)info->author, 1);
		} else {
			add_next_index_null(return_value);
		}
	} else {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "commit didn't return any info");
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */


static svn_error_t *
php_svn_blame_message_receiver (void *baton,
				apr_int64_t line_no,
				svn_revnum_t rev,
				const char *author,
				const char *date,
				const char *line,
				apr_pool_t *pool)
{
	zval *return_value = (zval *)baton, *row;

	TSRMLS_FETCH();

	if (rev == 0) {
		return SVN_NO_ERROR;
	}

	MAKE_STD_ZVAL(row);
	array_init(row);


	add_assoc_long(row, "rev", (long) rev);
	add_assoc_long(row, "line_no", line_no + 1);
	add_assoc_string(row, "line", (char *) line, 1);

	if (author) {
		add_assoc_string(row, "author", (char *) author, 1);
	}
	if (date) {
		add_assoc_string(row, "date", (char *) date, 1);
	}


	add_next_index_zval(return_value, row);
	return SVN_NO_ERROR;
}

/* {{{ proto array svn_blame(string repository_url [, int revision_no])
	Returns the revision number, date and author for each line of a file in a working copy or repository.*/
PHP_FUNCTION(svn_blame)
{
	const char *repos_url = NULL;
	const char *utf8_repos_url = NULL;
	int repos_url_len;
	int revision = -1;
	svn_error_t *err;
	svn_opt_revision_t
			start_revision = { 0 },
			end_revision = { 0 },
			peg_revision;
	apr_pool_t *subpool;
	const char *true_path;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|l", &repos_url, &repos_url_len, &revision) == FAILURE) {
		RETURN_FALSE;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_repos_url, repos_url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	repos_url = svn_path_canonicalize(utf8_repos_url, subpool);
	
	start_revision.kind =  svn_opt_revision_number;
	start_revision.value.number = 0;
		
	if (revision == -1) {
		end_revision.kind   =  svn_opt_revision_head;
	} else {
		end_revision.kind   =  svn_opt_revision_number;
		end_revision.value.number = revision;
	}

	err = svn_opt_parse_path(&peg_revision, &true_path, repos_url, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	array_init(return_value);

	err = svn_client_blame2(
			true_path,
			&peg_revision,
			&start_revision,
			&end_revision,
			php_svn_blame_message_receiver,
			(void *) return_value,
			SVN_G(ctx),
			subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto mixed svn_delete(string path [, bool force = true [, string message ]])
	Delete items from a working copy or repository. */
PHP_FUNCTION(svn_delete)
{
	const char *path = NULL, *utf8_path = NULL, *logmsg = NULL;
	int pathlen, logmsg_len;
	apr_pool_t *subpool;
	zend_bool force = 0;
	svn_error_t *err;
	svn_commit_info_t *info = NULL;
	apr_array_header_t *targets;
         
	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|bs",
					&path, &pathlen, &force, &logmsg, &logmsg_len)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	targets = apr_array_make (subpool, 1, sizeof(char *));
	APR_ARRAY_PUSH(targets, const char *) = svn_path_canonicalize(utf8_path, subpool);
	
	SVN_G(ctx)->log_msg_baton = logmsg; 
	err = svn_client_delete2(&info, targets, force, SVN_G(ctx), subpool);
	SVN_G(ctx)->log_msg_baton = NULL; 

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (info) {
		array_init(return_value);
		add_next_index_long(return_value, info->revision);
		if (info->date) {
			add_next_index_string(return_value, (char*)info->date, 1);
		} else {
			add_next_index_null(return_value);
		}

		if (info->author) {
			add_next_index_string(return_value, (char*)info->author, 1);
		} else {
			add_next_index_null(return_value);
		}
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto mixed svn_mkdir(string path [, string log_message ])
	Creates a directory in a working copy or repository. - log_message is optional for local mkdir */
PHP_FUNCTION(svn_mkdir)
{
	const char *path = NULL, *utf8_path = NULL;
	char *log_message = NULL;
	int pathlen, loglen = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_commit_info_t *info = NULL;
	apr_array_header_t *targets;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|s",
					&path, &pathlen, &log_message, &loglen)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	SVN_G(ctx)->log_msg_baton = NULL;

	if (loglen) {
		SVN_G(ctx)->log_msg_baton = log_message;
	}

	targets = apr_array_make (subpool, 1, sizeof(char *));

	APR_ARRAY_PUSH(targets, const char *) = svn_path_canonicalize(utf8_path, subpool);

	err = svn_client_mkdir2(&info, targets, SVN_G(ctx), subpool);

	SVN_G(ctx)->log_msg_baton = NULL;

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	/* no error message set, info did not get returned, and no log was set - hence it's a local mkdir */
	if (!loglen && !info) {
		RETVAL_TRUE;
		goto cleanup;
	}
	
	if (!info) {
		RETVAL_FALSE;
		goto cleanup;
	}

	array_init(return_value);
	add_next_index_long(return_value, info->revision);
	if (info->date) {
		add_next_index_string(return_value, (char*)info->date, 1);
	} else {
		add_next_index_null(return_value);
	}

	if (info->author) {
		add_next_index_string(return_value, (char*)info->author, 1);
	} else {
		add_next_index_null(return_value);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto mixed svn_move(string src_path, string dst_path [, bool force])
	Moves src_path to dst_path in a working copy or repository. */
PHP_FUNCTION(svn_move)
{
	const char *src_path = NULL, *utf8_src_path = NULL;
	const char *dst_path = NULL, *utf8_dst_path = NULL;
	int src_pathlen, dst_pathlen;
	zend_bool force = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_commit_info_t *info = NULL;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|b",
					&src_path, &src_pathlen, &dst_path, &dst_pathlen, &force)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_src_path, src_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_dst_path, dst_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	src_path = svn_path_canonicalize(utf8_src_path, subpool);
	dst_path = svn_path_canonicalize(utf8_dst_path, subpool);

	err = svn_client_move3(&info, src_path, dst_path, force, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (info) {
		array_init(return_value);
		add_next_index_long(return_value, info->revision);
		if (info->date) {
			add_next_index_string(return_value, (char*)info->date, 1);
		} else {
			add_next_index_null(return_value);
		}

		if (info->author) {
			add_next_index_string(return_value, (char*)info->author, 1);
		} else {
			add_next_index_null(return_value);
		}
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto mixed svn_proplist(string path [, bool recurse, [int revision]])
	Returns the properties of a path, the path can be a working copy or repository. */
PHP_FUNCTION(svn_proplist)
{
	const char *path = NULL, *utf8_path = NULL;
	int pathlen;
	zend_bool recurse = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	apr_array_header_t *props;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 };
	int i = 0;
	const char *true_path;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|bl", 
					&path, &pathlen, &recurse, &revision.value.number)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));

	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 

	path = svn_path_canonicalize(utf8_path, subpool);
	
	revision.kind = php_svn_get_revision_kind(revision); 

	err = svn_opt_parse_path(&peg_revision, &true_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}
	
	err = svn_client_proplist2(&props, true_path, &peg_revision, &revision, recurse, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		array_init(return_value);

		for (i = 0; i < props->nelts; ++i) {
			svn_client_proplist_item_t *item
				= ((svn_client_proplist_item_t **)props->elts)[i];
			zval *row;
			apr_hash_index_t *hi;

			MAKE_STD_ZVAL(row);
			array_init(row);
			for (hi = apr_hash_first(subpool, item->prop_hash); hi; hi = apr_hash_next(hi)) {
				const void *key;
				void *val;
				const char *pname;
				svn_string_t *propval;

				apr_hash_this(hi, &key, NULL, &val);
				pname = key;
				propval = val;

				add_assoc_stringl(row, (char *)pname, (char *)propval->data, propval->len, 1);
			}
			add_assoc_zval(return_value, (char *)svn_path_local_style(item->node_name->data, subpool), row);
		}
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto mixed svn_propget(string path, string property_name [, bool recurse [, int revision]])
	Returns an array of paths with a propery of property_name from a working copy or repository. */
PHP_FUNCTION(svn_propget)
{
	const char *path = NULL, *utf8_path = NULL;
	const char *propname = NULL;
	int pathlen, propnamelen;
	zend_bool recurse = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	apr_hash_t *props;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 };
	apr_hash_index_t *hi;
	const char *true_path;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|bl", 
			&path, &pathlen, &propname, &propnamelen, &recurse, &revision.value.number)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 

	path = svn_path_canonicalize(utf8_path, subpool);
	
	revision.kind = php_svn_get_revision_kind(revision); 

	err = svn_opt_parse_path(&peg_revision, &true_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}
 
	err = svn_client_propget2(&props, propname, true_path, &peg_revision, &revision, recurse, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {

		array_init(return_value);
		for (hi = apr_hash_first(subpool, props); hi; hi = apr_hash_next(hi)) {
			const void *key;
			void *val;
			const char *pname;
			svn_string_t *propval;
			zval *row;

			MAKE_STD_ZVAL(row);
			array_init(row);
			apr_hash_this(hi, &key, NULL, &val);
			pname = key;
			propval = val;

			add_assoc_stringl(row, (char *)propname, (char *)propval->data, propval->len, 1);
			add_assoc_zval(return_value, (char *)svn_path_local_style(pname, subpool), row);
		}
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto mixed svn_propset(string path, string property_name, string property_value [, bool recurse [, bool skip_checks [, int revision]]])
	Returns TRUE on success, FALSE on failure. */
PHP_FUNCTION(svn_propset)
{
	const char *path = NULL, *utf8_path = NULL;
	const char *propname = NULL, *propval = NULL;
	int pathlen, propnamelen, propvallen;
	zend_bool recurse = 0, skip_checks = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 };
	apr_hash_index_t *hi;
	const char *true_path;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sss|bbl", 
			&path, &pathlen, &propname, &propnamelen, &propval, &propvallen, &recurse, &skip_checks, &revision.value.number)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 

	path = svn_path_canonicalize(utf8_path, subpool);
	
	revision.kind = php_svn_get_revision_kind(revision); 

	err = svn_opt_parse_path(&peg_revision, &true_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}
 
	err = svn_client_propset2(propname, svn_string_ncreate(propval, propvallen, subpool), true_path, recurse, skip_checks, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto mixed svn_prop_delete(string path, string property_name, [, bool recurse [, bool skip_checks [, int revision]]])
	Returns TRUE on success, FALSE on failure. */
PHP_FUNCTION(svn_prop_delete)
{
	const char *path = NULL, *utf8_path = NULL;
	const char *propname = NULL;
	int pathlen, propnamelen;
	zend_bool recurse = 0, skip_checks = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 }, peg_revision = { 0 };
	apr_hash_index_t *hi;
	const char *true_path;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ss|bbl", 
			&path, &pathlen, &propname, &propnamelen, &recurse, &skip_checks, &revision.value.number)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 

	path = svn_path_canonicalize(utf8_path, subpool);
	
	revision.kind = php_svn_get_revision_kind(revision); 

	err = svn_opt_parse_path(&peg_revision, &true_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}
 
	err = svn_client_propset2(propname, NULL, true_path, recurse, skip_checks, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */


/* {{{ proto mixed svn_revprop_get(string url, int revision, string property_name)
 *  Returns a revision property value */
PHP_FUNCTION(svn_revprop_get)
{
	const char *url = NULL, *utf8_url = NULL;
	const char *propname = NULL, * utf8_propname = NULL;
	int urllen, propnamelen;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 };
	svn_revnum_t result_rev;
	svn_string_t* pval = NULL;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sls", 
			&url, &urllen, &revision.value.number, &propname, &propnamelen)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 
	err = svn_utf_cstring_to_utf8 (&utf8_propname, propname, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}

	url = svn_path_canonicalize(utf8_url, subpool);

	revision.kind = php_svn_get_revision_kind(revision);

	err = svn_client_revprop_get (
		utf8_propname, &pval,
		url, &revision, &result_rev, SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else if (!pval) {
		RETVAL_STRINGL("", 0, 1);
	} else {
		RETVAL_STRINGL((char*)pval->data, pval->len, 1);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto mixed svn_revprop_set(string url, int revision, string property_name, string property_value, [, bool force])
 *  Changes a revision property and returns the affected revision number */
PHP_FUNCTION(svn_revprop_set)
{
	const char *url = NULL, *utf8_url = NULL;
	const char *propname = NULL, *propval = NULL, * utf8_propname = NULL;
	int urllen, propnamelen, propvallen;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 };
	svn_string_t* pval = NULL;
	svn_revnum_t result_rev;
	zend_bool force = 0;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "slss|b", 
			&url, &urllen, &revision.value.number, &propname, &propnamelen, &propval, &propvallen, &force)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 
	err = svn_utf_cstring_to_utf8 (&utf8_propname, propname, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}

	url = svn_path_canonicalize(utf8_url, subpool);

	revision.kind = php_svn_get_revision_kind(revision);

	err = svn_client_revprop_set (
		utf8_propname, svn_string_ncreate(propval, propvallen, subpool), 
		url, &revision, &result_rev, force, SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else {
		RETVAL_LONG(result_rev);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto mixed svn_revprop_delete(string url, int revision, string property_name)
 *  Deletes a revision property */
PHP_FUNCTION(svn_revprop_delete)
{
	const char *url = NULL, *utf8_url = NULL;
	const char *propname = NULL, * utf8_propname = NULL;
	int urllen, propnamelen;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_opt_revision_t revision = { 0 };
	svn_string_t* pval = NULL;
	svn_revnum_t result_rev;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sls", 
			&url, &urllen, &revision.value.number, &propname, &propnamelen)) { 
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_url, url, subpool); 
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 
	err = svn_utf_cstring_to_utf8 (&utf8_propname, propname, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	}

	url = svn_path_canonicalize(utf8_url, subpool);

	revision.kind = php_svn_get_revision_kind(revision);

	err = svn_client_revprop_set (
		utf8_propname, NULL,
		url, &revision, &result_rev, FALSE, SVN_G(ctx), subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	} else {
		RETVAL_LONG(result_rev);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto resource svn_repos_create(string path [, array config [, array fsconfig]])
	Create a new Subversion repository at path. */
PHP_FUNCTION(svn_repos_create)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	zval *config = NULL;
	zval *fsconfig = NULL;
	apr_hash_t *config_hash = NULL;
	apr_hash_t *fsconfig_hash = NULL;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_repos_t *repos = NULL;
	struct php_svn_repos *resource = NULL;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|a!a!",
				&path, &pathlen, &config, &fsconfig)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {	
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		svn_pool_destroy(subpool);
		RETURN_FALSE;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	config_hash = replicate_zend_hash_to_apr_hash(config, subpool TSRMLS_CC);
	fsconfig_hash = replicate_zend_hash_to_apr_hash(fsconfig, subpool TSRMLS_CC);

	err = svn_repos_create(&repos, path, NULL, NULL, config_hash, fsconfig_hash, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
	}

	if (repos) {
		resource = emalloc(sizeof(*resource));
		resource->pool = subpool;
		resource->repos = repos;
		ZEND_REGISTER_RESOURCE(return_value, resource, le_svn_repos);
	} else {
		svn_pool_destroy(subpool);
		RETURN_FALSE;
	}
}
/* }}} */

/* {{{ proto bool svn_repos_recover(string path)
	Run database recovery procedures on the repository at path, returning the database to a consistent state. */
PHP_FUNCTION(svn_repos_recover)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	apr_pool_t *subpool;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s",
				&path, &pathlen)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 
	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_repos_recover2(path, 0, NULL, NULL, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_repos_hotcopy(string src_path, string dst_path, bool cleanlogs)
	Make a hot copy of the Subversion repository found at src_path to dst_path. */
PHP_FUNCTION(svn_repos_hotcopy)
{
	const char *src_path = NULL, *dst_path = NULL;
	const char *utf8_src_path = NULL, *utf8_dst_path = NULL;
	int src_path_len, dst_path_len;
	zend_bool cleanlogs;
	apr_pool_t *subpool;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ssb",
				&src_path, &src_path_len, &dst_path, &dst_path_len, &cleanlogs)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_src_path, src_path, subpool);
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 
	err = svn_utf_cstring_to_utf8 (&utf8_dst_path, dst_path, subpool);
	if (err) { 
		php_svn_handle_error(err TSRMLS_CC); 
		RETVAL_FALSE; 
		goto cleanup; 
	} 

	src_path = svn_path_canonicalize(utf8_src_path, subpool);
	dst_path = svn_path_canonicalize(utf8_dst_path, subpool);

	err = svn_repos_hotcopy(src_path, dst_path, cleanlogs, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

static int replicate_array(void *pDest TSRMLS_DC, int num_args, va_list args, zend_hash_key *key)
{
	zval **val = (zval **)pDest;
	apr_pool_t *pool = (apr_pool_t*)va_arg(args, apr_pool_t*);
	apr_array_header_t *arr = (apr_array_header_t*)va_arg(args, apr_array_header_t*);

	if (Z_TYPE_PP(val) == IS_STRING) {
		APR_ARRAY_PUSH(arr, const char*) = apr_pstrdup(pool, Z_STRVAL_PP(val));
	}

	va_end(args);

	return ZEND_HASH_APPLY_KEEP;
}


static apr_array_header_t *replicate_zend_hash_to_apr_array(zval *arr, apr_pool_t *pool TSRMLS_DC)
{
	apr_array_header_t *apr_arr = apr_array_make(pool, zend_hash_num_elements(Z_ARRVAL_P(arr)), sizeof(const char*));

	if (!apr_arr) return NULL;

	zend_hash_apply_with_arguments(Z_ARRVAL_P(arr) TSRMLS_CC, replicate_array, 2, pool, apr_arr);

	return apr_arr;
}

/* {{{ proto array svn_commit(string log, mixed targets [, bool recursive])
	Commit files or directories from the local working copy into the repository */
PHP_FUNCTION(svn_commit)
{
	char *log = NULL;
	int loglen, pathlen;
	const char *path = NULL;
	const char *utf8_path = NULL;
	zend_bool recursive = 1;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_commit_info_t *info = NULL;
	zval *targets = NULL;
	apr_array_header_t *targets_array;

	if (FAILURE == zend_parse_parameters_ex(ZEND_PARSE_PARAMS_QUIET, ZEND_NUM_ARGS() TSRMLS_CC, "ss|b",
				&log, &loglen, &path, &pathlen, &recursive)) {
		if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa|b",
					&log, &loglen, &targets, &recursive)) {
			return;
		}
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	SVN_G(ctx)->log_msg_baton = log;

	if (path) {
		err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}
		path = svn_path_canonicalize(utf8_path, subpool);

		targets_array = apr_array_make (subpool, 1, sizeof(char *));
		APR_ARRAY_PUSH(targets_array, const char *) = path;
	} else {
		/* TODO: need to canonicalize the array */
		targets_array = replicate_zend_hash_to_apr_array(targets, subpool TSRMLS_CC);
	}

	err = svn_client_commit3(&info, targets_array, recursive, 1, SVN_G(ctx), subpool);
	SVN_G(ctx)->log_msg_baton = NULL;

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (info) {
		array_init(return_value);
		add_next_index_long(return_value, info->revision);
		if (info->date) {
			add_next_index_string(return_value, (char*)info->date, 1);
		} else {
			add_next_index_null(return_value);
		}

		if (info->author) {
			add_next_index_string(return_value, (char*)info->author, 1);
		} else {
			add_next_index_null(return_value);
		}
	} else {
		php_error_docref(NULL TSRMLS_CC, E_WARNING, "commit didn't return any info");
		RETVAL_FALSE;
	}

cleanup:

	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_lock(string comment, mixed targets [, bool steal_lock])
	Lock targets in the repository */
PHP_FUNCTION(svn_lock)
{
	char *comment = NULL;
	int comment_len, pathlen;
	const char *path = NULL;
	const char *utf8_path = NULL;
	zend_bool steal_lock = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	zval *targets = NULL;
	apr_array_header_t *targets_array;

	if (FAILURE == zend_parse_parameters_ex(ZEND_PARSE_PARAMS_QUIET, ZEND_NUM_ARGS() TSRMLS_CC, "ss|b",
				&comment, &comment_len, &path, &pathlen, &steal_lock)) {
		if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "sa|b",
					&comment, &comment_len, &targets, &steal_lock)) {
			return;
		}
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	if (path) {
		err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}
		path = svn_path_canonicalize(utf8_path, subpool);

		targets_array = apr_array_make (subpool, 1, sizeof(char *));
		APR_ARRAY_PUSH(targets_array, const char *) = path;
	} else {
		/* TODO: need to canonicalize the array */
		targets_array = replicate_zend_hash_to_apr_array(targets, subpool TSRMLS_CC);
	}

	err = svn_client_lock(targets_array, comment, steal_lock, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_unlock(mixed targets [, bool break_lock])
	Lock targets in the repository */
PHP_FUNCTION(svn_unlock)
{
	int pathlen;
	const char *path = NULL;
	const char *utf8_path = NULL;
	zend_bool break_lock = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	zval *targets = NULL;
	apr_array_header_t *targets_array;

	if (FAILURE == zend_parse_parameters_ex(ZEND_PARSE_PARAMS_QUIET, ZEND_NUM_ARGS() TSRMLS_CC, "s|b",
				&path, &pathlen, &break_lock)) {
		if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "a|b",
					&targets, &break_lock)) {
			return;
		}
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	if (path) {
		err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
		if (err) {
			php_svn_handle_error(err TSRMLS_CC);
			RETVAL_FALSE;
			goto cleanup;
		}
		path = svn_path_canonicalize(utf8_path, subpool);

		targets_array = apr_array_make (subpool, 1, sizeof(char *));
		APR_ARRAY_PUSH(targets_array, const char *) = path;
	} else {
		/* TODO: need to canonicalize the array */
		targets_array = replicate_zend_hash_to_apr_array(targets, subpool TSRMLS_CC);
	}

	err = svn_client_unlock(targets_array, break_lock, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto bool svn_add(string path [, bool recursive [, bool force]])
	Schedule the addition of a file or path to a working directory */
PHP_FUNCTION(svn_add)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	zend_bool recurse = 1, force = 0;
	apr_pool_t *subpool;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|bb",
				&path, &pathlen, &recurse, &force)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	err = svn_client_add2((const char*)path, recurse, force, SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

static void php_svn_status_receiver(void *baton, const char *path, svn_wc_status2_t *status)
{
	zval *return_value = (zval*)baton;
	zval *entry;
	TSRMLS_FETCH();

	MAKE_STD_ZVAL(entry);
	array_init(entry);

	add_assoc_string(entry, "path", (char*)path, 1);
	if (status) {
		add_assoc_long(entry, "text_status", status->text_status);
		add_assoc_long(entry, "repos_text_status", status->repos_text_status);
		add_assoc_long(entry, "prop_status", status->prop_status);
		add_assoc_long(entry, "repos_prop_status", status->repos_prop_status);
		add_assoc_bool(entry, "locked", status->locked);
		add_assoc_bool(entry, "copied", status->copied);
		add_assoc_bool(entry, "switched", status->switched);

		if (status->entry) {
			if (status->entry->name) {
				add_assoc_string(entry, "name", (char*)status->entry->name, 1);
			}
			if (status->entry->url) {
				add_assoc_string(entry, "url", (char*)status->entry->url, 1);
			}
			if (status->entry->repos) {
				add_assoc_string(entry, "repos", (char*)status->entry->repos, 1);
			}

			add_assoc_long(entry, "revision", status->entry->revision);
			add_assoc_long(entry, "kind", status->entry->kind);
			add_assoc_long(entry, "schedule", status->entry->schedule);
			if (status->entry->deleted) add_assoc_bool(entry, "deleted", status->entry->deleted);
			if (status->entry->absent) add_assoc_bool(entry, "absent", status->entry->absent);
			if (status->entry->incomplete) add_assoc_bool(entry, "incomplete", status->entry->incomplete);

			if (status->entry->copyfrom_url) {
				add_assoc_string(entry, "copyfrom_url", (char*)status->entry->copyfrom_url, 1);
				add_assoc_long(entry, "copyfrom_rev", status->entry->copyfrom_rev);
			}

			if (status->entry->cmt_author) {
				add_assoc_long(entry, "cmt_date", apr_time_sec(status->entry->cmt_date));
				add_assoc_long(entry, "cmt_rev", status->entry->cmt_rev);
				add_assoc_string(entry, "cmt_author", (char*)status->entry->cmt_author, 1);
			}
			if (status->entry->prop_time) {
				add_assoc_long(entry, "prop_time", apr_time_sec(status->entry->prop_time));
			}

			if (status->entry->text_time) {
				add_assoc_long(entry, "text_time", apr_time_sec(status->entry->text_time));
			}
		}
	}

	add_next_index_zval(return_value, entry);
}

/* {{{ proto array svn_status(string path [, int flags]])
	Returns the status of a working copy directory or a single file */
PHP_FUNCTION(svn_status)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int path_len;
	long flags = 0;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_revnum_t result_revision;
	svn_opt_revision_t revision;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|l",
				&path, &path_len, &flags)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	array_init(return_value);
	revision.kind = svn_opt_revision_head;

	err = svn_client_status2(
		&result_revision,
		path,
		&revision,
		php_svn_status_receiver,
		(void*)return_value,
		!(flags & SVN_NON_RECURSIVE),
		flags & SVN_ALL,
		flags & SVN_SHOW_UPDATES,
		flags & SVN_NO_IGNORE,
		flags & SVN_IGNORE_EXTERNALS,
		SVN_G(ctx),
		subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */

/* {{{ proto int svn_update(string path [, int revno [, bool recurse]])
	Updates a working copy at path to revno */
PHP_FUNCTION(svn_update)
{
	const char *path = NULL;
	const char *utf8_path = NULL;
	int pathlen;
	zend_bool recurse = 1;
	apr_pool_t *subpool;
	svn_error_t *err;
	svn_revnum_t result_rev;
	svn_opt_revision_t rev;
	long revno = -1;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s|lb",
				&path, &pathlen, &revno, &recurse)) {
		return;
	}

	PHP_SVN_INIT_CLIENT();
	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err)
	{
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	rev.value.number = revno;
	rev.kind = php_svn_get_revision_kind (rev);
	 
	err = svn_client_update(&result_rev, path, &rev, recurse,
			SVN_G(ctx), subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_LONG(result_rev);
	}

cleanup:
	svn_pool_destroy(subpool);

}
/* }}} */


static void php_svn_get_version(char *buf, int buflen)
{
	const svn_version_t *vers;
	vers = svn_client_version();

	if (strlen(vers->tag))
		snprintf(buf, buflen, "%d.%d.%d#%s", vers->major, vers->minor, vers->patch, vers->tag);
	else
		snprintf(buf, buflen, "%d.%d.%d", vers->major, vers->minor, vers->patch);
}

/* {{{ proto string svn_client_version()
	Returns the version of the SVN client libraries */
PHP_FUNCTION(svn_client_version)
{
	char vers[128];

	if (ZEND_NUM_ARGS()) {
		WRONG_PARAM_COUNT;
	}

	php_svn_get_version(vers, sizeof(vers));
	RETURN_STRING(vers, 1);
}
/* }}} */

/* {{{ proto resource svn_repos_fs_begin_txn_for_commit(resource repos, long rev, string author, string log_msg)
	create a new transaction */
PHP_FUNCTION(svn_repos_fs_begin_txn_for_commit)
{
	svn_fs_txn_t *txn_p = NULL;
	struct php_svn_repos_fs_txn *new_txn = NULL;
	zval *zrepos;
	struct php_svn_repos *repos = NULL;
	svn_revnum_t rev;
	char *author, *log_msg;
	int author_len, log_msg_len;
	apr_pool_t *subpool;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rlss",
				&zrepos, &rev, &author, &author_len, &log_msg, &log_msg_len)) {
		return;
	}

	ZEND_FETCH_RESOURCE(repos, struct php_svn_repos *, &zrepos, -1, "svn-repos", le_svn_repos);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_repos_fs_begin_txn_for_commit(&txn_p, repos->repos, rev, author, log_msg, subpool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
	}

	if (txn_p) {
		new_txn = emalloc(sizeof(*new_txn));
		new_txn->repos = repos;
		zend_list_addref(repos->rsrc_id);
		new_txn->txn = txn_p;

		ZEND_REGISTER_RESOURCE(return_value, new_txn, le_svn_repos_fs_txn);
	} else {
		svn_pool_destroy(subpool);
		RETURN_FALSE;
	}
}
/* }}} */

/* {{{ proto int svn_repos_fs_commit_txn(resource txn)
	Commits a transaction and returns the new revision */
PHP_FUNCTION(svn_repos_fs_commit_txn)
{
	zval *ztxn;
	struct php_svn_repos_fs_txn *txn;
	const char *conflicts;
	svn_revnum_t new_rev;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r",
				&ztxn)) {
		RETURN_FALSE;
	}

	ZEND_FETCH_RESOURCE(txn, struct php_svn_repos_fs_txn *, &ztxn, -1, "svn-repos-fs-txn", le_svn_repos_fs_txn);

	err = svn_repos_fs_commit_txn(&conflicts, txn->repos->repos, &new_rev, txn->txn, txn->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	}

	RETURN_LONG(new_rev);
}
/* }}} */

/* {{{ proto resource svn_fs_txn_root(resource txn)
	Creates and returns a transaction root */
PHP_FUNCTION(svn_fs_txn_root)
{
	svn_fs_root_t *root_p = NULL;
	struct php_svn_fs_root *new_root = NULL;
	zval *ztxn;
	struct php_svn_repos_fs_txn *txn = NULL;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r",
				&ztxn)) {
		return;
	}

	ZEND_FETCH_RESOURCE(txn, struct php_svn_repos_fs_txn *, &ztxn, -1, "svn-fs-repos-txn", le_svn_repos_fs_txn);

	err = svn_fs_txn_root(&root_p, txn->txn, txn->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	}

	if (root_p) {
		new_root = emalloc(sizeof(*new_root));
		new_root->repos = txn->repos;
		zend_list_addref(txn->repos->rsrc_id);
		new_root->root = root_p;

		ZEND_REGISTER_RESOURCE(return_value, new_root, le_svn_fs_root);
	} else {
		RETURN_FALSE;
	}
}
/* }}} */

/* {{{ proto bool svn_fs_make_file(resource root, string path)
	Create a new file named path in root, returns true on success, false otherwise */
PHP_FUNCTION(svn_fs_make_file)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_make_file(root->root, path, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_make_dir(resource root, string path)
	Create a new directory named path in root, returns true on success, false otherwise */
PHP_FUNCTION(svn_fs_make_dir)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_make_dir(root->root, path, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */


/* {{{ proto resource svn_fs_apply_text(resource root, string path)
	Creates and returns a stream that will be used to replace
	the content of an existing file. */
PHP_FUNCTION(svn_fs_apply_text)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	svn_stream_t *stream_p = NULL;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_apply_text(&stream_p, root->root, path, NULL, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	if (stream_p) {
		php_stream *stm;
		stm = php_stream_alloc(&php_svn_stream_ops, stream_p, 0, "w");
		php_stream_to_zval(stm, return_value);
	} else {
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_copy(resource from_root, string from_path, resourse to_root, string to_path)
	Create a copy of from_path in from_root named to_path in to_root, returns true on success, false otherwise */
PHP_FUNCTION(svn_fs_copy)
{
	zval *zfrom_root, *zto_root;
	struct php_svn_fs_root *from_root, *to_root;
	const char *from_path = NULL, *to_path = NULL;
	const char *utf8_from_path = NULL, *utf8_to_path = NULL;
	int from_path_len, to_path_len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rsrs",
				&zfrom_root, &from_path, &from_path_len,
				&zto_root, &to_path, &to_path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_from_path, from_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_to_path, to_path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	from_path = svn_path_canonicalize(utf8_from_path, subpool);
	to_path = svn_path_canonicalize(utf8_to_path, subpool);

	ZEND_FETCH_RESOURCE(from_root, struct php_svn_fs_root *, &zfrom_root, -1, "svn-fs-root", le_svn_fs_root);
	ZEND_FETCH_RESOURCE(to_root, struct php_svn_fs_root *, &zto_root, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_copy(from_root->root, from_path, to_root->root, to_path, to_root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_delete(resource root, string path)
	Delete the filesystem at path, return true on success, false otherwise */
PHP_FUNCTION(svn_fs_delete)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_delete(root->root, path, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto resource svn_fs_begin_txn2(resource repos, long rev)
	Begin a new transaction on the filesystem, based on the existing revision specified. */
PHP_FUNCTION(svn_fs_begin_txn2)
{
	svn_fs_txn_t *txn_p = NULL;
	struct php_svn_repos_fs_txn *new_txn = NULL;
	zval *zfs;
	struct php_svn_fs *fs = NULL;
	svn_revnum_t rev;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rl",
				&zfs, &rev)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fs, struct php_svn_fs *, &zfs, -1, "svn-fs", le_svn_fs);

	err = svn_fs_begin_txn2(&txn_p, fs->fs, rev, 0, SVN_G(pool));

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	}

	if (txn_p) {
		new_txn = emalloc(sizeof(*new_txn));
		new_txn->repos = fs->repos;
		zend_list_addref(fs->repos->rsrc_id);
		new_txn->txn = txn_p;

		ZEND_REGISTER_RESOURCE(return_value, new_txn, le_svn_repos_fs_txn);
	} else {
		RETURN_FALSE;
	}
}
/* }}} */

/* {{{ proto bool svn_fs_is_file(resource root, string path)
	Returns true if path in root is a file, false otherwise */
PHP_FUNCTION(svn_fs_is_file)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	svn_boolean_t is_file;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_is_file(&is_file, root->root, path, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_BOOL(is_file);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_is_dir(resource root, string path)
	Returns true if path in root is a directory, false otherwise */
PHP_FUNCTION(svn_fs_is_dir)
{
	zval *zroot;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	int path_len;
	svn_error_t *err;
	svn_boolean_t is_dir;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zroot, &path, &path_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_is_dir(&is_dir, root->root, path, root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_BOOL(is_dir);
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_change_node_prop(resource root, string path, string name, string value)
	Change a node's property's value, or add/delete a property. (use NULL as value to delete) Returns true on success. */
PHP_FUNCTION(svn_fs_change_node_prop)
{
	zval *zroot, *value;
	struct php_svn_fs_root *root = NULL;
	const char *path = NULL, *utf8_path = NULL;
	char *name;
	int path_len, name_len, value_len;
	svn_string_t *svn_value = NULL;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rssz",
				&zroot, &path, &path_len, &name, &name_len, &value)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path, path, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	path = svn_path_canonicalize(utf8_path, subpool);

	ZEND_FETCH_RESOURCE(root, struct php_svn_fs_root *, &zroot, -1, "svn-fs-root", le_svn_fs_root);

	if (Z_TYPE_P(value) != IS_NULL) {
		convert_to_string_ex(&value);
		svn_value = emalloc(sizeof(*svn_value));
		svn_value->data = Z_STRVAL_P(value);
		svn_value->len  = Z_STRLEN_P(value);
	}

	err = svn_fs_change_node_prop(root->root, path, name, svn_value,
			root->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else {
		RETVAL_TRUE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_contents_changed(resource root1, string path1, resource root2, string path2)
	Returns true if the contents at path1 under root1 differ from those at path2 under root2, or set it to 0 if they are the same */
PHP_FUNCTION(svn_fs_contents_changed)
{
	zval *zroot1, *zroot2;
	struct php_svn_fs_root *root1 = NULL, *root2 = NULL;
	const char *path1 = NULL, *path2 = NULL;
	const char *utf8_path1 = NULL, *utf8_path2 = NULL;
	int path1_len, path2_len;
	svn_boolean_t changed;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rsrs",
				&zroot1, &path1, &path1_len,
				&zroot2, &path2, &path2_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path1, path1, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_path2, path2, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path1 = svn_path_canonicalize(utf8_path1, subpool);
	path2 = svn_path_canonicalize(utf8_path2, subpool);

	ZEND_FETCH_RESOURCE(root1, struct php_svn_fs_root *, &zroot1, -1, "svn-fs-root", le_svn_fs_root);
	ZEND_FETCH_RESOURCE(root2, struct php_svn_fs_root *, &zroot2, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_contents_changed(&changed, root1->root, path1,
			root2->root, path2,	root1->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (changed == 1) {
		RETVAL_TRUE;
	} else {
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_props_changed(resource root1, string path1, resource root2, string path2)
	Returns true if the properties of two path/root combinations are different, else false. */
PHP_FUNCTION(svn_fs_props_changed)
{
	zval *zroot1, *zroot2;
	struct php_svn_fs_root *root1 = NULL, *root2 = NULL;
	const char *path1 = NULL, *path2 = NULL;
	const char *utf8_path1 = NULL, *utf8_path2 = NULL;
	int path1_len, path2_len;
	svn_boolean_t changed;
	svn_error_t *err;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rsrs",
				&zroot1, &path1, &path1_len,
				&zroot2, &path2, &path2_len)) {
		RETURN_FALSE;
	}

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_utf_cstring_to_utf8 (&utf8_path1, path1, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}
	err = svn_utf_cstring_to_utf8 (&utf8_path2, path2, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
		goto cleanup;
	}

	path1 = svn_path_canonicalize(utf8_path1, subpool);
	path2 = svn_path_canonicalize(utf8_path2, subpool);

	ZEND_FETCH_RESOURCE(root1, struct php_svn_fs_root *, &zroot1, -1, "svn-fs-root", le_svn_fs_root);
	ZEND_FETCH_RESOURCE(root2, struct php_svn_fs_root *, &zroot2, -1, "svn-fs-root", le_svn_fs_root);

	err = svn_fs_props_changed(&changed, root1->root, path1,
			root2->root, path2,	root1->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (changed == 1) {
		RETVAL_TRUE;
	} else {
		RETVAL_FALSE;
	}

cleanup:
	svn_pool_destroy(subpool);
}
/* }}} */

/* {{{ proto bool svn_fs_abort_txn(resource txn)
	Aborts a transaction, returns true on success, false otherwise */
PHP_FUNCTION(svn_fs_abort_txn)
{
	zval *ztxn;
	struct php_svn_repos_fs_txn *txn;
	svn_error_t *err;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "r",
				&ztxn)) {
		return;
	}

	ZEND_FETCH_RESOURCE(txn, struct php_svn_repos_fs_txn *, &ztxn, -1, "svn-repos-fs-txn", le_svn_repos_fs_txn);

	err = svn_fs_abort_txn(txn->txn, txn->repos->pool);

	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETURN_FALSE;
	} else {
		RETURN_TRUE;
	}
}
/* }}} */

/* {{{ proto resource svn_fs_open_txn(resource fs, string name)
	Opens a transaction, returns a transaction resource on success, false otherwise */
PHP_FUNCTION(svn_fs_open_txn)
{
	zval *zfs;
	struct php_svn_fs *fs;
	zval *ztxn;
	struct php_svn_repos_fs_txn *txn;
	svn_error_t *err;
	const char *name = NULL;
	int name_len;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&zfs, &name, &name_len)) {
		return;
	}

	ZEND_FETCH_RESOURCE(fs, struct php_svn_fs *, &zfs, -1, "svn-fs", le_svn_fs);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_fs_open_txn (&txn, fs->fs, name, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (txn) {
		struct php_svn_repos_fs_txn *new_txn;

		new_txn = emalloc(sizeof(*new_txn));
		new_txn->repos = fs->repos;
		zend_list_addref(fs->repos->rsrc_id);
		new_txn->txn = txn;

		ZEND_REGISTER_RESOURCE(return_value, new_txn, le_svn_repos_fs_txn);
	} else {
		RETVAL_FALSE;
	}

	svn_pool_destroy (subpool);
}
/* }}} */

/* {{{ proto string svn_fs_txn_prop(resource txn, string propname)
	Fetches the value of property propname at a transaction. */
PHP_FUNCTION(svn_fs_txn_prop)
{
	zval *ztxn;
	struct php_svn_repos_fs_txn *txn;
	svn_error_t *err;
	svn_string_t *str;
	char *propname;
	int propnamelen;
	apr_pool_t *subpool;

	if (FAILURE == zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "rs",
				&ztxn, &propname, &propnamelen)) {
		return;
	}

	ZEND_FETCH_RESOURCE(txn, struct php_svn_repos_fs_txn *, &ztxn, -1, "svn-repos-fs-txn", le_svn_repos_fs_txn);

	subpool = svn_pool_create(SVN_G(pool));
	if (!subpool) {
		RETURN_FALSE;
	}

	err = svn_fs_txn_prop(&str, txn->txn, propname, subpool);
	if (err) {
		php_svn_handle_error(err TSRMLS_CC);
		RETVAL_FALSE;
	} else if (!str) {
		/* the property is not found. return an empty string */
		RETVAL_STRINGL("", 0, 1);
	} else {
		RETVAL_STRINGL((char*)str->data, str->len, 1);
	}

	svn_pool_destroy(subpool);
}
/* }}} */


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
