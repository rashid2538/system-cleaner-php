<?php

function dump()
{
    var_dump('==========================', date('Y-m-d H:i:s'), ...func_get_args());
}

function dd()
{
    dump(...func_get_args());
    die;
}

function size_format(int $kiloBytes) {
    $kb = 1024;
    $bytes = $kiloBytes * 1024;
    $mb = $kb * 1024;
    $gb = $mb * 1024;
    $tb = $gb * 1024;

    if ($bytes >= 0 && $bytes < $kb) {
        return $bytes . ' B';
    } elseif ($bytes >= $kb && $bytes < $mb) {
        return ceil($bytes / $kb) . ' KB';
    } elseif ($bytes >= $mb && $bytes < $gb) {
        return ceil($bytes / $mb) . ' MB';
    } elseif ($bytes >= $gb && $bytes < $tb) {
        return ceil($bytes / $gb) . ' GB';
    } else {
        return ceil($bytes / $tb) . ' TB';
    }
}

function folder_size(string $folder): int
{
    return 1 * explode("\t", shell_exec("du -s '$folder'"), 2)[0];
}

// $result = glob($path . '{,.}[!.,!..]*',GLOB_MARK|GLOB_BRACE);
function get_files_and_folders(string $directory)
{
    $directory = rtrim($directory, '/') . '/';
    $entries = glob($directory. '{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
    $result = ['files' => [], 'folders' => []];
    foreach($entries as $entry) {
        if(is_file($entry)) {
            $result['files'][] = [
                'name' => str_replace($directory, '', $entry),
                'path' => $entry,
                'size' => filesize($entry),
            ];
        } else {
            
            $result['folders'][] = [
                'name' => rtrim(str_replace($directory, '', $entry), '/'),
                'path' => rtrim($entry, '/'),
            ];
        }
    }
    return $result;
}

function cleanup(string $directory)
{
    $filesAndFolders = get_files_and_folders($directory);
    $cleaners = ['cache', 'node_modules', 'vendor'];
    foreach($cleaners as $cleaner) {
        $method = $cleaner . '_cleanup';
        $method($filesAndFolders);
    }
    foreach($filesAndFolders['folders'] as $folder) {
        if(!in_array($folder['name'], $cleaners)) {
            cleanup($folder['path']);
        }
    }
}

function delete_folder_with_confimation(string $folder, string $prompt)
{
    $size = folder_size($folder);
    if($size < 1024 * 10) {
        return;
    }
    echo $prompt . $folder . "\n\tWith size: " . size_format($size) . "\n\tWould you like to delete this folder(yes/y/no/n): ";
    $input = strtolower(trim(readline_terminal()));
    if(in_array($input, ['yes', 'y'])) {
        shell_exec("rm -rf '$folder'");
    }
}

function readline_terminal(string $prompt = '') {
    $prompt && print $prompt;
    $terminal_device = '/dev/tty';
    $h = fopen($terminal_device, 'r');
    if ($h === false) {
        #throw new RuntimeException("Failed to open terminal device $terminal_device");
        return false; # probably not running in a terminal.
    }
    $line = fgets($h);
    fclose($h);
    return $line;
}

function cache_cleanup(array $filesAndFolders)
{
    $folders = array_values(array_filter($filesAndFolders['folders'], fn($f) => count(explode('cache', strtolower($f['name']))) > 1));
    foreach($folders as $folder) {
        delete_folder_with_confimation($folder['path'], 'Found cache folder: ');
    }
}

function node_modules_cleanup(array $filesAndFolders)
{
    $file = array_values(array_filter($filesAndFolders['files'], fn($f) => $f['name'] === 'package.json'));
    if(!empty($file)) {
        $folder = array_values(array_filter($filesAndFolders['folders'], fn($f) => $f['name'] === 'node_modules'));
        if(!empty($folder)) {
            delete_folder_with_confimation($folder[0]['path'], 'Found node_modules folder: ');
        }
    }
}

function vendor_cleanup(array $filesAndFolders)
{
    $file = array_values(array_filter($filesAndFolders['files'], fn($f) => $f['name'] === 'composer.json'));
    if(!empty($file)) {
        $folder = array_values(array_filter($filesAndFolders['folders'], fn($f) => $f['name'] === 'vendor'));
        if(!empty($folder)) {
            delete_folder_with_confimation($folder[0]['path'], 'Found vendor folder: ');
        }
    }
}
