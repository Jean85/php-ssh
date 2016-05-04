<?php

namespace Ssh;

use RuntimeException;

/**
 * Wrapper for ssh2_exec
 *
 * @author Cam Spiers <camspiers@gmail.com>
 * @author Greg Militello <junk@thinkof.net>
 * @author Gildas Quéméner <gildas.quemener@gmail.com>
 */
class Exec extends Subsystem
{
    protected function createResource()
    {
        $this->resource = $this->getSessionResource();
    }

    /**
     * @param $cmd
     * @param null $pty
     * @param array $env THIS IS DISABLED -- due to bug in the SSH2 ext under PHP7 https://bugs.php.net/bug.php?id=72150
     * @param int $width
     * @param int $height
     * @param int $width_height_type
     * @return mixed
     */
    public function run($cmd, $pty = null, array $env = array(), $width = 80, $height = 25, $width_height_type = SSH2_TERM_UNIT_CHARS)
    {
        $cmd .= ';echo -ne "[return_code:$?]"';
        $stdout = ssh2_exec($this->getResource(), $cmd, $pty, null, $width, $height, $width_height_type);
        $stderr = ssh2_fetch_stream($stdout, SSH2_STREAM_STDERR);
        stream_set_blocking($stderr, true);
        stream_set_blocking($stdout, true);

        $output = stream_get_contents($stdout);
        preg_match('/\[return_code:(.*?)\]/', $output, $match);
        if ((int) $match[1] !== 0) {
            throw new RuntimeException(stream_get_contents($stderr), (int) $match[1]);
        }

        return preg_replace('/\[return_code:(.*?)\]/', '', $output);
    }
}
