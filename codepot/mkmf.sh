#!/bin/sh

TOPSRCDIR=www

procdir ()
{
	local curdir="$1"
	local pardir="$2"
	local instdir="$3"

	local subdirs=
	local files=

	local xcurdir="${curdir}"
	[ -n "${pardir}" ] && xcurdir="${pardir}/${xcurdir}"

	cd "${curdir}"

	for f in *
	do
		[ "${f}" = '*' ] && break;

		case "${f}" in
		Makefile*|makefile*|*.in|mkmf.sh)
			continue
			;;
		esac

		if [ -d "${f}" ] 
		then
			subdirs="${subdirs} ${f}"
			procdir "${f}" "${xcurdir}" "${instdir}/${f}"
		else
			files="${files} \\
	${f}"
		fi
	done	

	> Makefile.am
	[ -n "${subdirs}" ] && {
		echo "SUBDIRS = ${subdirs}" >> Makefile.am
		echo >> Makefile.am
	}

	[ -n "${files}" ] && {
		#echo 'wwwdir=$(WWWDIR)'"/${xcurdir}"  >> Makefile.am
		echo "wwwdir=${instdir}"  >> Makefile.am
		echo "www_DATA = ${files}" >> Makefile.am
		echo >> Makefile.am
		echo 'EXTRA_DIST = $(www_DATA)' >> Makefile.am
		echo >> Makefile.am
	}

	echo "	${xcurdir}/Makefile"
	cd ..
}

procdir "${TOPSRCDIR}" "" '$(WWWDIR)'
