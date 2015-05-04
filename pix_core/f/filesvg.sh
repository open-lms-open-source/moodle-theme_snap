#!/bin/bash
for fullpath in "$@"
do
    filename="${fullpath##*/}"
    base="${filename%.[^.]*}"
    cp $filename $base-24.svg
    cp $filename $base-32.svg
    cp $filename $base-48.svg
    cp $filename $base-64.svg
    cp $filename $base-72.svg
    cp $filename $base-80.svg
    cp $filename $base-96.svg
    cp $filename $base-128.svg
    cp $filename $base-256.svg
done
