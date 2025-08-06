<?php
/**
 * Plugin Name: Advanced File Manager
 * Description: Complete file manager for WordPress with upload, edit, delete, and zip download capabilities
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WP_Advanced_File_Manager {
    
    private $plugin_url;
    private $plugin_path;
    
    public function __construct() {
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_afm_browse', array($this, 'ajax_browse'));
        add_action('wp_ajax_afm_upload', array($this, 'ajax_upload'));
        add_action('wp_ajax_afm_edit_file', array($this, 'ajax_edit_file'));
        add_action('wp_ajax_afm_save_file', array($this, 'ajax_save_file'));
        add_action('wp_ajax_afm_delete', array($this, 'ajax_delete'));
        add_action('wp_ajax_afm_create_zip', array($this, 'ajax_create_zip'));
        add_action('wp_ajax_afm_download', array($this, 'ajax_download'));
    }
    
    public function add_admin_menu() {
        add_management_page(
            'File Manager',
            'File Manager',
            'manage_options',
            'advanced-file-manager',
            array($this, 'admin_page')
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'tools_page_advanced-file-manager') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('afm-style', $this->plugin_url . 'style.css');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Advanced File Manager</h1>
            
            <div id="afm-container">
                <div id="afm-toolbar">
                    <button id="afm-upload-btn" class="button button-primary">Upload File</button>
                    <button id="afm-create-folder-btn" class="button">New Folder</button>
                    <button id="afm-zip-btn" class="button">Create ZIP</button>
                    <button id="afm-refresh-btn" class="button">Refresh</button>
                </div>
                
                <div id="afm-path">
                    <span>Current Path: </span>
                    <span id="current-path">/</span>
                </div>
                
                <div id="afm-files">
                    <div class="file-header">
                        <span class="file-icon"></span>
                        <span class="file-name">Name</span>
                        <span class="file-size">Size</span>
                        <span class="file-modified">Last Modified</span>
                        <span class="file-actions">Actions</span>
                    </div>
                    <!-- Files will be loaded here -->
                </div>
                
                <!-- Progress Modal -->
                <div id="progress-modal" style="display: none;">
                    <div class="modal-content">
                        <h3 id="progress-title">Processing...</h3>
                        <div class="progress-bar-container">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progress-fill"></div>
                            </div>
                            <span class="progress-text" id="progress-text">0%</span>
                        </div>
                        <div id="progress-status">Preparing...</div>
                    </div>
                </div>
                
                <input type="file" id="file-upload" style="display: none;" multiple>
            </div>
            
            <!-- Edit Modal -->
            <div id="edit-modal" style="display: none;">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h3>Edit File: <span id="edit-filename"></span></h3>
                    <textarea id="file-content"></textarea>
                    <div class="modal-actions">
                        <button id="save-file" class="button button-primary">Save</button>
                        <button id="cancel-edit" class="button">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        #afm-container {
            background: #fff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
        }
        
        #afm-toolbar {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        #afm-toolbar .button {
            margin-right: 10px;
        }
        
        #afm-path {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }
        
        .file-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
        }
        
        .file-item:hover {
            background: #f9f9f9;
        }
        
        .file-icon {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .file-name {
            flex: 1;
            min-width: 0;
        }
        
        .file-size {
            margin-right: 15px;
            color: #666;
            font-size: 12px;
            min-width: 80px;
            text-align: right;
        }
        
        .file-modified {
            margin-right: 15px;
            color: #666;
            font-size: 12px;
            min-width: 140px;
            text-align: center;
        }
        
        .file-actions {
            margin-left: auto;
        }
        
        .file-actions button {
            margin-left: 5px;
            padding: 2px 8px;
            font-size: 11px;
        }
        
        .file-header {
            display: flex;
            align-items: center;
            padding: 10px 8px;
            background: #f7f7f7;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            color: #333;
        }
        
        .file-header .file-icon {
            margin-right: 10px;
            width: 20px;
        }
        
        .file-header .file-name {
            flex: 1;
            min-width: 0;
        }
        
        .file-header .file-size {
            margin-right: 15px;
            min-width: 80px;
            text-align: right;
        }
        
        .file-header .file-modified {
            margin-right: 15px;
            min-width: 140px;
            text-align: center;
        }
        
        .file-header .file-actions {
            margin-left: auto;
            min-width: 120px;
            text-align: center;
        }
        
        /* Progress Modal Styles */
        #progress-modal {
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .progress-bar-container {
            margin: 20px 0;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background-color: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #0073aa, #005a87);
            width: 0%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .progress-text {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            color: #333;
            font-size: 12px;
        }
        
        #progress-status {
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        #edit-modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 5px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        #file-content {
            width: 100%;
            height: 400px;
            font-family: monospace;
            font-size: 14px;
        }
        
        .modal-actions {
            margin-top: 15px;
            text-align: right;
        }
        
        .folder {
            color: #0073aa;
            font-weight: bold;
        }
        
        .back-folder {
            color: #666;
            font-style: italic;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPath = '';
            
            function loadFiles(path = '') {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'afm_browse',
                        path: path,
                        nonce: '<?php echo wp_create_nonce('afm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#afm-files').html(response.data.html);
                            $('#current-path').text(response.data.path);
                            currentPath = response.data.path;
                        }
                    }
                });
            }
            
            // Load files on page load
            loadFiles();
            
            // Refresh button
            $('#afm-refresh-btn').click(function() {
                loadFiles(currentPath);
            });
            
            // Upload button
            $('#afm-upload-btn').click(function() {
                $('#file-upload').click();
            });
            
            // File upload handler
            $('#file-upload').change(function() {
                var files = this.files;
                if (files.length === 0) return;
                
                var formData = new FormData();
                formData.append('action', 'afm_upload');
                formData.append('path', currentPath);
                formData.append('nonce', '<?php echo wp_create_nonce('afm_nonce'); ?>');
                
                var totalSize = 0;
                for (var i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                    totalSize += files[i].size;
                }
                
                // Show progress modal
                showProgressModal('Uploading Files', 'Preparing upload...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    xhr: function() {
                        var xhr = new window.XMLHttpRequest();
                        xhr.upload.addEventListener("progress", function(evt) {
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total * 100;
                                updateProgress(percentComplete, 'Uploading... (' + formatFileSize(evt.loaded) + ' / ' + formatFileSize(evt.total) + ')');
                            }
                        }, false);
                        return xhr;
                    },
                    success: function(response) {
                        hideProgressModal();
                        if (response.success) {
                            alert('Files uploaded successfully!');
                            loadFiles(currentPath);
                        } else {
                            alert('Upload failed: ' + response.data);
                        }
                        $('#file-upload').val('');
                    },
                    error: function() {
                        hideProgressModal();
                        alert('Upload failed: Network error');
                        $('#file-upload').val('');
                    }
                });
            });
            
            // Folder navigation
            $(document).on('click', '.folder-link', function(e) {
                e.preventDefault();
                var path = $(this).data('path');
                loadFiles(path);
            });
            
            // Edit file
            $(document).on('click', '.edit-file', function() {
                var filepath = $(this).data('file');
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'afm_edit_file',
                        file: filepath,
                        nonce: '<?php echo wp_create_nonce('afm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#edit-filename').text(response.data.filename);
                            $('#file-content').val(response.data.content);
                            $('#edit-modal').show();
                        }
                    }
                });
            });
            
            // Save file
            $('#save-file').click(function() {
                var filename = $('#edit-filename').text();
                var content = $('#file-content').val();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'afm_save_file',
                        file: currentPath + '/' + filename,
                        content: content,
                        nonce: '<?php echo wp_create_nonce('afm_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('File saved successfully!');
                            $('#edit-modal').hide();
                            loadFiles(currentPath);
                        } else {
                            alert('Save failed: ' + response.data);
                        }
                    }
                });
            });
            
            // Delete file
            $(document).on('click', '.delete-file', function() {
                if (confirm('Are you sure you want to delete this file?')) {
                    var filepath = $(this).data('file');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'afm_delete',
                            file: filepath,
                            nonce: '<?php echo wp_create_nonce('afm_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('File deleted successfully!');
                                loadFiles(currentPath);
                            } else {
                                alert('Delete failed: ' + response.data);
                            }
                        }
                    });
                }
            });
            
            // Create ZIP
            $('#afm-zip-btn').click(function() {
                if (confirm('Create ZIP archive of current directory?')) {
                    showProgressModal('Creating ZIP Archive', 'Preparing archive...');
                    
                    var startTime = Date.now();
                    var progressInterval = setInterval(function() {
                        var elapsed = Date.now() - startTime;
                        var fakeProgress = Math.min(90, (elapsed / 1000) * 10); // 10% per second, max 90%
                        updateProgress(fakeProgress, 'Compressing files...');
                    }, 200);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'afm_create_zip',
                            path: currentPath,
                            nonce: '<?php echo wp_create_nonce('afm_nonce'); ?>'
                        },
                        success: function(response) {
                            clearInterval(progressInterval);
                            updateProgress(100, 'Archive created! Starting download...');
                            
                            setTimeout(function() {
                                hideProgressModal();
                                if (response.success) {
                                    window.location = ajaxurl + '?action=afm_download&file=' + encodeURIComponent(response.data.file) + '&nonce=<?php echo wp_create_nonce('afm_nonce'); ?>';
                                } else {
                                    alert('ZIP creation failed: ' + response.data);
                                }
                            }, 1000);
                        },
                        error: function() {
                            clearInterval(progressInterval);
                            hideProgressModal();
                            alert('ZIP creation failed: Network error');
                        }
                    });
                }
            });
            
            // Progress modal functions
            function showProgressModal(title, status) {
                $('#progress-title').text(title);
                $('#progress-status').text(status);
                $('#progress-fill').css('width', '0%');
                $('#progress-text').text('0%');
                $('#progress-modal').show();
            }
            
            function updateProgress(percent, status) {
                $('#progress-fill').css('width', percent + '%');
                $('#progress-text').text(Math.round(percent) + '%');
                if (status) {
                    $('#progress-status').text(status);
                }
            }
            
            function hideProgressModal() {
                $('#progress-modal').hide();
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 B';
                var k = 1024;
                var sizes = ['B', 'KB', 'MB', 'GB'];
                var i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }
            
            // Close modal
            $('.close, #cancel-edit').click(function() {
                $('#edit-modal').hide();
            });
        });
        </script>
        <?php
    }
    
    public function ajax_browse() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $path = sanitize_text_field($_POST['path']);
        $full_path = ABSPATH . trim($path, '/');
        
        if (!is_dir($full_path)) {
            wp_send_json_error('Directory not found');
        }
        
        $files = scandir($full_path);
        $html = '';
        
        // Add back button if not in root
        if ($path !== '' && $path !== '/') {
            $parent_path = dirname($path);
            if ($parent_path === '.') $parent_path = '';
            $html .= '<div class="file-item back-folder">';
            $html .= '<span class="file-icon">📁</span>';
            $html .= '<span class="file-name"><a href="#" class="folder-link" data-path="' . esc_attr($parent_path) . '">.. (Parent Directory)</a></span>';
            $html .= '<span class="file-size">-</span>';
            $html .= '<span class="file-modified">-</span>';
            $html .= '<div class="file-actions"></div>';
            $html .= '</div>';
        }
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $file_path = $full_path . '/' . $file;
            $relative_path = trim($path . '/' . $file, '/');
            $modified_time = filemtime($file_path);
            $formatted_time = $this->format_date_time($modified_time);
            
            if (is_dir($file_path)) {
                $html .= '<div class="file-item folder">';
                $html .= '<span class="file-icon">📁</span>';
                $html .= '<span class="file-name"><a href="#" class="folder-link" data-path="' . esc_attr($relative_path) . '">' . esc_html($file) . '</a></span>';
                $html .= '<span class="file-size">-</span>';
                $html .= '<span class="file-modified">' . esc_html($formatted_time) . '</span>';
                $html .= '<div class="file-actions"></div>';
                $html .= '</div>';
            } else {
                $file_size = filesize($file_path);
                $html .= '<div class="file-item">';
                $html .= '<span class="file-icon">📄</span>';
                $html .= '<span class="file-name">' . esc_html($file) . '</span>';
                $html .= '<span class="file-size">' . $this->format_file_size($file_size) . '</span>';
                $html .= '<span class="file-modified">' . esc_html($formatted_time) . '</span>';
                $html .= '<div class="file-actions">';
                
                if ($this->is_editable($file)) {
                    $html .= '<button class="button edit-file" data-file="' . esc_attr($relative_path) . '">Edit</button>';
                }
                
                $html .= '<button class="button delete-file" data-file="' . esc_attr($relative_path) . '">Delete</button>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }
        
        wp_send_json_success(array('html' => $html, 'path' => $path));
    }
    
    public function ajax_upload() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $path = sanitize_text_field($_POST['path']);
        $upload_dir = ABSPATH . trim($path, '/');
        
        if (!is_dir($upload_dir)) {
            wp_send_json_error('Directory not found');
        }
        
        if (empty($_FILES['files'])) {
            wp_send_json_error('No files uploaded');
        }
        
        $files = $_FILES['files'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $filename = sanitize_file_name($files['name'][$i]);
                $target_file = $upload_dir . '/' . $filename;
                
                if (move_uploaded_file($files['tmp_name'][$i], $target_file)) {
                    // File uploaded successfully
                } else {
                    wp_send_json_error('Failed to upload: ' . $filename);
                }
            }
        }
        
        wp_send_json_success('Files uploaded successfully');
    }
    
    public function ajax_edit_file() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $file = sanitize_text_field($_POST['file']);
        $full_path = ABSPATH . trim($file, '/');
        
        if (!file_exists($full_path) || !$this->is_editable(basename($full_path))) {
            wp_send_json_error('File not found or not editable');
        }
        
        $content = file_get_contents($full_path);
        
        wp_send_json_success(array(
            'content' => $content,
            'filename' => basename($file)
        ));
    }
    
    public function ajax_save_file() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $file = sanitize_text_field($_POST['file']);
        $content = wp_unslash($_POST['content']);
        $full_path = ABSPATH . trim($file, '/');
        
        if (!$this->is_editable(basename($full_path))) {
            wp_send_json_error('File type not editable');
        }
        
        if (file_put_contents($full_path, $content) !== false) {
            wp_send_json_success('File saved successfully');
        } else {
            wp_send_json_error('Failed to save file');
        }
    }
    
    public function ajax_delete() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $file = sanitize_text_field($_POST['file']);
        $full_path = ABSPATH . trim($file, '/');
        
        if (!file_exists($full_path)) {
            wp_send_json_error('File not found');
        }
        
        if (is_dir($full_path)) {
            if (rmdir($full_path)) {
                wp_send_json_success('Directory deleted successfully');
            } else {
                wp_send_json_error('Failed to delete directory');
            }
        } else {
            if (unlink($full_path)) {
                wp_send_json_success('File deleted successfully');
            } else {
                wp_send_json_error('Failed to delete file');
            }
        }
    }
    
    public function ajax_create_zip() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        if (!class_exists('ZipArchive')) {
            wp_send_json_error('ZipArchive class not available');
        }
        
        $path = sanitize_text_field($_POST['path']);
        $source_dir = ABSPATH . trim($path, '/');
        
        if (!is_dir($source_dir)) {
            wp_send_json_error('Directory not found');
        }
        
        $zip_name = 'wordpress_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $zip_path = wp_upload_dir()['basedir'] . '/' . $zip_name;
        
        $zip = new ZipArchive();
        if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
            wp_send_json_error('Cannot create zip file');
        }
        
        $this->add_to_zip($zip, $source_dir, '');
        $zip->close();
        
        wp_send_json_success(array('file' => $zip_path));
    }
    
    private function add_to_zip($zip, $source, $relative_path) {
        $files = scandir($source);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $file_path = $source . '/' . $file;
            $zip_path = $relative_path . '/' . $file;
            
            if (is_dir($file_path)) {
                $zip->addEmptyDir(ltrim($zip_path, '/'));
                $this->add_to_zip($zip, $file_path, $zip_path);
            } else {
                $zip->addFile($file_path, ltrim($zip_path, '/'));
            }
        }
    }
    
    public function ajax_download() {
        check_ajax_referer('afm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Access denied');
        }
        
        $file = sanitize_text_field($_GET['file']);
        
        if (!file_exists($file)) {
            wp_die('File not found');
        }
        
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Content-Length: ' . filesize($file));
        
        readfile($file);
        unlink($file); // Clean up the temporary zip file
        exit;
    }
    
    private function is_editable($filename) {
        $editable_extensions = array('php', 'html', 'css', 'js', 'txt', 'md', 'json', 'xml', 'htaccess');
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        return in_array($extension, $editable_extensions) || $filename === '.htaccess';
    }
    
    private function format_file_size($bytes) {
        if ($bytes === 0) return '0 B';
        
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $base = 1024;
        $exponent = floor(log($bytes) / log($base));
        
        return round($bytes / pow($base, $exponent), 1) . ' ' . $units[$exponent];
    }
    
    private function format_date_time($timestamp) {
        $now = current_time('timestamp');
        $diff = $now - $timestamp;
        
        // If less than 24 hours ago, show relative time
        if ($diff < 86400) {
            if ($diff < 3600) {
                $minutes = floor($diff / 60);
                return $minutes <= 1 ? '1 min ago' : $minutes . ' mins ago';
            } else {
                $hours = floor($diff / 3600);
                return $hours == 1 ? '1 hour ago' : $hours . ' hours ago';
            }
        }
        
        // If less than 7 days ago, show days
        if ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days == 1 ? '1 day ago' : $days . ' days ago';
        }
        
        // Otherwise show formatted date
        return date('M j, Y H:i', $timestamp);
    }
}

new WP_Advanced_File_Manager();
?>