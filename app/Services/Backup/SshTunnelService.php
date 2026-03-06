<?php

namespace App\Services\Backup;

class SshTunnelService
{
    /**
     * Open an SSH tunnel and return the local port and PID.
     *
     * @param  array  $ssh  SSH config: host, port, user, key_path
     * @param  string  $remoteHost  Remote host visible from SSH server (usually 127.0.0.1)
     * @param  int  $remotePort  Remote port to forward
     * @return array{local_port: int, pid: int}
     *
     * @throws \RuntimeException
     */
    public function open(array $ssh, string $remoteHost, int $remotePort): array
    {
        $localPort   = $this->findFreePort();
        $authMethod  = $ssh['auth_method'] ?? 'key';
        $baseOpts    = sprintf(
            '-N -f -L %d:%s:%d -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -p %d %s@%s',
            $localPort,
            escapeshellarg($remoteHost),
            $remotePort,
            (int) ($ssh['port'] ?? 22),
            escapeshellarg($ssh['user']),
            escapeshellarg($ssh['host'])
        );

        if ($authMethod === 'password') {
            $cmd = sprintf('sshpass -p %s ssh %s', escapeshellarg($ssh['password']), $baseOpts);
        } else {
            $cmd = sprintf('ssh -i %s %s', escapeshellarg($ssh['key_path']), $baseOpts);
        }

        exec($cmd . ' 2>/dev/null', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new \RuntimeException("Failed to open SSH tunnel to {$ssh['host']}: exit code {$exitCode}");
        }

        // Give the tunnel a moment to establish
        usleep(800000);

        // Find the PID of the running tunnel
        $pid = $this->findTunnelPid($localPort);

        return [
            'local_port' => $localPort,
            'pid' => $pid,
        ];
    }

    /**
     * Close a previously opened SSH tunnel by PID.
     */
    public function close(int $pid): void
    {
        if ($pid > 0) {
            exec("kill {$pid} 2>/dev/null");
        }
    }

    /**
     * Execute a callback with an SSH tunnel active, then close it.
     *
     * @param  array  $ssh  SSH config
     * @param  string  $remoteHost  Remote host on the SSH server side
     * @param  int  $remotePort  Remote port to forward
     * @param  callable  $callback  Receives (int $localPort): mixed
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function withTunnel(array $ssh, string $remoteHost, int $remotePort, callable $callback): mixed
    {
        $tunnel = $this->open($ssh, $remoteHost, $remotePort);

        try {
            return $callback($tunnel['local_port']);
        } finally {
            $this->close($tunnel['pid']);
        }
    }

    /**
     * Build SSH base options string for rsync / ssh commands.
     */
    public function buildSshOptions(array $ssh): string
    {
        $authMethod = $ssh['auth_method'] ?? 'key';
        $baseOpts   = sprintf(
            'ssh -p %d -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null',
            (int) ($ssh['port'] ?? 22)
        );

        if ($authMethod === 'password') {
            return sprintf('sshpass -p %s %s', escapeshellarg($ssh['password']), $baseOpts);
        }

        return sprintf('%s -i %s', $baseOpts, escapeshellarg($ssh['key_path']));
    }

    // -------------------------------------------------------------------------

    private function findFreePort(): int
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $port = rand(20000, 60000);
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if (! $socket) {
                return $port;
            }
            fclose($socket);
        }

        throw new \RuntimeException('Could not find a free local port for SSH tunnel.');
    }

    private function findTunnelPid(int $localPort): int
    {
        exec("lsof -ti tcp:{$localPort} 2>/dev/null", $output);

        return (int) trim($output[0] ?? '0');
    }
}
