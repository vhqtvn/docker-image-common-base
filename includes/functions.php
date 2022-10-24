<?php

function dockerfile_include_dir($name, $dir, $cwd = null, $opts = [])
{
    if (is_null($cwd)) $cwd = $dir;
    echo "# INCLUDE $name: $dir\n";
    if (!file_exists($fn = "$dir/Dockerfile")) throw new Exception("$dir does not have a Dockerfile");
    $uniq = md5(random_bytes(10));
    $img_prefix = "$name--";
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
    $from_alias = [];
    foreach (file($fn) as $line) {
        if (isset($opts['ignore']) && $opts['ignore']($line)) continue;
        if (str_starts_with($line, '#')) {
            echo $line;
            continue;
        }
        if (!$continue) switch ($cmd = strtoupper(($toks = preg_split("!\s!", trim($line)))[0])) {
            case "ADD":
                throw new Exception("Usage of ADD command: $line");
            case "COPY":
                $changed = false;
                for ($fr = 1; $fr < count($toks) && str_starts_with($toks[$fr], "--"); $fr++) {
                    if (str_starts_with($toks[$fr], "--from=")) {
                        $from = substr($toks[$fr], 7);
                        if (isset($from_alias[$from]))
                            $toks[$fr] = "--from=" . $from_alias[$from];
                        $changed = true;
                    }
                }
                for ($i = $fr; $i + 1 < count($toks); $i++)
                    if (!str_starts_with($toks[$i], "/")) {
                        $changed = true;
                        $toks[$i] = "$cwd/" . $toks[$i];
                    }
                if ($changed) {
                    echo "# changed from $line";
                    echo implode(" ", $toks) . "\n";
                } else {
                    echo "$line\n";
                }
                break;
            case "FROM":
                if ($nfrom) {
                    echo $flush();
                    ob_start();
                }
                if (!(count($toks) == 2
                    || (count($toks) == 4 && strtolower($toks[2]) == "as"))) {
                    throw new Error("invalid line: $line");
                }
                $from = $toks[1];
                if (isset($from_alias[$from])) {
                    $toks[1] = $from_alias[$from];
                    $line = implode(" ", $toks) . "\n";
                }
                if (count($toks) == 4) {
                    $from_alias[$toks[3]] = $img_prefix . $toks[3];
                    $toks[3] = $img_prefix . $toks[3];
                    $line = implode(" ", $toks) . "\n";
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
