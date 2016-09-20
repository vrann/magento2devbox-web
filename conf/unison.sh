#!/usr/bin/env bash

# Gracefully stop the process on 'docker stop'
trap 'kill -TERM $PID' TERM INT

# Run unison server
unison -repeat=watch magento2 &

# Wait until the process is stopped
PID=$!
wait $PID
trap - TERM INT
wait $PID