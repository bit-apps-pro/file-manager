<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Plugin;

\defined('ABSPATH') or exit();
class MediaSynchronizer
{
    public $wpUploadBaseDirectory;

    function __construct()
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $this->wpUploadBaseDirectory = wp_upload_dir()['basedir'];
    }

    // Triggers when a file is uploaded and initiates the uploading process for single or batch files.
    public function onFileUpload($cmd, &$result, $args, $elfinder, $volume)
    {
        $targetPath = $volume->getPath($args['target']);

        if (
            PermissionsProvider::realpathWithin($targetPath, $this->wpUploadBaseDirectory) !== null
            && Plugin::instance()->preferences()->isWpMediaSyncEnabled()
        ) {
            $images = [];
            for ($file = 0; $file < \count($args['FILES']['upload']['name']); $file++) {
                $images[] = [
                    'name' => $args['FILES']['upload']['name'][$file],
                    'type' => wp_check_filetype($args['FILES']['upload']['name'][$file], null),
                    'path' => trailingslashit($targetPath) . $args['FILES']['upload']['name'][$file],
                    'url'  => $this->abs_path_to_url(
                        trailingslashit($targetPath)
                         . $args['FILES']['upload']['name'][$file]
                    ),
                ];
            }

            $this->addMedia($images);
        }
    }

    private function addMedia($images)
    {
        foreach ($images as $image) {
            $attachment = [
                'post_mime_type' => $image['type']['type'],
                'post_title'     => sanitize_file_name($image['name']),
            ];
            $attachmentId  = wp_insert_attachment($attachment, $image['path']);
            $attachData    = wp_generate_attachment_metadata($attachmentId, $image['path']);
            wp_update_attachment_metadata($attachmentId, $attachData);
        }
    }

    /**
     * Converts an absolute file path to a URL. User can upload files to any directory
     * within the WordPress root, but only files within the wp-content/uploads directory
     * will be added to the media library. This function helps to generate the correct
     * URL for the uploaded file based on its absolute path.
     *
     * @param string $path The absolute file path to convert.
     *
     * @return string The corresponding URL for the given file path.
     */
    private function abs_path_to_url($path = '')
    {
        $url = str_replace(
            wp_normalize_path(untrailingslashit(ABSPATH)),
            site_url(),
            wp_normalize_path($path)
        );

        return esc_url_raw($url);
    }
}
