all:
	@echo "Choose one of the following targets:"
	@echo "  rocky84 ubnt2004"

ubnt2004: codepot-@VERSION@.tar.gz
	docker build -t codepot:ubnt20.04 -f Dockerfile.ubnt2004 @srcdir@

rocky84: codepot-@VERSION@.tar.gz
	docker build -t codepot:rocky8.4 -f Dockerfile.rocky84 @srcdir@

codepot-@VERSION@.tar.gz:
	cd .. && make dist
	cp ../codepot-@VERSION@.tar.gz  .

