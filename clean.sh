#!/bin/bash
docker-compose stop
docker rmi `docker images | grep '<none>' | awk '{print $3}'`
docker rmi `docker images | grep 'frameworkcms_web' | awk '{print $3}'`
