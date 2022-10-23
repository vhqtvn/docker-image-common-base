<?php

function dockerfile_include_dir($name, $dir, $cwd = null, $opts = [])
{
    if (is_null($cwd)) $cwd = $dir;
    echo "# INCLUDE $name: $dir\n";
    if (!file_exists($fn = "$dir/Dockerfile")) throw new Exception("$dir does not have a Dockerfile");
    $uniq = md5(random_bytes(10));
    ob_start();
    $continue = false;
    $nfrom = 0;
    $flush = function ($final = false) use ($uniq, $name) {
        $content = ob_get_clean();
        if (!$final) {
            $content = str_replace("ENV {$uniq}_", "", $content);
        } else {
            $content = str_replace("ENV {$uniq}_", "ENV {$name}_", $content);
        }
        return "# REGION $name\n" . $content . "\n# ENDREGION $name\n";
    };
    foreach (file($fn) as $line) {
        if (isset($opts['ignore']) && $opts['ignore']($line)) continue;
        if (str_starts_with($line, '#')) {
            echo $line;
            continue;
        }
        if (!$continue) switch ($cmd = ($toks = preg_split("!\s!", trim($line)))[0]) {
            case "COPY":
                if (str_starts_with($toks[1], "--from")) {
                    echo $line;
                    break;
                }
                for ($i = 1; $i + 1 < count($toks); $i++)
                    if (!str_starts_with($toks[$i], "/")) {
                        $toks[$i] = "$cwd/" . $toks[$i];
                    }
                echo "# changed from $line";
                echo implode(" ", $toks) . "\n";
                break;
            case "FROM":
                if ($nfrom) {
                    echo $flush();
                    ob_start();
                }
                $nfrom++;
                $line = "ENV {$uniq}_" . ltrim($line);
                echo $line;
                break;
            case "ENTRYPOINT":
            case "CMD":
                $line = "ENV {$name}_" . ltrim($line);
            default:
                echo $line;
                break;
        }
        else {
            echo $line;
        }
        if (preg_match("![\\\\]+$!", $line, $m) && strlen($m[0]) % 2) {
            $continue = true;
        } else {
            $continue = false;
        }
    }
    global $pending_content;
    if (!isset($pending_content)) $pending_content = [];
    $pending_content[] = $flush(true);
}

function docker_file_flush_pending()
{
    echo "# PENDING BUILDER\n";
    global $pending_content;
    if (isset($pending_content)) {
        foreach ($pending_content as $content) {
            echo "\nWORKDIR /\n";
            echo $content . "\n\n";
        }
    }
}
