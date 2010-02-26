#!/bin/sh

make_repo() {
	local repodir="$1"
	local reponame="$2"
	local cfgdir="$3"
	local api="$4"

	echo "${reponame}" | grep -qF '/' && {
		echo "ERROR: invalid repository name - ${reponame}"
		return 1
	}

	mkdir -p "${repodir}" >/dev/null 2>&1 || {
		echo "ERROR: cannot create directory - ${repodir}"
		return 1
	}

	[ -d "${repodir}" -a -w "${repodir}" -a -x "${repodir}" ] || {
		echo "ERROR: directory set with wrong permission - ${repodir}"
		return 1;
	}

	#[ -f "${repodir}/start-commit" ] || {
		sed "s|%API%|${api}|g" "${cfgdir}/start-commit" > "${repodir}/start-commit" || {
			echo "ERROR: cannot install start-commit to ${repodir}"
			return 1;
		}
		chmod 0755 "${repodir}/start-commit"
	#}

	#[ -f "${repodir}/pre-commit" ] || {
	#	sed "s|%API%|${api}|g" "${cfgdir}/pre-commit" > "${repodir}/pre-commit" || {
	#		echo "ERROR: cannot install pre-commit to ${repodir}"
	#		return 1;
	#	}
	#	chmod 0755 "${repodir}/pre-commit"
	#}

	#[ -f "${repodir}/post-commit" ] || {
		sed "s|%API%|${api}|g" "${cfgdir}/post-commit" > "${repodir}/post-commit" || {
			echo "ERROR: cannot install post-commit to ${repodir}"
			return 1;
		}
		chmod 0755 "${repodir}/post-commit"
	#}

	svnadmin create "${repodir}/${reponame}" && {
		oldpwd="`pwd`"
		cd "${repodir}/${reponame}/hooks"
		ln -sf ../../start-commit start-commit
		ln -sf ../../post-commit post-commit
		cd "${oldpwd}"
	}

	return 0;
}

delete_repo() {
	local repodir="$1"
	local reponame="$2"

	echo "${reponame}" | grep -qF '/' && {
		echo "ERROR: invalid repository name - ${reponame}"
		return 1
	}

	rm -rf "${repodir}/${reponame}" 
}


case $1 in
make)
	make_repo "$2" "$3" "$4" "$5"
	;;
delete)
	delete_repo "$2" "$3"
	;;
*)
	exit 1
	;;
esac

