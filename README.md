CGE Module
===================

This project documents the file sharing server of the Center for Genomic Epidemiology (CGE) running in a Docker container

Installation
=============

If in another platform that is not Linux, like MacOS X or Window, boot2docker should be installed.

Recommended installer can be found [here](http://boot2docker.io) which install Docker and the VM.

For the specific OS:
- [Mac](https://github.com/boot2docker/osx-installer/releases)
- [Windows](https://github.com/boot2docker/windows-installer/releases)

Alternatively, on Mac there is also the possibility to install it through HomeBrew

```bash
brew install boot2docker
brew install docker-compose
```

To start running the VM and the Docker daemon:

```bash
boot2docker init
boot2docker start
# To test that everything worked
boot2docker status
docker version
docker run hello-worldw
```


Usage
=============
```bash

# Prints "success..." if everything went according to plan
docker-compose run app
# Alternatively, for testing, run CLI (Bash) on main service (app)
docker-compose run app bash
# Testing MySQL and Torque
bash /usr/src/scripts/test.sh
# Outside the Docker image, open the webpage on the browser with the ip address return by
boot2docker ip
# and copy paste on browser adding :8888 at the end
# on Linux platform should be enough with 127.0.0.1:8888

docker-compose up

#################
# Additional commands

# Build container as cgepipeline image
docker build -t cgepipeline .

# Run terminal shell on selected image
docker run -t -i <imageid> /bin/bash

# Remove all containers
docker rm $(docker ps -a -q)

# Remove all images
docker rmi $(docker images -a -q)
```

Documentation
=============



License
=======

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
