<?php

use BitApps\FM\Config;

if (!\defined('ABSPATH')) {
    exit();
}
$assetUrl = esc_url(Config::get('ASSET_URI'));
?>
 <!-- Add Banner Here -->
<br/>
<br/>
<hr>
        <?php
            printf(
                '💡<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span></a>',
                'https://bitapps.pro/advanced-contact-form-builder-for-wordpress',
                'The Most Advanced Contact Form Builder For WordPress: Bit Form',
                '(opens in a new tab)'
            );
?>
<p class="community-events-footer">
        <?php
    printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
        'https://bitapps.pro/contact/',
        'Support',
        '(opens in a new tab)'
    );
?>

        |

        <?php
    printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
        'https://bitapps.pro/blog',
        'Blog',
        '(opens in a new tab)'
    );
?>

        |

        <?php
    printf(
        '<a href="%1$s" target="_blank">%2$s <span class="screen-reader-text"> %3$s</span><span aria-hidden="true" class="dashicons dashicons-facebook"></span></a>',
        'https://www.facebook.com/groups/3308027439209387',
        'Facebook Group',
        '(opens in a new tab)'
    );
?>
</p>