<?php

/**
 * Pages pre-rendered by the Python scripts (html/profile, html/ranking, html/fame).
 *
 * They used to be pulled in with `require`, which executed them as PHP code even though they
 * contain player-chosen names. They are now sent as plain text.
 */
final class GeneratedPage {

    public static function path(string $relative): ?string {
        if (!preg_match('#^html/(profile|ranking|fame)/[A-Za-z0-9_\-]+\.html$#', $relative)) {
            return null;
        }
        return BASE_PATH . '/' . $relative;
    }

    public static function exists(string $relative): bool {
        $path = self::path($relative);
        return $path !== null && is_file($path);
    }

    /** Outputs the page, or a short notice when it has not been generated yet. */
    public static function output(string $relative): void {
        if (self::exists($relative)) {
            readfile(self::path($relative));
        } else {
            echo '<tr><td colspan="10" class="center">' . _('This information is being generated. Please check again later.') . '</td></tr>';
        }
    }

}
