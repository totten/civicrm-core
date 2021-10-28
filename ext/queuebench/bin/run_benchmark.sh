#!/bin/bash
LOG=/tmp/run-benchmark.txt
echo > "$LOG"

export CLASS MAX_WORKERS TASK_COUNT VERBOSE

VERBOSE=

for TRIAL in 1 2; do
for TASK_COUNT in 1 10 50; do
for MAX_WORKERS in 1 5 10 ; do
for CLASS in SingleThreadBenchmark ProcessPerTaskBenchmark ForkPerTaskBenchmark ForkPoolBenchmark HttpPerTaskBenchmark ; do
#for TRIAL in 1; do
#for TASK_COUNT in 5 10; do
#for MAX_WORKERS in 4 ; do
#for CLASS in HttpPerTaskBenchmark ; do
    php bin/benchmark.php | tee -a "$LOG"
done ## CLASS
done ## MAX_WORKERS
done ## TASK_COUNT
done ## TRIAL
