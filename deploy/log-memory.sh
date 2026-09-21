#!/bin/sh
set -eu

if [ -r /sys/fs/cgroup/memory.current ]; then
    current=$(cat /sys/fs/cgroup/memory.current)
    peak=unavailable
    if [ -r /sys/fs/cgroup/memory.peak ]; then
        peak=$(cat /sys/fs/cgroup/memory.peak)
    fi
    limit=$(cat /sys/fs/cgroup/memory.max)
elif [ -r /sys/fs/cgroup/memory/memory.usage_in_bytes ]; then
    current=$(cat /sys/fs/cgroup/memory/memory.usage_in_bytes)
    peak=$(cat /sys/fs/cgroup/memory/memory.max_usage_in_bytes)
    limit=$(cat /sys/fs/cgroup/memory/memory.limit_in_bytes)
else
    exit 0
fi

printf 'CONTAINER_MEMORY current_bytes=%s peak_bytes=%s limit_bytes=%s\n' "$current" "$peak" "$limit"
