<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Exception\PreCommandException;
use BitApps\FM\Plugin;
use WP_Error;

\defined('ABSPATH') or exit();
class FileEditValidator
{
    public function validate($cmd, &$args, $elfinder, $volume)
    {
        try {
            $this->checkPermission();
            Plugin::instance()->accessControl()->scanFile($cmd, $args);
        } catch (PreCommandException $th) {
            return $th->getError();
        }

        $args['content'] = stripcslashes($args['content']); // Default wordpress slashing removed.

        // Checking syntax for PHP file.
        if (strpos($args['content'], '<?php') !== false) {
            try {
                $this->checkSyntax($args['content'], $volume->getPath($args['target']));
            } catch (PreCommandException $th) {
                return $th->getError();
            }
        }
    }

    public function checkSyntax($content, $realFile)
    {
        if (\defined('BITAPPS_FM_FM_DISABLE_SYNTAX_CHECK') && BITAPPS_FM_FM_DISABLE_SYNTAX_CHECK) {
            return;
        }

        $wpError = (new PhpSyntaxChecker())->check($content, $realFile);

        if ($wpError instanceof WP_Error) {
            $message = $wpError->get_error_message();

            throw new PreCommandException(esc_html($message));
        }
    }

    private function checkPermission()
    {
        $error = '';
        if (\defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) {
            $error = __('File edit is disabled. To allow edit, please set DISALLOW_FILE_EDIT to false in wp-config file', 'file-manager');
        }

        if (empty($error) && !Plugin::instance()->permissions()->currentUserCanRun('edit')) {
            $error = __('Not Authorized to edit file', 'file-manager');
        }

        if (!empty($error)) {
            throw new PreCommandException(esc_html($error));
        }
    }
}
