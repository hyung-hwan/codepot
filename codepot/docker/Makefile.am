DOCKER ?= docker

all:
	@echo "Choose one of the following targets:"
	@echo "  rocky ubnt2004"

ubnt2004: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:ubnt20.04 -f @srcdir@/Dockerfile.ubnt2004 @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

rocky: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:rocky -f @srcdir@/Dockerfile.rocky @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

@srcdir@/codepot-@VERSION@.tar.gz:
	cd .. && make dist
	cp ../codepot-@VERSION@.tar.gz  @srcdir@/

