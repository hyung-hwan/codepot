all:
	@echo "Choose one of the following targets:"
	@echo "  rocky84 ubnt2004"

ubnt2004: codepot-$(VERSION):
	docker build -t codepot:ubnt20.04 -f Dockerfile.ubnt2004 .

rocky84: codepot-$(VERSION):
	docker build -t codepot:rocky8.4 -f Dockerfile.rocky84 .

codepot-$(VERSION):
	cd .. && make dist

