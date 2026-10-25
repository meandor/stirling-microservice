<?php

namespace Stirling\Core;

trait SubprocessRunner
{
    /**
     * @param string[] $lines
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runSubprocessLines(array $lines): array
    {
        $script = implode("\n", $lines) . "\n";
        $file = tempnam(sys_get_temp_dir(), "probe_");
        file_put_contents($file, $script);
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($file);
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes);
        $this->assertIsResource($process);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($file);
        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
