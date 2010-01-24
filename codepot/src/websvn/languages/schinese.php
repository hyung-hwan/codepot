<?php

// WebSVN - Subversion repository viewing via the web using PHP
// Copyright (C) 2004 Tim Armes
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
//
// --
//
// schinese-utf8.php
//
// Simple Chinese language strings
//
// Author: Liangxu Wang <wlx@mygis.org>

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Simplified Chinese";
$lang['LANGUAGETAG'] = 'zh-CN';

$lang["LOG"] = "记录";
$lang["DIFF"] = "差异";

$lang["NOREP"] = "没有仓库";
$lang["NOPATH"] = "找不到路径";
$lang["SUPPLYREP"] = "请在include/config.php中使用\$config->parentPath或\$config->addRepository设置仓库的路径<p>更详细的内容请参考安装手册";

$lang["DIFFREVS"] = "修订版本之间的差异";
$lang["AND"] = "和";
$lang["REV"] = "修订";
$lang["LINE"] = "行";
$lang["SHOWENTIREFILE"] = "显示整个文件";
$lang["SHOWCOMPACT"] = "只显示差异处";

$lang["DIFFPREV"] = "与前一次版本进行比较";
$lang["BLAME"] = "Blame";

$lang["REVINFO"] = "修订版信息";
$lang["GOYOUNGEST"] = "到最新的修订版";
$lang["LASTMOD"] = "最后修改";
$lang["LOGMSG"] = "记录消息";
$lang["CHANGES"] = "变化";
$lang["SHOWCHANGED"] = "显示有变化的文件";
$lang["HIDECHANGED"] = "隐藏有变化的文件";
$lang["NEWFILES"] = "新文件";
$lang["CHANGEDFILES"] = "已修改文件";
$lang["DELETEDFILES"] = "已删除文件";
$lang["VIEWLOG"] = "查看记录";
$lang["PATH"] = "路径";
$lang["AUTHOR"] = "作者";
$lang["AGE"] = "年龄";
$lang["LOG"] = "记录";
$lang["CURDIR"] = "当前目录";
$lang["TARBALL"] = "Tarball格式";

$lang["PREV"] = "前";
$lang["NEXT"] = "后";
$lang["SHOWALL"] = "全部显示";

$lang["BADCMD"] = "命令执行错误";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion&nbsp;Projects";
$lang["SERVER"] = "Subversion&nbsp;Server";

$lang["SEARCHLOG"] = "搜索记录内容";
$lang["CLEARLOG"] = "清除当前搜索";
$lang["MORERESULTS"] = "找个更多符合的...";
$lang["NORESULTS"] = "没有符合要求的记录";
$lang["NOMORERESULTS"] = "没有更多记录符合要求";

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "个文件被改动";
$lang["RSSFEED"] = "RSS feed";

$lang["LINENO"] = "行号";
$lang["BLAMEFOR"] = "Blame information for rev";

$lang["YEARS"] = "年";
$lang["MONTHS"] = "月";
$lang["WEEKS"] = "周";
$lang["DAYS"] = "日";
$lang["HOURS"] = "小时";
$lang["MINUTES"] = "分钟";

$lang["GO"] = "Go";

$lang["PATHCOMPARISON"] = "路径比较";
$lang["COMPAREPATHS"] = "路径比较";
$lang["COMPAREREVS"] = "比较修订版";
$lang["PROPCHANGES"] = "改变属性 :";
$lang["CONVFROM"] = "这个比较必须转换路径，从";
$lang["TO"] = "到";
$lang["REVCOMP"] = "颠倒比较";
$lang["COMPPATH"] = "路径比较:";
$lang["WITHPATH"] = "With Path:";
$lang["FILEDELETED"] = "已删除文件";
$lang["FILEADDED"] = "新文件";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
