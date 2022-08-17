DOCKER ?= DOCKER_BUILDKIT=1 docker

all:
	@echo "Choose one of the following targets:"
	@echo "  rocky ubnt"

ubnt: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:ubnt -f @srcdir@/Dockerfile.ubnt @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

rocky: @srcdir@/codepot-@VERSION@.tar.gz
	$(DOCKER) build -t codepot:rocky -f @srcdir@/Dockerfile.rocky @srcdir@ || true
	rm -f @srcdir@/codepot-@VERSION@.tar.gz

@srcdir@/codepot-@VERSION@.tar.gz:
	cd .. && make dist
	cp ../codepot-@VERSION@.tar.gz  @srcdir@/

