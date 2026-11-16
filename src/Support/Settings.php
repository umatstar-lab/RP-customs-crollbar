<?php
/** @license GNU General Public License version 2 or later */
namespace Joomla\Plugin\System\RPCustomScrollbar\Support;

\defined('_JEXEC') or die;

/** Only validated tokens may cross the boundary into a CSS declaration. */
final class Settings
{
    public static function defaults(): array
    {
        $defaults = ['site' => 1, 'admin' => 1];
        foreach (['s', 'a'] as $client) {
            foreach (['tk' => '#ffffff', 'tb' => '#d1c9cf', 'tbh' => '#787878'] as $part => $color) {
                $prefix = $part . $client;
                $defaults[$prefix . 'bgcolor'] = $color;
                $defaults[$prefix . 'border'] = 1;
                $defaults[$prefix . 'bwidth'] = '0';
                $defaults[$prefix . 'bcolor'] = '#ffffff';
                $defaults[$prefix . 'bstyle'] = 'solid';
                $defaults[$prefix . 'bradius'] = '100px';
            }
            $defaults['tk' . $client . 'width'] = '12px';
        }
        return $defaults;
    }

    public static function normalize(string $name, mixed $value): string|int
    {
        $defaults = self::defaults();
        if (!array_key_exists($name, $defaults)) {
            return '';
        }
        $default = $defaults[$name];
        if ($name === 'site' || $name === 'admin' || str_ends_with($name, 'border')) {
            return match ($value) {
                0, '0', false => 0,
                1, '1', true => 1,
                default => $default,
            };
        }
        if (!is_string($value) || strlen($value) > 64) {
            return $default;
        }
        $value = strtolower(trim($value));
        if (str_ends_with($name, 'color')) {
            return preg_match('/\A#(?:[0-9a-f]{3}|[0-9a-f]{6})\z/D', $value) === 1 ? $value : $default;
        }
        if (str_ends_with($name, 'style')) {
            return in_array($value, ['none', 'solid', 'dotted', 'dashed', 'double', 'groove', 'ridge', 'inset', 'outset'], true)
                ? $value : $default;
        }
        // Allow a single, nonnegative dimension. No functions, comments, URLs or delimiters.
        if (preg_match('/\A(\d{1,4}(?:\.\d{1,4})?|\.\d{1,4})(px|em|rem|%)?\z/D', $value, $matches) !== 1) {
            return $default;
        }
        $number = (float) $matches[1];
        $unit = $matches[2] ?? '';
        if ($number === 0.0) {
            return '0';
        }
        $radius = str_ends_with($name, 'radius');
        $limit = match ($unit) {
            'px' => $radius ? 1000 : 200,
            'em', 'rem' => $radius ? 100 : 10,
            '%' => 100,
            default => 0,
        };
        return $number <= $limit ? $value : $default;
    }

    public static function clean(array $values): array
    {
        $clean = [];
        foreach (self::defaults() as $name => $default) {
            $clean[$name] = self::normalize($name, $values[$name] ?? $default);
        }
        return $clean;
    }

    public static function css(array $values, string $client): string
    {
        if (!in_array($client, ['site', 'administrator'], true)) {
            return '';
        }
        $p = self::clean($values);
        $suffix = $client === 'site' ? 's' : 'a';
        if (!$p[$client === 'site' ? 'site' : 'admin']) {
            return '';
        }
        $css = '';
        $width = $p['tk' . $suffix . 'width'];
        foreach (['tk' => '::-webkit-scrollbar', 'tb' => '::-webkit-scrollbar-thumb', 'tbh' => '::-webkit-scrollbar-thumb:hover'] as $part => $selector) {
            $key = $part . $suffix;
            $css .= $selector . '{';
            if ($part === 'tk') {
                $css .= 'width:' . $width . ';height:' . $width . ';';
            }
            $css .= 'background-color:' . $p[$key . 'bgcolor'] . ';';
            if ($p[$key . 'border']) {
                $css .= 'border:' . $p[$key . 'bwidth'] . ' ' . $p[$key . 'bstyle'] . ' ' . $p[$key . 'bcolor'] . ';';
                $css .= 'border-radius:' . $p[$key . 'bradius'] . ';';
            }
            $css .= '}';
        }
        // Firefox uses standard properties; keep WebKit-specific dimensions in other browsers.
        $standardWidth = $width === '0' ? 'none' : 'auto';
        $css .= '@supports not selector(::-webkit-scrollbar){*{scrollbar-color:'
            . $p['tb' . $suffix . 'bgcolor'] . ' ' . $p['tk' . $suffix . 'bgcolor']
            . ';scrollbar-width:' . $standardWidth . ';}}';
        $css .= '@media(forced-colors:active){*{scrollbar-color:auto;scrollbar-width:auto;}'
            . '::-webkit-scrollbar,::-webkit-scrollbar-thumb,::-webkit-scrollbar-thumb:hover{all:revert;}}';
        return $css;
    }
}
