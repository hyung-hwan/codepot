##DOCKER ?= DOCKER_BUILDKIT=1 docker
DOCKER ?= docker

all:
	@echo "Choose one of the following targets:"
	@echo "  alpine rocky ubnt"

alpine: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:ubnt -f @srcdir@/Dockerfile.alpine @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

rocky: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:rocky -f @srcdir@/Dockerfile.rocky @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

ubnt: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:ubnt -f @srcdir@/Dockerfile.ubnt @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

@srcdir@/codepot-@VERSION@.tar.gz:
	cd .. && make dist
	cp ../codepot-@VERSION@.tar.gz  @srcdir@/

