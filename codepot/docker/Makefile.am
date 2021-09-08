all:
	@echo "Choose one of the following targets:"
	@echo "  rocky84 ubnt2004"

ubnt2004: @srcdir@/codepot-@VERSION@.tar.gz
	docker build -t codepot:ubnt20.04 -f @srcdir/Dockerfile.ubnt2004 @srcdir@
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

rocky84: @srcdir@/codepot-@VERSION@.tar.gz
	docker build -t codepot:rocky8.4 -f @srcdir@/Dockerfile.rocky84 @srcdir@
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

@srcdir@/codepot-@VERSION@.tar.gz:
	cd .. && make dist
	cp ../codepot-@VERSION@.tar.gz  @srcdir@/

