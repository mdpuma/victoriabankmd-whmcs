#!/bin/bash

openssl genrsa -f4 -out my_private.key 2048
openssl rsa -pubout -in my_private.key -out my_public.key

