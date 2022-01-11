If you require the root privilege to build a docker image, specify sudo in DOCKER.
```
make rocky8 DOCKER="sudo docker"
make ubnt2004 DOCKER="sudo docker"
```

Run the service like
```
docker run -dit --restart=unless-stopped --name=codepot codepot:ubnt20.04
docker run -dit --restart=unless-stopped --name=codepot -p 80:80 codepot:ubnt20.04
docker run -dit --restart=unless-stopped --name=codepot -p 80:80 -v /home/container-data/codepot:/var/lib/codepot codepot:ubnt20.04
docker run -dit --restart=unless-stopped --name=codepot -p 1200:1200 -v /home/container-data/codepot:/var/lib/codepot codepot:ubnt20.04 --port 1200 --hide-index-page=yes --https-redirected=yes
```

Run the shell on the existing container for in-container management.
```
docker exec -it codepot /bin/bash
```
