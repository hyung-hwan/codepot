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
// indonesian.php
//
// Indonesian language strings
// by Zaenal Muttaqin <public@lokamaya.com>

// The language name is displayed in the drop down box.  It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Indonesian";
// This is the RFC 2616 (§3.10) language tag that corresponds to this translation
// see also RFC 4646
$lang['LANGUAGETAG'] = 'id';

$lang["LOG"] = "Log";
$lang["DIFF"] = "Perbedaan";

$lang["NOREP"] = "Tidak ada repositori yang disediakan";
$lang["NOPATH"] = "Path tidak ditemukan";
$lang["NOACCESS"] = "Anda tidak memiliki hak akses untuk masuk ke direktori ini";
$lang["RESTRICTED"] = "Akses terbatas";
$lang["SUPPLYREP"] = "Harap memberikan path ke repositori di file include/config.php, baik dengan \$config->parentPath atau pun \$config->addRepository<p>Lihat petunjuk instalasi untuk mendapatkan informasi yang lebih detail.";

$lang["DIFFREVS"] = "Perbedaan antar revisi";
$lang["AND"] = "dan";
$lang["REV"] = "Rev";
$lang["LINE"] = "Baris";
$lang["SHOWENTIREFILE"] = "Tampilkan Semua";
$lang["SHOWCOMPACT"] = "Tampilkan bagian yang berbeda saja";

$lang["FILEDETAIL"] = "Detail";
$lang["DIFFPREV"] = "Bandingkan";
$lang["BLAME"] = "Bubuhi";

$lang["REVINFO"] = "Informasi Revisi";
$lang["GOYOUNGEST"] = "Tampilkan revisi terbaru";
$lang["LASTMOD"] = "Perubahan terakhir";
$lang["LOGMSG"] = "Pesan log";
$lang["CHANGES"] = "Daftar yang berubah";
$lang["SHOWCHANGED"] = "Tampilkan perubahan file";
$lang["HIDECHANGED"] = "Sembunyikan perubahan file";
$lang["NEWFILES"] = "Daftar file baru";
$lang["CHANGEDFILES"] = "Daftar file yang berubah";
$lang["DELETEDFILES"] = "Daftar file yang dihapus";
$lang["VIEWLOG"] = "Lihat&nbsp;Log";
$lang["PATH"] = "Path";
$lang["AUTHOR"] = "Pemrakarsa";
$lang["AGE"] = "Usia";
$lang["LOG"] = "Log";
$lang["CURDIR"] = "Direktori saat ini";
$lang["TARBALL"] = "Tarball";

$lang["PREV"] = "Kembali";
$lang["NEXT"] = "Lanjut";
$lang["SHOWALL"] = "Tampilkan Semua";

$lang["BADCMD"] = "Kesalahan menjalankan instruksi ini";
$lang["UNKNOWNREVISION"] = "Revisi tidak ditemukan";

$lang["POWERED"] = "Didukung oleh <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Repositori";
$lang["SERVER"] = "Server";
$lang["WIKI"] = "Wiki";
$lang["SOURCE"] = "sumber";
$lang["FILES"] = "File";
$lang["HOME"] = "Home";

$lang["FILTER"] = "Pilihan Filter";
$lang["STARTLOG"] = "Dari rev";
$lang["ENDLOG"] = "Ke rev";
$lang["MAXLOG"] = "Maks rev";
$lang["SEARCHLOG"] = "Pencarian untuk";
$lang["CLEARLOG"] = "Hapus filter yang ada";
$lang["MORERESULTS"] = "Cari lebih lanjut...";
$lang["NORESULTS"] = "Tidak ada log yang sesuai dengan permintaan anda";
$lang["NOMORERESULTS"] = "Tidak ada lagi log yang bisa ditampilkan";
$lang['NOPREVREV'] = 'Tidak ada lagi revisi yang lebih lama';

$lang["RSSFEEDTITLE"] = "Feed RSS WebSVN";
$lang["FILESMODIFIED"] = "file yang berubah";
$lang["RSSFEED"] = "Feed RSS";

$lang["LINENO"] = "Baris No.";
$lang["BLAMEFOR"] = "Bubuhi (blame) informasi for rev";

$lang["DAYLETTER"] = "d";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Pilih";

$lang["PATHCOMPARISON"] = "Perbandingan Path";
$lang["COMPAREPATHS"] = "Bandingkan Paths";
$lang["COMPAREREVS"] = "Bandingkan Revisi";
$lang["PROPCHANGES"] = "Perubahan properti :";
$lang["CONVFROM"] = "Perbandingan ini menunjukkan perubahan yang diperlukan untuk mengkonversi path ";
$lang["TO"] = "Ke";
$lang["REVCOMP"] = "Balikan Perbandingan";
$lang["COMPPATH"] = "Bandingkan Path:";
$lang["WITHPATH"] = "Dengan Path:";
$lang["FILEDELETED"] = "File dihapus";
$lang["FILEADDED"] = "File baru";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";
