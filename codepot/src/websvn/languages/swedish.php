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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
//
// --
//
// swedish.php
//
// Swedish language strings

// The language name is displayed in the drop down box. It MUST be encoded as Unicode (no HTML entities).
$lang["LANGUAGENAME"] = "Swedish";
$lang['LANGUAGETAG'] = 'sv';

$lang["LOG"] = "Logg";
$lang["DIFF"] = "Skillnad";

$lang["NOREP"] = "Inget arkiv angivet"; //repository
$lang["NOPATH"] = "Sökvägen saknas";
$lang["NOACCESS"] = "Du har inte tillräckliga rättigheter för att läsa i denna arkivsökväg";
$lang["RESTRICTED"] = "Rättighetsbegränsad";
$lang["SUPPLYREP"] = "Vänligen sätt upp en sökväg till arkivet i include/config.php med \$config->parentPath eller \$config->addRepository<p>Se installationsanvisningen för mer detaljer";

$lang["DIFFREVS"] = "Skillnad mellan rev.";
$lang["AND"] = "och";
$lang["REV"] = "Rev";
$lang["LINE"] = "Rad";
$lang["SHOWENTIREFILE"] = "Visa hela filen";
$lang["SHOWCOMPACT"] = "Visa bara områden med skillnader";

$lang["DIFFPREV"] = "Skillnad mot föregående";
$lang["BLAME"] = "Ansvarig";

$lang["REVINFO"] = "Revisionsinformation";
$lang["GOYOUNGEST"] = "Gå till senaste revision";
$lang["LASTMOD"] = "Senast ändrad";
$lang["LOGMSG"] = "Loggmeddelande";
$lang["CHANGES"] = "Ändringar";
$lang["SHOWCHANGED"] = "Visa Ändrade filer";
$lang["HIDECHANGED"] = "Göm ändrade filer";
$lang["NEWFILES"] = "Nya filer";
$lang["CHANGEDFILES"] = "Ändrade filer";
$lang["DELETEDFILES"] = "Raderade filer";
$lang["VIEWLOG"] = "Visa Logg";
$lang["PATH"] = "Sökväg";
$lang["AUTHOR"] = "Författare";
$lang["AGE"] = "Ålder";
$lang["LOG"] = "Logg";
$lang["CURDIR"] = "Nuvarande folder";
$lang["TARBALL"] = "Tarball";

$lang["PREV"] = "Föreg.";
$lang["NEXT"] = "Nästa";
$lang["SHOWALL"] = "Visa alla";

$lang["BADCMD"] = "Fel vid körning av kommando";

$lang["POWERED"] = "Powered by <a href=\"http://www.websvn.info/\">WebSVN</a>";
$lang["PROJECTS"] = "Subversion Projekt";
$lang["SERVER"] = "Subversion Server";

$lang["FILTER"] = "Filteralternativ";
$lang["STARTLOG"] = "Från rev";
$lang["ENDLOG"] = "Till rev";
$lang["MAXLOG"] = "Max revs";
$lang["SEARCHLOG"] = "Sök i logg efter";
$lang["CLEARLOG"] = "Rensa nuvarande sökning";
$lang["MORERESULTS"] = "Hitta fler träffar...";
$lang["NORESULTS"] = "Det finns ingen logg som motsvarar din sökning";
$lang["NOMORERESULTS"] = "Det finns inga fler loggar i din sökning";
$lang['NOPREVREV'] = "Ingen föregående revision";

$lang["RSSFEEDTITLE"] = "WebSVN RSS feed";
$lang["FILESMODIFIED"] = "fil(er) ändrade";
$lang["RSSFEED"] = "RSS";

$lang["LINENO"] = "Radnr.";
$lang["BLAMEFOR"] = "Ansvariginformation för rev";

$lang["DAYLETTER"] = "d";
$lang["HOURLETTER"] = "h";
$lang["MINUTELETTER"] = "m";
$lang["SECONDLETTER"] = "s";

$lang["GO"] = "Utför";

$lang["PATHCOMPARISON"] = "Sökvägsjämförelse";
$lang["COMPAREPATHS"] = "Jämför sökvägar";
$lang["COMPAREREVS"] = "Jämför revisioner";
$lang["PROPCHANGES"] = "Egenskapsändringar :";
$lang["CONVFROM"] = "Denna jämförelse visar ändringarna som behövs för att konvertera sökväg ";
$lang["TO"] = "till";
$lang["REVCOMP"] = "Växla jämförelse";
$lang["COMPPATH"] = "Jämför sökväg:";
$lang["WITHPATH"] = "Med sökväg:";
$lang["FILEDELETED"] = "Filen raderad";
$lang["FILEADDED"] = "Ny fil";

// The following are defined by some languages to stop unwanted line splitting
// in the template files.

$lang["NOBR"] = "";
$lang["ENDNOBR"] = "";

// $lang["NOBR"] = "<nobr>";
// $lang["ENDNOBR"] = "</nobr>";