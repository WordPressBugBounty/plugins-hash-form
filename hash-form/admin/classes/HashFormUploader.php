<?php

defined('ABSPATH') || die();

/**
 * Handle file uploads via XMLHttpRequest
 */
class HashFormUploadedFileXhr {

    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    public function save($path) {
        global $wp_filesystem;

        // Initialize the WordPress Filesystem API
        if (empty($wp_filesystem)) {
            require_once(ABSPATH . '/wp-admin/includes/file.php');
            // Attempt direct connection; if it fails, this might prompt for credentials elsewhere
            if (!WP_Filesystem()) {
                // Log error or handle failure to initialize filesystem
                // error_log('Failed to initialize WP_Filesystem.');
                return false;
            }
        }

        // Read the raw input stream into memory
        // (This replaces the initial fopen("php://input"), stream_copy_to_stream, and fclose($input))
        $raw_input_data = file_get_contents('php://input');

        if ($raw_input_data === false) {
            return false;
        }

        $realSize = strlen($raw_input_data);

        // Perform size validation
        if ($realSize != $this->getSize()) {
            // Clear the data from memory
            $raw_input_data = null;
            return false;
        }

        // Write data to the target file using WP_Filesystem
        // (This replaces tmpfile(), fseek(), fopen($path), stream_copy_to_stream, and fclose($target))
        $result = $wp_filesystem->put_contents(
            $path,
            $raw_input_data,
            FS_CHMOD_FILE // Optional: Sets recommended file permissions (e.g., 0644)
        );

        // Clear the data from memory after writing
        $raw_input_data = null;

        // Return success status
        return $result; // put_contents returns true on success, false on failure.
    }

    function getName() {
        return HashFormHelper::get_var('qqfile');
    }

    function getSize() {
        // Callers treat 0 as an empty upload and report it; throwing here would
        // surface as an uncaught fatal on the AJAX endpoint.
        return isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : 0;
    }

}

class HashFormFileUploader {

    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    private $file;
    private $error = '';

    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760) {
        $allowedExtensions = array_map('strtolower', $allowedExtensions);
        //$unallowed_extensions = array('php', 'exe', 'ini', 'perl');
        $exts = array_keys(get_allowed_mime_types());

        $available_exts = array();
        foreach ($exts as $ext) {
            $array = explode('|', $ext);
            foreach ($array as $a) {
                $available_exts[] = $a;
            }
        }

        $this->allowedExtensions = array_values(array_intersect($allowedExtensions, $available_exts));
        $this->sizeLimit = $sizeLimit;
        $this->checkServerSettings();

        if (HashFormHelper::get_var('qqfile')) {
            $this->file = new HashFormUploadedFileXhr();
        } else {
            $this->file = false;
        }
    }

    private function checkServerSettings() {
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit) {
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';
            /* translators: 1: required size in megabytes, e.g. 10M */
            $this->error = sprintf(esc_html__('Server error. Increase post_max_size and upload_max_filesize to %s.', 'hash-form'), $size);
        }
    }

    private function toBytes($str) {
        $val = trim($str);

        // An unset ini directive returns an empty string.
        if ('' === $val) {
            return 0;
        }

        $last = strtolower($val[strlen($val) - 1]);
        $val = floatval($val);
        switch ($last) {
            case 'g':
                $val *= 1024 * 1024 * 1024;
                break;
            case 'm':
                $val *= 1024 * 1024;
                break;
            case 'k':
                $val *= 1024;
                break;
        }

        return $val;
    }

    function handleUpload($uploadDirectory, $replaceOldFile = false, $upload_url = '') {
        if ($this->error) {
            return array('error' => $this->error);
        }

        $this->ensureUploadDirectory($uploadDirectory);
        $uploadDirectory = trailingslashit($uploadDirectory . '/temp');
        $upload_url = $upload_url . '/temp';
        $unallowed_extensions = array('php', 'exe', 'ini', 'perl', 'asp');

        global $wp_filesystem;

        // Initialize the WP_Filesystem if not already done
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!$wp_filesystem) {
            WP_Filesystem();
        }

        if (!$wp_filesystem || !$wp_filesystem->is_writable($uploadDirectory)) {
            return array('error' => esc_html__('Server error. Upload directory isn\'t writable.', 'hash-form'));
        }

        if (!$this->file) {
            return array('error' => esc_html__('No files were uploaded.', 'hash-form'));
        }

        $size = $this->file->getSize();

        if ($size == 0) {
            return array('error' => esc_html__('File is empty', 'hash-form'));
        }

        if ($size > $this->sizeLimit) {
            return array('error' => esc_html__('File is too large', 'hash-form'));
        }

        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

        if (in_array(strtolower($ext), $unallowed_extensions)) {
            return array('error' => esc_html__('This type of file is not allowed.', 'hash-form'));
        }

        if ($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)) {
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => esc_html__('File has an invalid extension, it should be one of', 'hash-form') . ' ' . $these . '.');
        }

        if (!$replaceOldFile) {
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= wp_rand(10, 99);
            }
        }

        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)) {
            return array(
                'success' => true,
                'url' => $upload_url . '/' . $filename . '.' . $ext,
                'path' => HashFormHelper::encrypt($filename . '.' . $ext)
            );
        } else {
            return array(
                'error' => esc_html__('Could not save uploaded file. The upload was cancelled, or server error encountered.', 'hash-form')
            );
        }
    }

    protected function ensureUploadDirectory($path) {
        global $wp_filesystem;

        // Initialize the WP_Filesystem if not already done
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        if (!$wp_filesystem) {
            WP_Filesystem();
        }

        // WP_Filesystem() returns false when it cannot connect, leaving the
        // global unset; handleUpload() reports the unwritable directory.
        if (!$wp_filesystem) {
            return;
        }

        $htaccess = $wp_filesystem->get_contents(HASHFORM_PATH . 'admin/stubs/htaccess.stub');
        $index = $wp_filesystem->get_contents(HASHFORM_PATH . 'admin/stubs/index.stub');

        // Omitting the mode lets WP_Filesystem apply FS_CHMOD_FILE itself; the
        // constant is only defined once WP_Filesystem() has run.
        if (!is_dir($path)) {
            $wp_filesystem->mkdir($path, 0755);
            $wp_filesystem->put_contents($path . '/.htaccess', $htaccess);
        }

        if (!is_dir($path . '/temp')) {
            $wp_filesystem->mkdir($path . '/temp', 0755);
            $wp_filesystem->put_contents($path . '/temp/.htaccess', $htaccess);
        }

        if (!file_exists($path . '/index.php')) {
            $wp_filesystem->put_contents($path . '/index.php', $index);
        }

        if (!file_exists($path . '/temp/index.php')) {
            $wp_filesystem->put_contents($path . '/temp/index.php', $index);
        }
    }

}
