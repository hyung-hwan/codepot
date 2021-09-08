If you require the root privilege to build a docker image, specify sudo in DOCKER.
	make rocky8 DOCKER="sudo docker"

Run the service
	docker run -dit --restart unless-stopped --name codepot -p 7000:80 codepot:ubnt20.04

Run the shell on the existing container
	docker exec -it codepot /bin/bash
