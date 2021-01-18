<?php

if (!(strnatcmp(phpversion(), '5.2.0') >= 0)) {
    die("You need at least PHP 5.2!");
}

// For debug
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

$GENERATOR_DOMAIN_NAME = getenv('GENERATOR_DOMAIN_NAME');
$GENERATOR_DOMAIN_NAME = !empty($GENERATOR_DOMAIN_NAME) ? $GENERATOR_DOMAIN_NAME : 'example.com';

$GENERATOR_DOMAIN_TYPE = getenv('GENERATOR_DOMAIN_TYPE');
$GENERATOR_DOMAIN_TYPE = !empty($GENERATOR_DOMAIN_TYPE) && $GENERATOR_DOMAIN_TYPE === 'single' ? 'single' : $GENERATOR_DOMAIN_TYPE;

/**
 * Convert umlauts
 * @param $str
 * @return string|string[]
 */
function convert_umlauts($str)
{
    $chars = [
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss',
    ];
    foreach ($chars as $char => $replaceable) {
        $str = str_replace($char, $replaceable, $str);
    }
    return $str;
}

if (!empty($_FILES)) {

    $cwd = getcwd();
    $uploaddir = 'uploads' . DIRECTORY_SEPARATOR;
    $uploaddir_with_date = $uploaddir . date('Y/m/d') . DIRECTORY_SEPARATOR;
    $fulluploaddir = $uploaddir_with_date;
    if (!file_exists($fulluploaddir)) {
        mkdir($cwd . DIRECTORY_SEPARATOR . $fulluploaddir, 0755, true);
    }
    @chmod($cwd . DIRECTORY_SEPARATOR . $fulluploaddir, 0755);

    $file = fopen($cwd . DIRECTORY_SEPARATOR . $uploaddir . DIRECTORY_SEPARATOR . 'index.html', 'w') or die('fail on upload');
    fclose($file);

    $file = fopen($cwd . DIRECTORY_SEPARATOR . $uploaddir . DIRECTORY_SEPARATOR . date('Y') . DIRECTORY_SEPARATOR . 'index.html', 'w') or die('fail on upload year');
    fclose($file);

    $file = fopen($cwd . DIRECTORY_SEPARATOR . $uploaddir . DIRECTORY_SEPARATOR . date('Y/m') . DIRECTORY_SEPARATOR . 'index.html', 'w') or die('fail on upload month');
    fclose($file);

    // Empty index.html in the new folder
    $file = fopen($cwd . DIRECTORY_SEPARATOR . $fulluploaddir . DIRECTORY_SEPARATOR . 'index.html', 'w') or die('fail on upload day');
    fclose($file);

    $ul_file = basename($_FILES['app_logo']['name']);
    $ul_filename = pathinfo($ul_file, PATHINFO_EXTENSION);
    $ul_extension = pathinfo($ul_file, PATHINFO_EXTENSION);

    $new_file = str_replace('.', '', str_replace(' ', '', microtime())) . '.' . $ul_extension;

    // 65456465446546.jpg
    rename($_FILES['app_logo']['tmp_name'], $new_file);

    $uploadfile = $fulluploaddir . $new_file;
    $isValid = copy($new_file, $uploadfile);
    if ($isValid) {
        unlink($new_file);
    }

    // Remove current cordova template logo.png
    unlink($cwd . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.png');

    // Copy the new logo to template logo file
    $new_logo_file = 'logo.' . $ul_extension;
    copy($uploadfile, $cwd . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . $new_logo_file);
}

$app_name = convert_umlauts(array_key_exists('app_name', $_POST) ? $_POST['app_name'] : 'test');
$app_uid = convert_umlauts(array_key_exists('app_uid', $_POST) ? $_POST['app_uid'] : 'test');
$mobile_application_namespace = convert_umlauts(array_key_exists('mobile_application_namespace', $_POST) ?
    $_POST['mobile_application_namespace'] : 'com.platform.test' . time());


$app_uid_link = $GENERATOR_DOMAIN_TYPE === 'single' ?
    'https://' . $GENERATOR_DOMAIN_NAME . '/' :
    'https://' . $app_uid . '.' . $GENERATOR_DOMAIN_NAME . '/';

$cordova_files_path = getcwd() . DIRECTORY_SEPARATOR . 'template';
//this folder must be writeable by the server
$cordova_path = getcwd() . DIRECTORY_SEPARATOR . 'cordova';
if (!file_exists($fulluploaddir)) {
    mkdir($cordova_path, 0755, true);
}
@chmod($cordova_path, 0755);
$zip_file = $cordova_path . DIRECTORY_SEPARATOR . $app_name . ' - cordova files ' . date('d-m-Y') . '.zip';

if ($handle = opendir($cordova_files_path) && !empty($_POST)) {
    // Get real path for our folder
    $rootPath = realpath($cordova_files_path);

    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            // Add current file to archive and replace the dynamic variables
            if (in_array($file->getExtension(), ['js', 'html', 'css', 'xml', 'json'])) {
                $file_get_contents = file_get_contents($file);
                $file_get_contents = str_replace('$business_name$', $app_name, $file_get_contents);
//                $file_get_contents = str_replace('$app_uid$', $app_uid, $file_get_contents);
                $file_get_contents = str_replace('$new_logo_file$', $new_logo_file, $file_get_contents);
                $file_get_contents = str_replace('$mobile_application_namespace$', $mobile_application_namespace, $file_get_contents);
                $file_get_contents = str_replace('$mobile_application_subdomain$', $app_uid_link, $file_get_contents);
                $zip->addFromString(substr($filePath, strlen($cordova_files_path) + 1), $file_get_contents);
            } else {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

    // Restore the cordova original file
    copy($cwd . DIRECTORY_SEPARATOR . 'original_logo.png', $cwd . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . 'logo.png');

    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary");
    header("Content-disposition: attachment; filename=\"" . basename($zip_file) . "\"");
    readfile($zip_file);
    exit;

}
?>
<html>
<head>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>
<body>

<div class="container">
    <div class="row">
        <div class="col-12">
            <form method="POST" action="index.php" class="form" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="app_name" class="form-control" placeholder="Example - result: Example">
                </div>
                <?php if ($GENERATOR_DOMAIN_TYPE === 'wildcard'): ?>
                    <div class="form-group">
                        <label>UID:</label>
                        <input type="text" name="app_uid" class="form-control"
                               placeholder="example - result: app_example">
                    </div>
                <?php endif; ?>
                <div class="form-group">
                    <label>Namespace:</label>
                    <input type="text" name="mobile_application_namespace" class="form-control"
                           placeholder="example - result: com.platform.example">
                </div>
                <div class="form-group">
                    <label>App logo: (optional - best resolution 512 x 512px)</label>
                    <input type="file" name="app_logo" class="form-control">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-info">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<footer class="container">
    <div class="row">
        <div class="col-12 text-center">
            &copy;<?php echo date('Y'); ?> <a href="https://islamagic.com/" alt="Ermin Islamagić">Ermin Islamagić.</a>
            All rights reserved.
        </div>
    </div>
</footer>
</body>
</html>
