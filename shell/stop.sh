#!/bin/bash
ps -eaf |grep "RPC contractor Server" | grep -v "grep"| awk '{print $2}'|xargs kill -9