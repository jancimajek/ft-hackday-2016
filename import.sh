#!/bin/bash

while read line; do
	echo "Importing \"${line}\""
	aws dynamodb put-item --table "10k-list" --item "{\"word\": {\"S\": \"${line}\"}}"
done < ./google-10000-english-master/google-10000-english-no-swears.txt