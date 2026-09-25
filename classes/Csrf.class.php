<?php

/**
 * CSRF protection for the legacy pages.
 *
 * Every POST request must carry the session's token, either as the `csrf_token` form field or the
 * `X-CSRF-Token` header. The token is added to pages automatically: an output filter inserts a
 * hidden field into each POST form, and a script sends the header with every jQuery AJAX request.
 */
final class Csrf {

    const FIELD = 'csrf_token';
    const HEADER = 'HTTP_X_CSRF_TOKEN';

    public static function token(): string {
        if (empty($_SESSION['CSRF_TOKEN']) || !is_string($_SESSION['CSRF_TOKEN'])) {
            $_SESSION['CSRF_TOKEN'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['CSRF_TOKEN'];
    }

    public static function field(): string {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function isValid(?string $token): bool {
        return is_string($token) && $token !== '' && hash_equals(self::token(), $token);
    }

    /** Rejects POST requests without a valid token and installs the output filter. */
    public static function protect(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = $_POST[self::FIELD] ?? ($_SERVER[self::HEADER] ?? null);
            if (!self::isValid($token)) {
                http_response_code(403);
                exit('Invalid or expired form token. Please go back, reload the page and try again.');
            }
            unset($_POST[self::FIELD]);
        }
        ob_start([self::class, 'filterOutput']);
    }

    /** Output-buffer callback: adds the token to POST forms and AJAX requests on HTML pages. */
    public static function filterOutput(string $html): string {
        if (stripos($html, '<form') === false && stripos($html, '</body>') === false) {
            return $html;
        }
        $field = self::field();
        $html = preg_replace_callback(
            '/<form\b[^>]*>/i',
            function ($m) use ($field) {
                return preg_match('/\bmethod\s*=\s*["\']?post\b/i', $m[0]) ? $m[0] . $field : $m[0];
            },
            $html
        );
        $script = '<script>(function(){var t=' . json_encode(self::token()) . ';'
            . 'if(window.jQuery){jQuery.ajaxPrefilter(function(o,oo,x){x.setRequestHeader("X-CSRF-Token",t);});}'
            . '})();</script>';
        // Register the AJAX header right after jQuery is loaded, or at the end of the page otherwise.
        $count = 0;
        $html = preg_replace('#(<script[^>]+src=["\'][^"\']*jquery(\.min)?\.js["\'][^>]*>\s*</script>)#i', '$1' . $script, $html, 1, $count);
        if ($count === 0) {
            $html = preg_replace('#</body>#i', $script . '</body>', $html, 1);
        }
        return $html;
    }

}
