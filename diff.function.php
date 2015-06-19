<?PHP
    function diff($old, $new, $nret = true, $inline = false)
    {
        $file1 = tempnam('/tmp', 'diff_');
        $file2 = tempnam('/tmp', 'diff_');
        file_put_contents($file1, $old);
        file_put_contents($file2, $new);
        $out = array();
        if ($inline) {
            @exec('wdiff -3'.(($nret) ? '1' : '2').' '.escapeshellarg($file1).' '.escapeshellarg($file2), $out);
            foreach ($out as $key => $line) {
                if ($line == '======================================================================') {
                    unset($out[$key]);
                } elseif ($nret) {
                    $out[$key] = '> '.$line;
                } else {
                    $out[$key] = '< '.$line;
                }
            }
        } else {
            @exec('diff -d --suppress-common-lines '.escapeshellarg($file1).' '.escapeshellarg($file2), $out);
        }
        $out2 = array();
        foreach ($out as $line) {
            if (
                (
                    ($nret)
                    and (preg_match('/^\> .*$/', $line))
                )
                or (
                    (!$nret)
                    and (preg_match('/^\< .*$/', $line))
                )
            ) {
                $out2[] = substr($line, 2);
            }
        }
        $out = $out2;
        unset($out2);
        unlink($file1);
        unlink($file2);

        return implode("\n", $out);
    }
