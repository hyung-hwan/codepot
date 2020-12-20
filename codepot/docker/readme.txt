Run the service
	docker run rm -dit --restart unless-stopped --name codepot -p 7000:80 codepot:ubnt20.04

Run the shell on the existing container
	docker exec -it codepot /bin/bash
