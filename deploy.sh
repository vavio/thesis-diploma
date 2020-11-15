#!/bin/bash


release=$(date +"%Y%m%d")
moodle_name="qtype_codecpp_moodle39_${release}00.zip"
echo "Creating ${moodle_name}"
zip -q -r ${moodle_name} codecpp

webservice_name="qtype_codecpp_webservice_${release}00.zip"
echo "Creating ${webservice_name}"
zip -q -r ${webservice_name} webservice